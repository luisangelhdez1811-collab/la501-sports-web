<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El dueño del sistema (personal, respaldos y restaurar la base) se reconocía por «el usuario con id 2»,
 * escrito en 26 lugares del código. Ahora es una marca en su cuenta. Se conserva quien ya lo era: el
 * usuario 2 si es administrador; si no existe, el primer administrador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_owner')->default(false)->after('role');
        });

        $duenio = DB::table('users')->where('id', 2)->where('role', 'admin')->value('id')
            ?? DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');

        if ($duenio) {
            DB::table('users')->where('id', $duenio)->update(['is_owner' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_owner');
        });
    }
};
