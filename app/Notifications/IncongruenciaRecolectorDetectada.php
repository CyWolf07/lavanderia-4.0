<?php

namespace App\Notifications;

use App\Models\IncongruenciaRecolector;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncongruenciaRecolectorDetectada extends Notification
{
    use Queueable;

    public function __construct(private readonly IncongruenciaRecolector $incongruencia)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'incongruencia_id' => $this->incongruencia->id,
            'factura_id' => $this->incongruencia->factura_recolector_id,
            'recolector' => $this->incongruencia->recolector?->name ?? 'Desconocido',
            'titulo' => $this->incongruencia->titulo,
            'detalle' => $this->incongruencia->detalle,
            'estado' => $this->incongruencia->estado,
        ];
    }
}
