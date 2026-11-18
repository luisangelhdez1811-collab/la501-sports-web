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

        $request->merge(['telefono' => \App\Support\Phone::normalize($request->input('telefono'))]);

        $datos = $request->validate([
            'nombre_completo'    => 'required|string|max:120',
            'telefono'           => 'required|digits:10',
            'correo_electronico' => 'required|email|max:255',
            'fecha_reservacion'  => 'required|date|after_or_equal:today|before_or_equal:' . now()->addMonths(3)->toDateString(),
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
            'cantidad_personas'  => 'required|integer|min:1|max:10',
            'zona'               => 'required|in:General,Terraza',
            // Consentimiento expreso para tratar sus datos (LFPDPPP); casilla sin premarcar
            'acepta_privacidad'  => 'accepted',
        ], [
            'acepta_privacidad.accepted' => 'Para reservar, acepta el Aviso de Privacidad.',
            'telefono.digits' => 'El teléfono debe tener 10 dígitos.',
            'fecha_reservacion.before_or_equal' => 'Solo se puede reservar con hasta 3 meses de anticipación.',
        ]);

        // Solo los campos validados y siempre «pendiente»: antes se guardaba todo lo que llegara
        // ($request->all()), y alguien podía mandar su reservación ya «confirmada».
        unset($datos['acepta_privacidad']);
        $reserva = \App\Models\Reservation::create($datos + [
            'status' => 'pendiente',
            'privacy_accepted_at' => now(),
            'privacy_version' => \App\Services\LegalDocuments::version(\App\Services\LegalDocuments::PRIVACY),
        ]);

        event(new NotificationProcessed(
            "¡Nueva reservación de {$reserva->nombre_completo} para {$reserva->cantidad_personas} personas!",
            'success'
        ));

        return back()->with('success', 'Solicitud enviada. Espera nuestra confirmación.');
    }
}
