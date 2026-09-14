<?php

use App\Services\PuntualKeys;
use App\Services\PuntualService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('puntual:preparar', function (PuntualKeys $keys) {
    try {
        $keys->preparar();
        if ($keys->obtener()) {
            $this->info('Claves de avisos disponibles.');
        } else {
            $this->warn('Avisos pendientes: revisar las claves cifradas de esta instalacion.');
        }
    } catch (\Throwable $exception) {
        report($exception);
        $this->warn('No se pudieron preparar los avisos. La plataforma puede seguir funcionando.');
    }
});

Artisan::command('puntual:avisar', function (PuntualService $service) {
    $this->info('Recordatorios actualizados. Enviados: '.$service->enviar());
})->purpose('Generar y enviar recordatorios de entrega');

Schedule::command('puntual:avisar')->everyFiveMinutes()
    ->timezone(config('puntual.timezone'))
    ->between('08:00', '20:00')->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
