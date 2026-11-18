<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B-32 (Código de Comercio art. 89 y ss., LFPDPPP): qué versión del Aviso de Privacidad y de los Términos
 * aceptó cada cuenta y cada pedido, y qué versión del aviso para personal aceptó cada empleado (B-39). Las
 * cuentas que ya existían no tienen versión: al iniciar sesión se les pide aceptar la vigente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('privacy_version_accepted', 20)->nullable();
            $table->string('terms_version_accepted', 20)->nullable();
            $table->string('staff_privacy_version_accepted', 20)->nullable();
            $table->timestamp('legal_accepted_at')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('privacy_version_accepted', 20)->nullable();
            $table->string('terms_version_accepted', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['privacy_version_accepted', 'terms_version_accepted']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['privacy_version_accepted', 'terms_version_accepted', 'staff_privacy_version_accepted', 'legal_accepted_at']);
        });
    }
};
