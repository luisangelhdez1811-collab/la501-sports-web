<?php

namespace App\Http\Controllers\Api\Customer;

use App\Events\NotificationProcessed;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $opening = Setting::where('key', 'opening_time')->value('value') ?? '13:00';
        $closing = Setting::where('key', 'closing_time')->value('value') ?? '22:30';

        $validated = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string'],
            'correo_electronico' => ['required', 'email'],
            'fecha_reservacion' => ['required', 'date', 'after_or_equal:today'],
            'hora_reservacion' => [
                'required',
                function ($attribute, $value, $fail) use ($opening, $closing) {
                    unset($attribute);
                    $time = strtotime($value);
                    if ($time < strtotime($opening) || $time > strtotime($closing)) {
                        $fail("Nuestro horario de atención es de {$opening} a {$closing}. Por favor, elige una hora válida.");
                    }
                },
            ],
            'cantidad_personas' => ['required', 'integer', 'max:10'],
            'zona' => ['required', 'string'],
        ]);

        $reserva = Reservation::create($validated);

        event(new NotificationProcessed(
            "¡Nueva reservación de {$reserva->nombre_completo} para {$reserva->cantidad_personas} personas!",
            'success'
        ));

        return response()->json([
            'success' => true,
            'message' => 'Solicitud enviada. Espera nuestra confirmación.',
            'data' => $reserva,
        ], 201, [], JSON_UNESCAPED_UNICODE);
    }
}
