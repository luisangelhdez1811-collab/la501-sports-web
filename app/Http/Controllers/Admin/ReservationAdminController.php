<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Events\NotificationProcessed;
use Carbon\Carbon;

class ReservationAdminController extends Controller
{
    public function index(Request $request)
    {
        // OPCIONAL: Auto-finalizar reservas de días pasados
        Reservation::whereIn('status', ['pendiente', 'confirmada'])
            ->whereDate('fecha_reservacion', '<', Carbon::today())
            ->update(['status' => 'finalizada']);

        // 1. Obtenemos el status de la URL, por defecto 'pendiente'
        $status = $request->get('status', 'pendiente');

        // 2. Consultamos las reservaciones filtradas por ese status
        $reservations = Reservation::where('status', $status)
                        ->orderBy('fecha_reservacion', 'asc')
                        ->orderBy('hora_reservacion', 'asc')
                        ->paginate(10)
                        ->withQueryString();

        // 3. CRÍTICO: Pasamos AMBAS variables a la vista
        return view('admin.reservaciones', compact('reservations', 'status'));
    }
    
    public function updateStatus(Reservation $reservation, $status)
    {
        $validStatuses = ['pendiente', 'confirmada', 'cancelada', 'finalizada'];

        if (in_array($status, $validStatuses)) {
            $reservation->update(['status' => $status]);

            if ($status === 'confirmada') {
                $user = \App\Models\User::where('email', $reservation->correo_electronico)->first();
                if ($user) {
                    (new \App\Services\AchievementService())->check($user);
                }
            }
            
            // 2. DISPARAR EL EVENTO AQUÍ
            // Esto es lo que activa el Pusher/Reverb y hace que salte el SweetAlert2
            event(new NotificationProcessed(
                "La reservación de {$reservation->nombre} ha sido marcada como: " . ucfirst($status),
                $status === 'confirmada' ? 'success' : 'info'
            ));

            $msg = [
                'confirmada' => 'Reserva confirmada con éxito.',
                'cancelada'  => 'La reserva ha sido cancelada.',
                'finalizada' => 'Reserva marcada como finalizada.',
            ];

            return back()->with('success', $msg[$status] ?? 'Estado actualizado.');
        }

        return back()->with('error', 'El estado seleccionado no es válido.');
    }
}
