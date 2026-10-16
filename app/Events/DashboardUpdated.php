<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct()
    {
        // Constructor vacío
    }

    public function broadcastOn(): array
    {
        // Este es el canal que el dashboard está escuchando
        return [
            new Channel('dashboard-channel'),
        ];
    }

    public function broadcastAs(): string
    {
        // Este es el nombre del evento que el dashboard espera
        return 'dashboard.updated';
    }
}
