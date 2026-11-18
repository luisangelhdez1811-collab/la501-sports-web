<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Evidencia de los consentimientos que pide la web:
 * - Contacto y reservaciones: cuándo aceptó el Aviso de Privacidad y qué versión.
 * - Pedidos: cuándo aceptó los Términos de compra (precios, envío, cancelación y devoluciones).
 * - Boletín: doble confirmación; solo recibe promociones quien abrió el enlace del correo.
 *   Los suscriptores que ya existían quedan confirmados (se inscribieron con las reglas de antes).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['contact_messages', 'reservations'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->timestamp('privacy_accepted_at')->nullable();
                $table->string('privacy_version', 20)->nullable();
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('purchase_terms_accepted_at')->nullable();
        });

        Schema::table('promo_subscribers', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable();
            $table->string('confirm_token', 64)->nullable()->unique();
        });

        DB::table('promo_subscribers')->whereNull('confirmed_at')->update(['confirmed_at' => DB::raw('COALESCE(created_at, NOW())')]);
    }

    public function down(): void
    {
        foreach (['contact_messages', 'reservations'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn(['privacy_accepted_at', 'privacy_version']);
            });
        }

        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('purchase_terms_accepted_at'));

        Schema::table('promo_subscribers', function (Blueprint $table) {
            $table->dropUnique(['confirm_token']);
            $table->dropColumn(['confirmed_at', 'confirm_token']);
        });
    }
};
