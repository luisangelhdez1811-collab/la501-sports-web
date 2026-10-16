<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MenuUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct()
    {
        // No necesitamos pasar datos, solo avisar que hubo un cambio
    }

    // Este es el "Canal" de radio por el que transmitimos
    public function broadcastOn(): array
    {
        return [
            new Channel('menu-channel'),
        ];
    }

    // Este es el nombre exacto del "Grito" que escucha el celular del cliente
    public function broadcastAs(): string
    {
        return 'menu.updated';
    }
}
