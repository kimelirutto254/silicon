<?php

use App\Services\EcosystemPulseService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('signal:pulse', function () {
    app(EcosystemPulseService::class)->regenerate();
    $this->info('Ecosystem pulse regenerated.');
})->purpose('Generate the weekly Signal Engine pulse');

Schedule::command('signal:pulse')->mondays()->at('08:00');
