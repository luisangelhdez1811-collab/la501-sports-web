<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LegalDocuments;
use App\Services\SystemOwner;
use App\Support\PasswordPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Crea la cuenta del dueño del sistema en una base nueva (recién instalada o reiniciada). En la web nadie
 * puede volverse administrador al registrarse: el registro siempre crea clientes, así que el primer
 * administrador se crea aquí, desde el servidor. Si ya hay dueño, no hace nada (para eso está
 * owner:transfer).
 */
class CreateOwner extends Command
{
    protected $signature = 'owner:create {email : Correo del dueño} {--name= : Nombre completo}';

    protected $description = 'Crea la cuenta del dueño del sistema en una base nueva (solo si todavía no hay dueño)';

    public function handle(): int
    {
        if ($actual = SystemOwner::current()) {
            $this->error("Ya hay un dueño del sistema: {$actual->name} <{$actual->email}>.");
            $this->line('Para cambiarlo usa: php artisan owner:transfer <correo>');

            return self::FAILURE;
        }

        $email = mb_strtolower(trim($this->argument('email')));
        $nombre = $this->option('name') ?: $this->ask('Nombre completo del dueño');
        $password = $this->secret('Contraseña (8 o más, con mayúscula, minúscula, número y símbolo)');
        $confirmacion = $this->secret('Escribe la contraseña otra vez');

        $validador = Validator::make(
            ['email' => $email, 'name' => $nombre, 'password' => $password, 'password_confirmation' => $confirmacion],
            [
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'name' => ['required', 'string', 'max:120'],
                'password' => ['required', 'confirmed', PasswordPolicy::rule()],
            ],
            PasswordPolicy::messages() + [
                'email.unique' => 'Ya existe una cuenta con ese correo. Hazla dueña con: php artisan owner:transfer ' . $email . ' --make-admin',
                'password.confirmed' => 'Las dos contraseñas no coinciden.',
            ]
        );

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $duenio = User::create([
            'name' => $nombre,
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
            'is_active' => true,
            'points' => 0,
        ]);

        // Al entrar al panel acepta el aviso para personal; aquí solo queda lista la cuenta
        LegalDocuments::accept($duenio, [LegalDocuments::PRIVACY, LegalDocuments::TERMS]);
        SystemOwner::transfer($duenio, null, 'consola (instalación nueva)');

        $this->info("Listo. {$nombre} <{$email}> es el dueño del sistema. Ya puede entrar en /login.");

        return self::SUCCESS;
    }
}
