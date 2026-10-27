<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PhoneVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;


class OrderController extends Controller
{
    public function mine(Request $request): JsonResponse
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $orders], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function track(string $token): JsonResponse
    {
        $order = Order::with('items')->where('tracking_token', $token)->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado.'], 404, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'status' => $order->status,
                'es_a_domicilio' => $order->esADomicilio(),
                'total' => (float) $order->total,
                'items' => $order->items,
                'created_at' => $order->created_at,
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function confirmReceived(string $token): JsonResponse
    {
        $order = Order::where('tracking_token', $token)->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Pedido no encontrado.'], 404, [], JSON_UNESCAPED_UNICODE);
        }

        if (in_array($order->status, ['delivering', 'ready']) && !$order->delivered_confirmed_at) {
            $order->status = 'delivered';
            $order->delivered_confirmed_at = now();
            $order->save();
        }

        return response()->json(['success' => true, 'status' => $order->status], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function requestLookupOtp(Request $request, PhoneVerification $sms): JsonResponse
    {
        $validated = $request->validate([
            'telefono' => ['required', 'digits:10'],
            'canal' => ['nullable', 'in:sms,llamada'],
        ]);
        $telefono = $validated['telefono'];

        if ($tooMany = $this->tooManyPhoneAttempts($request, $telefono)) {
            return $tooMany;
        }

        if (Order::where('customer_phone', $telefono)->exists()) {
            try {
                $sms->send($telefono, $validated['canal'] ?? 'sms');
                Log::info('phone_verification', ['flujo' => 'order_lookup', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'enviado']);
            } catch (\Throwable $e) {
                report($e);
                Log::info('phone_verification', ['flujo' => 'order_lookup', 'telefono' => PhoneVerification::mask($telefono), 'resultado' => 'fallo_envio']);

                // No consumimos el límite: la falla fue nuestra (Twilio/config).
                return response()->json([
                    'success' => false,
                    'error' => 'sms_unavailable',
                    'message' => 'No pudimos enviar el SMS. Intenta con llamada o por correo.',
                ], 503, [], JSON_UNESCAPED_UNICODE);
            }
        }

        $this->hitPhoneAttempts($request, $telefono);

        return response()->json([
            'success' => true,
            'message' => 'Si el dato está registrado, te enviamos un código.',
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /** Paso 2 (invitados): verifica el código con Twilio y devuelve los pedidos de ese teléfono. */
    public function verifyLookupOtp(Request $request, PhoneVerification $sms): JsonResponse
    {
        $validated = $request->validate([
            'telefono' => ['required', 'digits:10'],
            'codigo' => ['required', 'string'],
        ]);

        if (!$sms->check($validated['telefono'], $validated['codigo'])) {
            Log::info('phone_verification', ['flujo' => 'order_lookup', 'telefono' => PhoneVerification::mask($validated['telefono']), 'resultado' => 'codigo_invalido']);

            return response()->json([
                'success' => false,
                'error' => 'invalid_or_expired_code',
                'message' => 'El código no es correcto o ya caducó.',
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        Log::info('phone_verification', ['flujo' => 'order_lookup', 'telefono' => PhoneVerification::mask($validated['telefono']), 'resultado' => 'aprobado']);

        $orders = Order::where('customer_phone', $validated['telefono'])
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();

        return response()->json(['success' => true, 'data' => $orders], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /** Mismo límite que AuthController: 3 por teléfono/10min, 10 por IP/hora. */
    private function tooManyPhoneAttempts(Request $request, string $telefono): ?JsonResponse
    {
        $phoneKey = 'sms-otp-phone:' . $telefono;
        $ipKey = 'sms-otp-ip:' . $request->ip();

        if (RateLimiter::tooManyAttempts($phoneKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            $segundos = max(RateLimiter::availableIn($phoneKey), RateLimiter::availableIn($ipKey));

            return response()->json([
                'success' => false,
                'error' => 'too_many_attempts',
                'message' => 'Espera un momento antes de pedir otro código.',
            ], 429, ['Retry-After' => $segundos], JSON_UNESCAPED_UNICODE);
        }

        return null;
    }

    private function hitPhoneAttempts(Request $request, string $telefono): void
    {
        RateLimiter::hit('sms-otp-phone:' . $telefono, 600);
        RateLimiter::hit('sms-otp-ip:' . $request->ip(), 3600);
    }
}
