<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * El código de 8 dígitos para restablecer la contraseña por correo. Una sola clase para la web y la app:
 * se guarda solo su hash, dura 10 minutos y aguanta 5 intentos fallidos; al quinto se borra y hay que
 * pedir otro (sin ese tope se podía intentar sin límite mientras el código siguiera vigente).
 */
class PasswordResetCodes
{
    public const MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /** Genera y manda el código. Si el correo no existe no hace nada (no se revela quién tiene cuenta). */
    public static function send(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return;
        }

        $codigo = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($codigo), 'created_at' => Carbon::now()]
        );
        RateLimiter::clear(self::attemptsKey($user->email));

        // El código va en el asunto: Gmail y el iPhone lo detectan y ofrecen «Copiar código»
        Mail::send('emails.otp', ['codigo' => $codigo], function ($message) use ($user, $codigo) {
            $message->to($user->email)->subject($codigo . ' es tu código de La 501 Sports');
        });
    }

    /** ¿El código es el vigente para ese correo? Cuenta los intentos fallidos. */
    public static function check(string $email, string $codigo): bool
    {
        $clave = self::attemptsKey($email);

        if (RateLimiter::tooManyAttempts($clave, self::MAX_ATTEMPTS)) {
            return false;
        }

        $registro = DB::table('password_reset_tokens')->where('email', $email)->first();

        $valido = $registro
            && Carbon::parse($registro->created_at)->addMinutes(self::MINUTES)->isFuture()
            && Hash::check($codigo, $registro->token);

        if ($valido) {
            return true;
        }

        RateLimiter::hit($clave, self::MINUTES * 60);

        if ($registro && RateLimiter::tooManyAttempts($clave, self::MAX_ATTEMPTS)) {
            // Demasiados intentos: el código deja de servir aunque luego atinen.
            self::forget($email);
            Log::warning('Código de recuperación bloqueado por intentos fallidos.', ['email_hash' => hash('sha256', mb_strtolower($email))]);
        }

        return false;
    }

    public static function forget(string $email): void
    {
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    private static function attemptsKey(string $email): string
    {
        return 'reset-code-attempts:' . hash('sha256', mb_strtolower(trim($email)));
    }
}
