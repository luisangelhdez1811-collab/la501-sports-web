<?php

namespace App\Services;

use Twilio\Rest\Client;

class PhoneVerification
{
    private ?Client $client = null;

    /** 7711234567 -> +527711234567 */
    public static function e164(string $telefono): string
    {
        return '+52' . substr(preg_replace('/\D/', '', $telefono), -10);
    }

    /** ******4567 — para bitácoras, nunca el número completo. */
    public static function mask(string $telefono): string
    {
        $digits = preg_replace('/\D/', '', $telefono);
        $ultimos = substr($digits, -4);

        return str_repeat('*', max(0, strlen($digits) - 4)) . $ultimos;
    }

    private function client(): Client
    {
        // Casteamos a string explícito: si la config llegara nula (ej. caché
        // de config desactualizada tras agregar las llaves), evita un
        // TypeError y en vez de eso deja que Twilio responda con su propio
        // error de autenticación — que sí es un TwilioException capturable.
        return $this->client ??= new Client(
            (string) config('services.twilio.sid'),
            (string) config('services.twilio.token'),
        );
    }

    private function serviceSid(): string
    {
        return (string) config('services.twilio.verify_sid');
    }

    /**
     * $canal: 'sms' o 'llamada'. Lanza una excepción si no se pudo enviar
     * (sin saldo, token inválido, número fuera de la geo-permission,
     * configuración incompleta, etc.) — el controlador la atrapa con
     * catch (\Throwable) y responde 503.
     */
    public function send(string $telefono, string $canal = 'sms'): void
    {
        $this->client()->verify->v2->services($this->serviceSid())->verifications->create(
            self::e164($telefono),
            $canal === 'llamada' ? 'call' : 'sms',
            ['locale' => 'es'],
        );
    }

    /** true solo si el código es correcto y sigue vigente. */
    public function check(string $telefono, string $codigo): bool
    {
        try {
            $result = $this->client()->verify->v2->services($this->serviceSid())
                ->verificationChecks->create(['to' => self::e164($telefono), 'code' => trim($codigo)]);

            return $result->status === 'approved';
        } catch (\Throwable $e) {
            return false; // caducado, ya usado, demasiados intentos, o error de configuración
        }
    }
}
