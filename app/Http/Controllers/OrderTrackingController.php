<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PhoneVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class OrderTrackingController extends Controller
{
    private const SESSION_PENDING_PHONE = 'order_lookup_pending_phone';
    private const SESSION_VERIFIED_PHONE = 'order_lookup_verified_phone';
    private const SESSION_VERIFIED_UNTIL = 'order_lookup_verified_until';

    public function show(string $token)
    {
        $order = Order::with('items')->where('tracking_token', $token)->firstOrFail();

        return view('pedido_confirmacion', compact('order'));
    }

    public function apiStatus(string $token)
    {
        $order = Order::where('tracking_token', $token)->firstOrFail();

        return response()->json([
            'status' => $order->status,
            'es_a_domicilio' => $order->esADomicilio(),
        ]);
    }

    public function confirmarRecibido(string $token)
    {
        $order = Order::where('tracking_token', $token)->firstOrFail();

        if (in_array($order->status, ['delivering', 'ready']) && !$order->delivered_confirmed_at) {
            $order->status = 'delivered';
            $order->delivered_confirmed_at = now();
            $order->save();
        }

        return response()->json(['success' => true, 'status' => $order->status]);
    }

    // Paso 1: pedir el teléfono.
    public function lookup(Request $request)
    {
        return view('pedido_historial', [
            'step' => 'phone',
            'telefono' => trim((string) $request->query('telefono')),
        ]);
    }

    // Manda el código por SMS/llamada al teléfono mismo, vía Twilio Verify.
    public function requestOtp(Request $request, PhoneVerification $sms)
    {
        $validated = $request->validate([
            'telefono' => 'required|digits:10',
            'canal' => 'nullable|in:sms,llamada',
        ]);
        $telefono = $validated['telefono'];
        $canal = $validated['canal'] ?? 'sms';

        $phoneKey = 'sms-otp-phone:' . $telefono;
        $ipKey = 'sms-otp-ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($phoneKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            return redirect()->route('order.lookup')->with('error', 'Espera un momento antes de pedir otro código.');
        }

        // No revelamos si ese teléfono tiene pedidos o no (mismo mensaje siempre).
        if (Order::where('customer_phone', $telefono)->exists()) {
            try {
                $sms->send($telefono, $canal);
                Log::info('phone_verification', ['flujo' => 'order_lookup_web', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'enviado']);
            } catch (\Throwable $e) {
                report($e);
                Log::info('phone_verification', ['flujo' => 'order_lookup_web', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'fallo_envio']);

                // No consumimos el límite de intentos: la falla fue nuestra
                // (Twilio/config), no algo que el usuario deba "pagar".
                return redirect()->route('order.lookup')->with('error', 'No pudimos enviar el SMS. Intenta con llamada o contáctanos directamente.');
            }
        }

        RateLimiter::hit($phoneKey, 600);
        RateLimiter::hit($ipKey, 3600);

        $request->session()->put(self::SESSION_PENDING_PHONE, $telefono);

        $mensaje = $canal === 'llamada' ? 'Te estamos llamando con tu código.' : 'Si el dato está registrado, te mandamos un código por SMS.';

        return redirect()->route('order.lookup.verify')->with('success', $mensaje);
    }

    public function showVerify(Request $request)
    {
        $telefono = $request->session()->get(self::SESSION_PENDING_PHONE);

        if (!$telefono) {
            return redirect()->route('order.lookup');
        }

        return view('pedido_historial', [
            'step' => 'code',
            'telefono' => $telefono,
        ]);
    }

    public function verifyOtp(Request $request, PhoneVerification $sms)
    {
        $telefono = $request->session()->get(self::SESSION_PENDING_PHONE);

        if (!$telefono) {
            return redirect()->route('order.lookup');
        }

        $request->validate([
            'codigo' => 'required|string',
        ]);

        if (!$sms->check($telefono, $request->codigo)) {
            Log::info('phone_verification', ['flujo' => 'order_lookup_web', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'codigo_invalido']);

            return back()->with('error', 'El código no es correcto o ya caducó.');
        }

        Log::info('phone_verification', ['flujo' => 'order_lookup_web', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'aprobado']);

        $request->session()->forget(self::SESSION_PENDING_PHONE);
        $request->session()->put(self::SESSION_VERIFIED_PHONE, $telefono);
        $request->session()->put(self::SESSION_VERIFIED_UNTIL, now()->addMinutes(30));

        return redirect()->route('order.lookup.results');
    }

    public function results(Request $request)
    {
        $telefono = $request->session()->get(self::SESSION_VERIFIED_PHONE);
        $vigente = $request->session()->get(self::SESSION_VERIFIED_UNTIL);

        if (!$telefono || !$vigente || now()->greaterThan($vigente)) {
            $request->session()->forget([self::SESSION_VERIFIED_PHONE, self::SESSION_VERIFIED_UNTIL]);
            return redirect()->route('order.lookup')->with('error', 'Tu sesión de verificación expiró. Vuelve a pedir tu código.');
        }

        $orders = Order::where('customer_phone', $telefono)
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();

        return view('pedido_historial', [
            'step' => 'results',
            'telefono' => $telefono,
            'orders' => $orders,
        ]);
    }
}
