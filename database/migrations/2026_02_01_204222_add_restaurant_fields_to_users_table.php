<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Datos extra para el restaurante
            if (!Schema::hasColumn('users', 'telefono')) {
                $table->string('telefono')->nullable();
            }
            if (!Schema::hasColumn('users', 'pregunta_secreta')) {
                $table->string('pregunta_secreta')->nullable(); // Criterio: Pregunta secreta segura [cite: 3]
            }
            if (!Schema::hasColumn('users', 'respuesta_secreta')) {
                $table->string('respuesta_secreta')->nullable(); // Se guardará con Hash [cite: 3]
            }
            if (!Schema::hasColumn('users', 'direccion_favorita')) {
                $table->string('direccion_favorita')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
