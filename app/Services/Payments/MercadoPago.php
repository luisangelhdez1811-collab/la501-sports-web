<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cliente de la API de Mercado Pago. Aquí vive todo lo que habla con ellos: crear el enlace de pago,
 * consultar un pago, devolverlo y validar la firma de sus avisos (webhook).
 *
 * La URL a la que Mercado Pago regresa al cliente solo sirve para mostrar una pantalla: nunca es
 * prueba de pago. Un pedido se da por pagado únicamente después de consultar el pago aquí
 * (OrderPayments::syncCardPayment).
 */
class MercadoPago
{
    private const API = 'https://api.mercadopago.com';

    private function http(): PendingRequest
    {
        return Http::withToken((string) config('services.mercadopago.access_token'))
            ->acceptJson()
            ->timeout(20);
    }

    /**
     * Crea el enlace de pago de un pedido con tarjeta. Devuelve el init_point o null si Mercado Pago
     * no respondió (el llamador decide qué decirle al cliente).
     */
    public function createPreference(Order $order): ?string
    {
        $order->loadMissing('items');
        $token = $order->trackingToken();

        $cuerpo = [
            'items' => $order->items->map(fn ($item) => [
                'title' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->price,
                'currency_id' => 'MXN',
            ])->values()->all(),
            // El pedido se identifica por esta referencia al consultar el pago.
            'external_reference' => (string) $order->id,
            // Mercado Pago avisa aquí cuando el pago cambia, aunque el cliente cierre la pestaña.
            'notification_url' => url('/api/webhooks/mercadopago'),
            // El token del pedido viaja de regreso para saber qué pedido mostrarle a quien vuelve.
            'back_urls' => [
                'success' => route('payment.success', ['t' => $token]),
                'failure' => route('payment.failure', ['t' => $token]),
                'pending' => route('payment.pending', ['t' => $token]),
            ],
            'auto_return' => 'approved',
            // Aprobado o rechazado, sin pagos «en proceso» que se queden colgados.
            'binary_mode' => true,
            'payment_methods' => [
                // Pagos en OXXO o por transferencia llegarían horas después, con la comida ya fría.
                'excluded_payment_types' => [['id' => 'ticket'], ['id' => 'atm'], ['id' => 'bank_transfer']],
                // Los Términos dicen «una sola exhibición».
                'installments' => 1,
            ],
        ];

        try {
            $respuesta = $this->http()->post(self::API . '/checkout/preferences', $cuerpo);
        } catch (ConnectionException $e) {
            Log::error('Mercado Pago no respondió al crear la preferencia de pago.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($respuesta->successful() && !empty($respuesta->json('init_point'))) {
            return $respuesta->json('init_point');
        }

        Log::error('Fallo de Mercado Pago al crear preferencia de pago.', [
            'order_id' => $order->id,
            'status' => $respuesta->status(),
            'response' => $respuesta->json(),
        ]);

        return null;
    }

    /** Consulta un pago. null si no existe o Mercado Pago no respondió. */
    public function getPayment(string|int $paymentId): ?array
    {
        $paymentId = trim((string) $paymentId);
        if (!ctype_digit($paymentId)) {
            return null;
        }

        try {
            $respuesta = $this->http()->get(self::API . '/v1/payments/' . $paymentId);
        } catch (ConnectionException $e) {
            Log::warning('Mercado Pago no respondió al consultar un pago.', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);

            return null;
        }

        if (!$respuesta->successful()) {
            if ($respuesta->status() !== 404) {
                Log::warning('Mercado Pago rechazó la consulta de un pago.', ['payment_id' => $paymentId, 'status' => $respuesta->status()]);
            }

            return null;
        }

        return $respuesta->json();
    }

    /**
     * Devuelve el pago completo (o el monto indicado). La llave de idempotencia evita que un reintento
     * devuelva dos veces. null si Mercado Pago no pudo hacerlo (hay que devolverlo a mano).
     */
    public function refund(string|int $paymentId, string $idempotencyKey, ?float $amount = null): ?array
    {
        try {
            $respuesta = $this->http()
                ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
                ->post(self::API . '/v1/payments/' . $paymentId . '/refunds', $amount === null ? [] : ['amount' => $amount]);
        } catch (ConnectionException $e) {
            Log::error('Mercado Pago no respondió al devolver un pago.', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);

            return null;
        }

        if ($respuesta->successful()) {
            return $respuesta->json();
        }

        Log::error('Mercado Pago no pudo devolver el pago.', [
            'payment_id' => $paymentId,
            'status' => $respuesta->status(),
            'response' => $respuesta->json(),
        ]);

        return null;
    }

    /**
     * Valida la firma x-signature de un aviso: ts=...,v1=... con
     * HMAC-SHA256("id:{data.id};request-id:{x-request-id};ts:{ts};") y la clave secreta del webhook.
     *
     * Sin clave configurada no se puede validar: se deja pasar, porque el aviso nunca marca nada pagado
     * por sí solo (OrderPayments consulta siempre el pago a la API), pero se deja un aviso en el log.
     */
    public function validWebhookSignature(Request $request, string $dataId): bool
    {
        $secreto = config('services.mercadopago.webhook_secret');

        if (blank($secreto)) {
            Log::warning('MERCADOPAGO_WEBHOOK_SECRET no está configurada: no se validó la firma del aviso de Mercado Pago.');

            return true;
        }

        $partes = collect(explode(',', (string) $request->header('x-signature')))
            ->mapWithKeys(function (string $parte) {
                [$clave, $valor] = array_pad(explode('=', trim($parte), 2), 2, '');

                return [trim($clave) => trim($valor)];
            });

        $ts = $partes->get('ts');
        $v1 = $partes->get('v1');
        if (blank($ts) || blank($v1)) {
            return false;
        }

        $manifiesto = 'id:' . Str::lower($dataId) . ';';
        if (filled($request->header('x-request-id'))) {
            $manifiesto .= 'request-id:' . $request->header('x-request-id') . ';';
        }
        $manifiesto .= 'ts:' . $ts . ';';

        return hash_equals(hash_hmac('sha256', $manifiesto, $secreto), $v1);
    }
}
