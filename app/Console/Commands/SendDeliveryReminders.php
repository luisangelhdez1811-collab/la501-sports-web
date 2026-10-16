<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDeliveryReminders extends Command
{
    protected $signature = 'orders:send-delivery-reminders';
    protected $description = 'Recuerda por correo a los clientes con un pedido en camino que confirmen si ya lo recibieron';

    public function handle(): int
    {
        // Desactivado: el cliente ya no confirma "recibido" desde su ticket, así que
        // el recordatorio por correo no tiene sentido. Para reactivarlo, quita este return.
        $this->info('Recordatorios de entrega desactivados.');

        return self::SUCCESS;

        $enviados = 0;

        $pedidos = Order::whereNull('table_number')
            ->where('status', 'delivering')
            ->whereNotNull('delivery_started_at')
            ->whereNull('delivered_confirmed_at')
            ->where('delivery_started_at', '<=', now()->subMinutes(30))
            ->where(function ($query) {
                $query->whereNull('last_reminder_sent_at')
                      ->orWhere('last_reminder_sent_at', '<=', now()->subMinutes(5));
            })
            ->get();

        foreach ($pedidos as $order) {
            $email = $order->correoNotificacion();
            if (!$email) {
                continue;
            }

            $nombreDestinatario = $order->user_id && $order->user
                ? $order->user->name
                : $order->customer_name;

            try {
                Mail::send('emails.delivery_reminder', [
                    'order' => $order,
                    'nombreDestinatario' => $nombreDestinatario,
                ], function ($message) use ($email) {
                    $message->to($email)->subject('¿Ya te llegó tu pedido? - La 501 Sports');
                });

                $order->last_reminder_sent_at = now();
                $order->save();
                $enviados++;
            } catch (\Exception $e) {
                Log::warning('No se pudo enviar el recordatorio de entrega del pedido #' . $order->id . ': ' . $e->getMessage());
            }
        }

        $this->info("Recordatorios enviados: {$enviados}");

        return self::SUCCESS;
    }
}
