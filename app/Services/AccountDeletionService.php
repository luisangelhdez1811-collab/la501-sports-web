<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\PromoSubscriber;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Derecho de Cancelación (LFPDPPP) y política de Google Play: la cuenta deja de existir para quien
 * la usaba y no queda ningún dato que identifique a la persona. Lo que la ley fiscal obliga a
 * conservar (productos, montos y fechas de los pedidos, 5 años) se queda, pero sin datos personales.
 *
 * La fila de users se anonimiza en lugar de borrarse porque los pedidos conservados la referencian.
 */
class AccountDeletionService
{
    private const CORREO_ANONIMO = 'eliminado@la501.invalid';

    public function delete(User $user): void
    {
        $correoOriginal = $user->email;
        $avatar = $user->avatar;

        // Todo o nada: si algo falla, la cuenta queda como estaba y se puede reintentar.
        DB::transaction(function () use ($user, $correoOriginal) {
            // Pedidos: sin nombre, teléfono, correo, dirección ni ubicación. customer_phone no acepta NULL.
            $pedidos = Order::where('user_id', $user->id);
            $idsPedidos = (clone $pedidos)->pluck('id');

            $pedidos->update([
                'customer_name' => 'Cliente eliminado',
                'customer_phone' => '',
                'customer_address' => null,
                'customer_email' => null,
                'delivery_lat' => null,
                'delivery_lng' => null,
            ]);

            // Las notas pueden traer alergias u otros datos de salud.
            if ($idsPedidos->isNotEmpty()) {
                DB::table('order_items')->whereIn('order_id', $idsPedidos)->update(['notes' => null]);
            }

            // Reservaciones y mensajes de contacto enviados con el mismo correo.
            Reservation::where('correo_electronico', $correoOriginal)->update([
                'nombre_completo' => 'Cliente eliminado',
                'telefono' => '',
                'correo_electronico' => self::CORREO_ANONIMO,
            ]);

            ContactMessage::where('email', $correoOriginal)->update([
                'name' => 'Cliente eliminado',
                'email' => self::CORREO_ANONIMO,
                'subject' => 'Mensaje eliminado',
                'message' => 'Mensaje eliminado a solicitud del titular.',
            ]);

            PromoSubscriber::where('user_id', $user->id)->orWhere('email', $correoOriginal)->delete();

            // Puntos y logros se pierden con la cuenta.
            DB::table('user_achievements')->where('user_id', $user->id)->delete();

            // Todas las sesiones: web (tabla sessions), Sanctum y refresh tokens JWT de la app.
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            $user->tokens()->delete();
            app(JwtService::class)->revokeAll($user);

            $user->forceFill([
                'name' => 'Usuario eliminado',
                'email' => 'eliminado_' . $user->id . '_' . time() . '@la501.invalid',
                'telefono' => null,
                'pregunta_secreta' => null,
                'respuesta_secreta' => null,
                'avatar' => null,
                'direccion_favorita' => null,
                'points' => 0,
                'pin' => null,
                'remember_token' => null,
                'recomendaciones_personalizadas' => false,
                'promos_sms' => false,
                'preferencias_privacidad_at' => null,
                'aviso_privacidad_aceptado_at' => null,
                'password' => Hash::make(Str::random(40)),
                'is_active' => 0,
            ])->save();
        });

        // El archivo se borra al final: si la transacción falla, la foto no se pierde.
        if ($avatar) {
            $ruta = public_path($avatar);
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }
    }
}
