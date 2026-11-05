<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * B-26: el logro «fan_bar» premiaba pedir 5 bebidas alcohólicas. Premiar el volumen de alcohol es incitar a
 * consumirlo (LGS art. 308; política de Google Play), así que el logro se elimina y se quita a quien ya lo
 * tenía.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('achievements')) {
            return;
        }

        $ids = DB::table('achievements')->where('slug', 'fan_bar')->pluck('id');

        if ($ids->isNotEmpty()) {
            if (Schema::hasTable('user_achievements')) {
                DB::table('user_achievements')->whereIn('achievement_id', $ids)->delete();
            }
            DB::table('achievements')->whereIn('id', $ids)->delete();
        }
    }

    public function down(): void
    {
        // El logro eliminado no se restaura.
    }
};
