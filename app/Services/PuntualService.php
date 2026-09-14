<?php

namespace App\Services;

use App\Models\FacturaRecolector;
use App\Models\PuntualRecordatorio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PuntualService
{
    public function generar(): void
    {
        $hoy = now(config('puntual.timezone'))->startOfDay();
        FacturaRecolector::noCanceladas()->whereNull('entregado_en')
            ->whereDate('fecha_entrega', '>=', $hoy->toDateString())
            ->whereDate('fecha_entrega', '<=', $hoy->copy()->addDay()->toDateString())
            ->whereHas('recolector', fn ($user) => $user->where('activo', true))
            ->chunkById(200, function ($facturas) use ($hoy) {
                foreach ($facturas as $factura) {
                    PuntualRecordatorio::firstOrCreate([
                        'factura_recolector_id' => $factura->id,
                        'user_id' => $factura->recolector_id,
                        'fecha_entrega' => $factura->fecha_entrega->toDateString(),
                        'tipo' => $factura->fecha_entrega->toDateString() === $hoy->toDateString() ? 'hoy' : 'manana',
                    ], ['fecha_aviso' => $hoy->toDateString()]);
                }
            });
    }

    public function enviar(): int
    {
        $this->generar();
        $keys = app(PuntualKeys::class)->obtener();
        if (! $keys) {
            return 0;
        }
        $push = app(WebPush::class, ['auth' => ['VAPID' => [
            'subject' => config('puntual.vapid_subject'),
            'publicKey' => $keys['publicKey'],
            'privateKey' => $keys['privateKey'],
        ]], 'defaultOptions' => ['TTL' => 3600], 'timeout' => 10, 'clientOptions' => ['allow_redirects' => false]]);
        $enviados = 0;
        PuntualRecordatorio::vigentes()->with('factura')->whereNull('push_en')
            ->whereDate('fecha_aviso', now(config('puntual.timezone'))->toDateString())
            ->chunkById(100, function ($avisos) use ($push, &$enviados) {
                foreach ($avisos as $aviso) {
                    $suscripciones = DB::table('puntual_suscripciones')->where('user_id', $aviso->user_id)->get();
                    $correcto = $suscripciones->isNotEmpty();
                    foreach ($suscripciones as $suscripcion) {
                        try {
                            $report = $push->sendOneNotification(Subscription::create([
                                'endpoint' => $suscripcion->endpoint,
                                'publicKey' => $suscripcion->public_key,
                                'authToken' => $suscripcion->auth_token,
                            ]), json_encode([
                                'title' => 'Puntual: entrega '.($aviso->tipo === 'hoy' ? 'hoy' : 'mañana'),
                                'body' => 'Orden #'.$aviso->factura->numero_orden.'. Revisa las prendas pendientes de entrega.',
                                'tag' => 'puntual-'.$aviso->id,
                                'url' => '/puntual?orden='.$aviso->factura_recolector_id,
                            ], JSON_UNESCAPED_UNICODE));
                        } catch (\Throwable $exception) {
                            Log::warning('Puntual: no se pudo enviar un aviso.', ['aviso_id' => $aviso->id, 'suscripcion_id' => $suscripcion->id]);
                            $correcto = false;

                            continue;
                        }
                        if ($report->isSubscriptionExpired()) {
                            DB::table('puntual_suscripciones')->where('id', $suscripcion->id)->delete();
                        } elseif (! $report->isSuccess()) {
                            $correcto = false;
                        }
                    }
                    if ($correcto) {
                        $aviso->update(['push_en' => now()]);
                        $enviados++;
                    }
                }
            });

        return $enviados;
    }
}
