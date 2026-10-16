<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\NotificationProcessed;

class ReservationController extends Controller
{
    public function store(Request $request)
    {

        $opening = \App\Models\Setting::where('key', 'opening_time')->value('value') ?? '13:00';
        $closing = \App\Models\Setting::where('key', 'closing_time')->value('value') ?? '22:30';

        $request->validate([
            'nombre_completo'    => 'required|string|max:255',
            'telefono'           => 'required|string',
            'correo_electronico' => 'required|email',
            'fecha_reservacion'  => 'required|date|after_or_equal:today',
            'hora_reservacion'   => [
                'required',
                function ($attribute, $value, $fail) use ($opening, $closing) {
                    unset($attribute);
                    
                    $time = strtotime($value);
                    $open = strtotime($opening);
                    $close = strtotime($closing);

                    if ($time < $open || $time > $close) {
                        $fail("Nuestro horario de atención es de {$opening} a {$closing}. Por favor, elige una hora válida.");
                    }
                },
            ],
            'cantidad_personas'  => 'required|integer|max:10',
            'zona'               => 'required|string',
        ]);

        $reserva = \App\Models\Reservation::create($request->all());

        event(new NotificationProcessed(
            "¡Nueva reservación de {$reserva->nombre_completo} para {$reserva->cantidad_personas} personas!",
            'success'
        ));

        return back()->with('success', 'Solicitud enviada. Espera nuestra confirmación.');
    }
}
