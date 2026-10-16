<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderLookupOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

    // Manda un código de 6 dígitos al correo del pedido más reciente de ese teléfono.
    public function requestOtp(Request $request)
    {
        $request->validate([
            'telefono' => 'required|string|max:20',
        ]);

        $telefono = trim($request->telefono);

        $ultimoPedidoConCorreo = Order::where('customer_phone', $telefono)
            ->whereNotNull('customer_email')
            ->where('customer_email', '!=', '')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$ultimoPedidoConCorreo) {
            return redirect()->route('order.lookup')
                ->with('error', 'Ese teléfono no tiene un correo asociado a sus pedidos, así que no podemos mandarte un código. Si acabas de pedir, revisa el enlace de seguimiento que te llegó por correo, o contáctanos directamente.');
        }

        $correo = $ultimoPedidoConCorreo->customer_email;

        $ultimoEnvio = OrderLookupOtp::where('phone', $telefono)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->exists();

        if (!$ultimoEnvio) {
            $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            OrderLookupOtp::create([
                'phone' => $telefono,
                'code' => $codigo,
                'expires_at' => now()->addMinutes(10),
            ]);

            $this->enviarCorreoOtp($correo, $codigo);
        }

        $request->session()->put(self::SESSION_PENDING_PHONE, $telefono);

        return redirect()->route('order.lookup.verify')->with('success', 'Te mandamos un código a ' . $this->enmascararCorreo($correo));
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

    public function verifyOtp(Request $request)
    {
        $telefono = $request->session()->get(self::SESSION_PENDING_PHONE);

        if (!$telefono) {
            return redirect()->route('order.lookup');
        }

        $request->validate([
            'codigo' => 'required|string|size:6',
        ]);

        $otp = OrderLookupOtp::where('phone', $telefono)
            ->where('expires_at', '>=', now())
            ->orderBy('id', 'desc')
            ->first();

        if (!$otp || $otp->attempts >= 5) {
            return back()->with('error', 'Tu código venció o ya no es válido. Pide uno nuevo.');
        }

        if ($otp->code !== trim($request->codigo)) {
            $otp->increment('attempts');
            return back()->with('error', 'Ese código no es correcto.');
        }

        $otp->delete();
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

    private function enviarCorreoOtp(string $correo, string $codigo): void
    {
        try {
            Mail::send('emails.order_lookup_otp', ['codigo' => $codigo], function ($message) use ($correo) {
                $message->to($correo)->subject('Tu código para ver tus pedidos - La 501 Sports');
            });
        } catch (\Exception $e) {
            Log::warning('Fallo al enviar correo de verificación de pedidos: ' . $e->getMessage());
        }
    }

    private function enmascararCorreo(string $correo): string
    {
        [$usuario, $dominio] = array_pad(explode('@', $correo, 2), 2, '');

        if (strlen($usuario) <= 2) {
            $usuarioOculto = str_repeat('*', strlen($usuario));
        } else {
            $usuarioOculto = $usuario[0] . str_repeat('*', strlen($usuario) - 2) . $usuario[strlen($usuario) - 1];
        }

        return $usuarioOculto . '@' . $dominio;
    }
}
