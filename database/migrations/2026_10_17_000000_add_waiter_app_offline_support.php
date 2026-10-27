<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soporte para la app del mesero:
 * - Cada envío a cocina puede llevar el comensal ("Persona 1", "Ana") para cobrar por separado.
 * - client_ref / charge_ref: identificadores que genera la app, para que un pedido o cobro
 *   guardado sin conexión y reenviado varias veces se registre una sola vez.
 * - table_charges: bitácora de cada cobro (en línea o sin conexión) con sus pagos, para que
 *   el administrador pueda auditar cobros hechos sin internet o con diferencias.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_charges', function (Blueprint $table) {
            $table->id();
            $table->string('charge_ref', 64)->unique();
            $table->unsignedInteger('table_number');
            $table->foreignId('waiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total_due', 10, 2)->default(0);
            $table->decimal('amount_received', 10, 2)->default(0);
            $table->json('payments');
            $table->boolean('offline')->default(false);
            $table->boolean('forced')->default(false);
            $table->text('reason')->nullable();
            // applied | discrepancy (pagado menos de lo debido) | conflict (no había nada que cobrar)
            $table->string('status', 20)->default('applied');
            $table->text('notes')->nullable();
            $table->timestamp('charged_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'charged_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('client_ref', 64)->nullable()->unique()->after('tracking_token');
            $table->string('diner_label', 40)->nullable()->after('table_number');
            $table->foreignId('table_charge_id')->nullable()->after('charged_by')
                ->constrained('table_charges')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('table_charge_id');
            $table->dropUnique(['client_ref']);
            $table->dropColumn(['client_ref', 'diner_label']);
        });

        Schema::dropIfExists('table_charges');
    }
};
