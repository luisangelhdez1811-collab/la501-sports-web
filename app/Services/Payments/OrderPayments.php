<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Único lugar donde un pedido con tarjeta pasa a pagado o a rechazado, y donde se dan los puntos.
 * Lo usan el webhook de Mercado Pago y la página de regreso del cliente, así que todo aquí puede
 * ejecutarse varias veces sin efectos dobles (idempotente).
 */
class OrderPayments
{
    public function __construct(private MercadoPago $mercadoPago)
    {
    }

    /**
     * Consulta el pago a la API de Mercado Pago y actualiza el pedido según lo que la API diga, nunca
     * según lo que traiga la URL o el aviso. Devuelve el pedido, o null si el pago no existe o no se
     * pudo consultar.
     *
     * Solo se marca pagado si el pago está aprobado Y su referencia, monto y moneda coinciden con el
     * pedido. $esperado, si se da, obliga a que el pago sea de ese pedido (página de regreso).
     */
    public function syncCardPayment(string|int $paymentId, ?Order $esperado = null): ?Order
    {
        $pago = $this->mercadoPago->getPayment($paymentId);
        if (!$pago || !isset($pago['id'])) {
            return null;
        }

        $referencia = $pago['external_reference'] ?? null;
        if (!is_scalar($referencia) || !ctype_digit((string) $referencia)) {
            Log::warning('Pago de Mercado Pago sin referencia de pedido válida.', ['payment_id' => $pago['id']]);

            return null;
        }

        if ($esperado && (int) $esperado->id !== (int) $referencia) {
            Log::warning('El pago de Mercado Pago no es del pedido esperado.', [
                'payment_id' => $pago['id'],
                'pedido_esperado' => $esperado->id,
                'pedido_del_pago' => $referencia,
            ]);

            return null;
        }

        $devolver = false;

        // Un solo proceso a la vez por pedido: el webhook y la página de regreso pueden llegar juntos.
        $order = DB::transaction(function () use ($pago, $referencia, &$devolver) {
            $order = Order::lockForUpdate()->find((int) $referencia);
            if (!$order || $order->payment_method !== 'tarjeta') {
                return $order;
            }

            $estado = $pago['status'] ?? '';

            if ($estado === 'approved') {
                if (!$this->coincide($order, $pago)) {
                    Log::warning('Pago aprobado que no coincide con el pedido: no se marca pagado.', [
                        'order_id' => $order->id,
                        'payment_id' => $pago['id'],
                        'monto_pago' => $pago['transaction_amount'] ?? null,
                        'monto_pedido' => (string) $order->total,
                        'moneda' => $pago['currency_id'] ?? null,
                    ]);

                    return $order;
                }

                if ($order->status === 'cancelled') {
                    // El cliente pagó tarde, cuando el pedido ya estaba cancelado: se le devuelve.
                    $order->payment_id = $order->payment_id ?: (string) $pago['id'];
                    $order->save();
                    $devolver = true;
                } elseif (in_array($order->status, ['pending', 'failed'], true)) {
                    $order->status = 'paid';
                    $order->payment_id = (string) $pago['id'];
                    $order->save();
                    $this->awardPoints($order);
                } elseif (!$order->payment_id) {
                    $order->payment_id = (string) $pago['id'];
                    $order->save();
                }
            } elseif (in_array($estado, ['rejected', 'cancelled'], true) && $order->status === 'pending') {
                $order->status = 'failed';
                $order->save();
            }

            return $order;
        });

        if ($order && $devolver) {
            $this->refundLatePayment($order, $pago);
        }

        return $order?->fresh();
    }

