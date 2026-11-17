<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// B-31: borra los datos que cumplieron su plazo de conservación (Aviso de Privacidad, sección 12).
// En Hostinger se necesita el cron «* * * * * php artisan schedule:run» para que esto corra.
Illuminate\Support\Facades\Schedule::command('privacy:purge')->dailyAt('03:30')->timezone('America/Mexico_City');
