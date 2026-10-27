<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Derecho de Oposición (ARCO): finalidades secundarias que el cliente puede apagar.
 * - recomendaciones_personalizadas: usar su historial de pedidos para sugerirle platillos
 *   (encendido por defecto, se puede oponer).
 * - promos_sms: recibir promociones por SMS/WhatsApp (apagado hasta que lo autorice).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'recomendaciones_personalizadas')) {
                $table->boolean('recomendaciones_personalizadas')->default(true);
            }
            if (!Schema::hasColumn('users', 'promos_sms')) {
                $table->boolean('promos_sms')->default(false);
            }
            if (!Schema::hasColumn('users', 'preferencias_privacidad_at')) {
                // Evidencia de cuándo cambió por última vez sus preferencias.
                $table->timestamp('preferencias_privacidad_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['recomendaciones_personalizadas', 'promos_sms', 'preferencias_privacidad_at']);
        });
    }
};
