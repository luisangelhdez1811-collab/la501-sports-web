<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B-27 (LFPC art. 46 a 48): toda promoción dice sus condiciones y su vigencia. B-25 (LGS art. 308):
 * una promoción puede anunciar alcohol aunque no tenga platillos ligados (ej. «2x1 en cervezas»), así
 * que el admin también puede marcarla; si alguno de sus platillos lleva alcohol se considera marcada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->text('conditions')->nullable()->after('description');
            $table->boolean('alcoholic')->default(false)->after('sellable_online');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['conditions', 'alcoholic']);
        });
    }
};
