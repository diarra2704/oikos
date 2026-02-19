<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Points présence culte : chaque lundi à 6h (pour le culte du dimanche)
Schedule::command('points:presence-culte')->weeklyOn(1, '06:00');
