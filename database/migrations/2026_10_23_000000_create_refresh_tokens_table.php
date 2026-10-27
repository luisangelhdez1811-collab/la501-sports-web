<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refresh tokens de la app móvil (sesiones JWT). Solo se guarda el SHA-256 del token:
 * quien lea la base no puede usarlos. "family" agrupa las rotaciones de una misma
 * sesión para cerrarla completa si se detecta reuso.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('refresh_tokens')) {
            return;
        }

        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->uuid('family')->index();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('replaced_by_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
