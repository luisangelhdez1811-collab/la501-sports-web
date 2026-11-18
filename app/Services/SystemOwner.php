<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * El dueño del sistema: la única cuenta que maneja al personal, los respaldos y la restauración de la
 * base, y la única que nadie puede pausar ni eliminar. Siempre hay exactamente uno.
 *
 * Se cambia de dos formas:
 *  - En el panel, el dueño actual pasa la propiedad a otro administrador (Personal → «Hacer dueño»).
 *  - En el servidor, con «php artisan owner:transfer correo@ejemplo.com», por si nadie puede entrar.
 */
class SystemOwner
{
    public static function current(): ?User
    {
        return User::where('is_owner', true)->first();
    }

    /**
     * Pasa la propiedad a $nuevo. La cuenta anterior sigue siendo administrador, pero ya no es dueña:
     * desde ese momento el nuevo dueño la puede pausar o eliminar.
     */
    public static function transfer(User $nuevo, ?User $por = null, string $via = 'panel'): void
    {
        if ($nuevo->role !== 'admin') {
            throw new InvalidArgumentException('El nuevo dueño debe tener el puesto de administrador.');
        }
        if (!$nuevo->isActive()) {
            throw new InvalidArgumentException('La cuenta del nuevo dueño está pausada. Reactívala primero.');
        }

        $anterior = self::current();

        DB::transaction(function () use ($nuevo) {
            // Bloquea las cuentas involucradas para que dos cambios al mismo tiempo no dejen dos dueños
            User::where('is_owner', true)->orWhere('id', $nuevo->id)->lockForUpdate()->get();

            User::where('is_owner', true)->where('id', '!=', $nuevo->id)->update(['is_owner' => false]);
            User::whereKey($nuevo->id)->update(['is_owner' => true]);
        });

        $nuevo->is_owner = true;

        Log::channel('single')->warning('Cambio de dueño del sistema', [
            'via' => $via,
            'por_usuario_id' => $por?->id,
            'dueno_anterior_id' => $anterior?->id,
            'dueno_nuevo_id' => $nuevo->id,
        ]);
    }
}
