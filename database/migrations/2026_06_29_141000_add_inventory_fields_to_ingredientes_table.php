<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ingredientes', function (Blueprint $table) {
            $table->foreignId('inventory_id')->nullable()->after('product_id')->constrained('inventories')->nullOnDelete();
            $table->decimal('cantidad_usada', 8, 2)->default(1.00)->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_id');
            $table->dropColumn('cantidad_usada');
        });
    }
};
