<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * B-33: nada envía SMS de promociones, así que se pedía un permiso para algo que no existe (principio de
 * proporcionalidad de la LFPDPPP). Se apaga el valor guardado; la columna se conserva por ahora.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'promos_sms')) {
            DB::table('users')->where('promos_sms', true)->update(['promos_sms' => false]);
        }
    }

    public function down(): void
    {
        // El valor anterior no se recupera.
    }
};
