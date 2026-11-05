<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pagos y puntos (cumplimiento B-01, B-02 y B-10):
 * - points_awarded_at: un pedido da puntos como máximo una vez en toda su vida.
 * - cancelled_at, cancel_reason, refund_status y refund_id: toda cancelación queda registrada y, si
 *   había un pago con tarjeta aprobado, también su devolución (refunded) o que hay que hacerla a mano (manual).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'points_awarded_at')) {
                $table->timestamp('points_awarded_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'cancel_reason')) {
                $table->string('cancel_reason', 255)->nullable();
            }
            if (!Schema::hasColumn('orders', 'refund_status')) {
                $table->string('refund_status', 20)->nullable();
            }
            if (!Schema::hasColumn('orders', 'refund_id')) {
                $table->string('refund_id', 64)->nullable();
            }
        });

        // Los pedidos que ya dieron puntos no los vuelven a dar.
        DB::table('orders')
            ->whereNotNull('user_id')
            ->whereNull('points_awarded_at')
            ->whereIn('status', ['paid', 'preparing', 'ready', 'delivering', 'delivered'])
            ->update(['points_awarded_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['points_awarded_at', 'cancelled_at', 'cancel_reason', 'refund_status', 'refund_id'] as $columna) {
                if (Schema::hasColumn('orders', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
