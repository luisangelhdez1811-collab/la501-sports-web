<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Una sola regla de contraseña para clientes y personal: de 8 a 64 caracteres, con mayúscula, minúscula,
 * número y símbolo. Antes cada formulario pedía algo distinto (el personal, solo 6 caracteres).
 */
class PasswordPolicy
{
    public static function rule(): Password
    {
        // Máximo 64: bcrypt solo usa los primeros 72 bytes y una contraseña enorme solo sirve para
        // hacer lento el servidor al calcular su hash.
        return Password::min(8)->max(64)->mixedCase()->numbers()->symbols();
    }

    /** @return array<string, string> */
    public static function messages(string $campo = 'password'): array
    {
        return [
            "{$campo}.min" => 'La contraseña debe tener al menos 8 caracteres.',
            "{$campo}.max" => 'La contraseña puede tener como máximo 64 caracteres.',
            "{$campo}.mixed" => 'La contraseña debe incluir mayúscula y minúscula.',
            "{$campo}.numbers" => 'La contraseña debe incluir al menos un número.',
            "{$campo}.symbols" => 'La contraseña debe incluir al menos un símbolo (por ejemplo ! @ # $).',
        ];
    }
}