    /**
     * ÚNICO camino para cancelar un pedido (cliente, personal, panel o Alexa). Pone el pedido en cancelado
     * con su fecha y motivo, quita los puntos que había dado y, si el cliente ya pagó con tarjeta, le
     * devuelve el pago con la API de Mercado Pago.
     *
     * Si la devolución falla, el pedido igual queda cancelado con refund_status = 'manual' y un Log::critical
     * para que alguien la haga a mano (el cliente ve «máximo 5 días hábiles»). Se puede llamar varias veces:
     * un pedido ya cancelado no se vuelve a tocar y nunca se devuelve dos veces.
     */
    public function cancel(Order $order, ?string $motivo = null, bool $porCliente = false): Order
    {
        $motivo = trim((string) $motivo);
        $motivo = $motivo !== '' ? mb_substr($motivo, 0, 255) : ($porCliente ? 'Cancelado por el cliente' : 'Cancelado por el personal');

        $debeDevolver = false;

        $actual = DB::transaction(function () use ($order, $motivo, $porCliente, &$debeDevolver) {
            $actual = Order::lockForUpdate()->findOrFail($order->id);

            if ($actual->status === 'cancelled') {
                // Ya estaba cancelado: solo se reintenta la devolución si quedó pendiente (nunca dos veces).
                $debeDevolver = $this->pendienteDeDevolver($actual);

                return $actual;
            }

            // El cliente solo cancela mientras la cocina no empiece. Se vuelve a revisar aquí, con el pedido
            // bloqueado, por si la cocina lo tomó justo después de que el cliente abrió la pantalla.
            if ($porCliente && !$actual->puedeCancelarElCliente()) {
                return $actual;
            }

            $actual->status = 'cancelled';
            $actual->cancelled_at = now();
            $actual->cancel_reason = $motivo;
            $actual->save();

            $this->removePoints($actual);

            $debeDevolver = $this->pendienteDeDevolver($actual);

            return $actual;
        });

        if ($debeDevolver) {
            $this->refundCancelled($actual);
        }

        return $actual->fresh();
    }

    /** Tarjeta con un pago aprobado (payment_id solo se guarda al aprobarse) que aún no se devuelve. */
    private function pendienteDeDevolver(Order $order): bool
    {
        return $order->payment_method === 'tarjeta'
            && filled($order->payment_id)
            && $order->refund_status !== 'refunded';
    }

    private function refundCancelled(Order $order): void
    {
        $devolucion = $this->mercadoPago->refund($order->payment_id, 'pedido-' . $order->id . '-cancelacion-' . $order->payment_id);

        $order->forceFill([
            'refund_status' => $devolucion ? 'refunded' : 'manual',
            'refund_id' => $devolucion['id'] ?? null,
        ])->save();

        if (!$devolucion) {
            Log::critical('Hay que devolver a mano el pago de un pedido cancelado.', [
                'order_id' => $order->id,
                'payment_id' => $order->payment_id,
            ]);
        }
    }

    /** Resta los puntos que dio el pedido (sin dejar el saldo negativo) para que pueda premiarse de nuevo. */
    private function removePoints(Order $order): void
    {
        if (!$order->points_awarded_at || !$order->user_id) {
            return;
        }

        $usuario = User::lockForUpdate()->find($order->user_id);
        if ($usuario) {
            $usuario->points = max(0, (int) $usuario->points - (int) floor($order->total / 10));
            $usuario->save();
        }

        $order->points_awarded_at = null;
        $order->save();
    }

    /**
     * Da los puntos del pedido una sola vez en toda su vida (1 por cada $10). Devuelve true si los dio
     * en esta llamada. Sin cuenta (invitado) no hay puntos que dar.
     */
    public function awardPoints(Order $order): bool
    {
        $usuario = DB::transaction(function () use ($order) {
            $actual = Order::lockForUpdate()->find($order->id);
            if (!$actual || !$actual->user_id || $actual->points_awarded_at) {
                return null;
            }

            $usuario = User::lockForUpdate()->find($actual->user_id);
            if (!$usuario) {
                return null;
            }

            $usuario->points = (int) $usuario->points + (int) floor($actual->total / 10);
            $usuario->save();

            $actual->points_awarded_at = now();
            $actual->save();
            $order->points_awarded_at = $actual->points_awarded_at;

            return $usuario;
        });

        if (!$usuario) {
            return false;
        }

        (new AchievementService())->check($usuario);

        return true;
    }

    /** Referencia, monto (al centavo) y moneda del pago contra el pedido. */
    private function coincide(Order $order, array $pago): bool
    {
        $centavosPago = (int) round(((float) ($pago['transaction_amount'] ?? 0)) * 100);
        $centavosPedido = (int) round(((float) $order->total) * 100);

        return $centavosPago === $centavosPedido && ($pago['currency_id'] ?? null) === 'MXN';
    }

    private function refundLatePayment(Order $order, array $pago): void
    {
        if ($order->refund_status === 'refunded') {
            return;
        }

        $devolucion = $this->mercadoPago->refund($pago['id'], 'pedido-' . $order->id . '-pago-tardio-' . $pago['id']);

        $order->forceFill([
            'refund_status' => $devolucion ? 'refunded' : 'manual',
            'refund_id' => $devolucion['id'] ?? null,
        ])->save();

        if (!$devolucion) {
            Log::critical('Hay que devolver a mano un pago aprobado de un pedido cancelado.', [
                'order_id' => $order->id,
                'payment_id' => $pago['id'],
            ]);
        }
    }
}
