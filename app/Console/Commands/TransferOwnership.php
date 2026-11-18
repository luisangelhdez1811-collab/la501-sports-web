<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SystemOwner;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Cambia al dueño del sistema desde el servidor (Hostinger, por SSH). Sirve cuando nadie puede entrar al
 * panel como dueño: se olvidó la contraseña, la cuenta se perdió o se restauró una base sin dueño.
 * Solo lo puede correr quien tiene acceso al servidor.
 */
class TransferOwnership extends Command
{
    protected $signature = 'owner:transfer {email : Correo de la cuenta que será la nueva dueña}
                            {--make-admin : Si la cuenta no es administrador, la convierte en administrador}
                            {--force : No pedir confirmación}';

    protected $description = 'Hace dueño del sistema a otra cuenta (personal, respaldos y restauración de la base)';

    public function handle(): int
    {
        $user = User::where('email', mb_strtolower(trim($this->argument('email'))))->first();

        if (!$user) {
            $this->error('No existe una cuenta con ese correo. Créala primero (Personal → Nuevo empleado, como Administrador).');

            return self::FAILURE;
        }

        if ($user->is_owner) {
            $this->info("{$user->name} ya es el dueño del sistema.");

            return self::SUCCESS;
        }

        $anterior = SystemOwner::current();
        $this->line('Dueño actual: ' . ($anterior ? "{$anterior->name} <{$anterior->email}>" : 'ninguno'));
        $this->line("Nuevo dueño:  {$user->name} <{$user->email}> (puesto: {$user->role})");

        if ($user->role !== 'admin' && !$this->option('make-admin')) {
            $this->error('La cuenta no es administrador. Vuelve a correr el comando con --make-admin si de verdad debe serlo.');

            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('¿Pasar la propiedad del sistema a esta cuenta?')) {
            $this->line('Cancelado. No se cambió nada.');

            return self::SUCCESS;
        }

        if ($user->role !== 'admin') {
            $user->forceFill(['role' => 'admin'])->save();
        }

        try {
            SystemOwner::transfer($user, null, 'consola');
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Listo. {$user->name} es ahora el dueño del sistema.");

        return self::SUCCESS;
    }
}
