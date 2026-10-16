<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderNotificationService
{
    public function notificarEstado(Order $order, string $stage): void
    {
        $email = $order->correoNotificacion();
        if (!$email) {
            return;
        }

        $nombreDestinatario = $order->user_id && $order->user
            ? $order->user->name
            : $order->customer_name;

        $asunto = $stage === 'delivering'
            ? '¡Tu pedido va en camino! 🛵 - La 501 Sports'
            : '¡Tu pedido está listo! 🍳 - La 501 Sports';

        try {
            Mail::send('emails.order_status', [
                'order' => $order,
                'stage' => $stage,
                'nombreDestinatario' => $nombreDestinatario,
            ], function ($message) use ($email, $asunto) {
                $message->to($email)->subject($asunto);
            });
        } catch (\Exception $e) {
            Log::warning('No se pudo enviar el correo de estado de pedido #' . $order->id . ': ' . $e->getMessage());
        }
    }
}
