<?php

namespace App\Support;

/** Teléfonos de México: se guardan como 10 dígitos, sin espacios, guiones ni el +52. */
class Phone
{
    public static function normalize(mixed $valor): mixed
    {
        if (!is_string($valor)) {
            return $valor;
        }

        $digitos = preg_replace('/\D+/', '', $valor);

        // +52 771 123 4567 y el formato viejo de celular +52 1 771 123 4567
        if (strlen($digitos) === 12 && str_starts_with($digitos, '52')) {
            return substr($digitos, 2);
        }
        if (strlen($digitos) === 13 && str_starts_with($digitos, '521')) {
            return substr($digitos, 3);
        }

        // Si no quedan 10 dígitos se devuelve tal cual para que la validación diga qué está mal
        return strlen($digitos) === 10 ? $digitos : $valor;
    }
}
