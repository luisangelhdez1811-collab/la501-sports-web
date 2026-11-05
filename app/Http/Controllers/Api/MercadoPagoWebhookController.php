<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payments\MercadoPago;
use App\Services\Payments\OrderPayments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Aviso de pago de Mercado Pago (webhook). El aviso solo dice «revisa este pago»: se valida su
 * firma y se consulta el pago a la API antes de tocar el pedido. Se responde 200 rápido y la
 * operación es idempotente, porque Mercado Pago reintenta si recibe un error.
 */
class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, MercadoPago $mercadoPago, OrderPayments $pagos): JsonResponse
    {
        // El id llega en el cuerpo (data.id) o en la URL; PHP cambia «data.id» por «data_id» en la URL.
        $idPago = (string) ($request->input('data.id') ?? $request->query('data_id') ?? $request->query('id') ?? '');

        if (!$mercadoPago->validWebhookSignature($request, $idPago)) {
            return response()->json(['ok' => false], 401);
        }

        $tipo = $request->input('type') ?? $request->query('type') ?? $request->query('topic');

        // Solo interesan los pagos; lo demás se confirma sin hacer nada.
        if ($tipo === 'payment' && $idPago !== '') {
            $pagos->syncCardPayment($idPago);
        }

        return response()->json(['ok' => true]);
    }
}
