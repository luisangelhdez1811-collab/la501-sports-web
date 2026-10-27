<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'aviso_privacidad_aceptado_at')) {
                // Evidencia del consentimiento (derechos ARCO): cuándo aceptó
                // el aviso de privacidad al registrarse.
                $table->timestamp('aviso_privacidad_aceptado_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('aviso_privacidad_aceptado_at');
        });
    }
};
