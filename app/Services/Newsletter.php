<?php

namespace App\Services;

use App\Models\PromoSubscriber;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/** Inscripción al boletín desde la web y la app, con doble confirmación para quien no tiene sesión. */
class Newsletter
{
    public const CONFIRMED = 'confirmed';

    public const PENDING = 'pending';

    /**
     * Si la persona tiene sesión y es su propio correo, queda confirmada. Si no, se le manda un correo
     * con el enlace para confirmar. La respuesta no revela si el correo ya estaba inscrito.
     */
    public static function subscribe(string $email, ?User $user = null): string
    {
        $email = mb_strtolower(trim($email));
        $suscriptor = PromoSubscriber::firstOrNew(['email' => $email]);

        if ($user && mb_strtolower($user->email) === $email) {
            $suscriptor->user_id = $user->id;
            $suscriptor->confirmed_at = $suscriptor->confirmed_at ?? now();
            $suscriptor->confirm_token = null;
            $suscriptor->save();

            return self::CONFIRMED;
        }

        if ($suscriptor->exists && $suscriptor->isConfirmed()) {
            return self::PENDING; // ya estaba: misma respuesta para no revelar quién está inscrito
        }

        $suscriptor->confirm_token = Str::random(48);
        $suscriptor->save();

        try {
            Mail::send('emails.newsletter_confirm', ['token' => $suscriptor->confirm_token], function ($message) use ($email) {
                $message->to($email)->subject('Confirma tu suscripción a las promociones de La 501 Sports');
            });
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar la confirmación del boletín: ' . $e->getMessage());
        }

        return self::PENDING;
    }

    public static function confirm(string $token): bool
    {
        $suscriptor = PromoSubscriber::where('confirm_token', $token)->first();
        if (!$suscriptor) {
            return false;
        }

        $suscriptor->forceFill(['confirmed_at' => now(), 'confirm_token' => null])->save();

        return true;
    }

    public static function message(string $estado): string
    {
        return $estado === self::CONFIRMED
            ? 'Listo. Te avisaremos por correo cuando haya nuevas promociones.'
            : 'Te enviamos un correo. Abre el enlace para confirmar tu suscripción a las promociones.';
    }
}
