<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;
use Minishlink\WebPush\VAPID;

class PuntualKeys
{
    public function obtener(): ?array
    {
        if (config('puntual.vapid_public_key') && config('puntual.vapid_private_key')) {
            return ['publicKey' => config('puntual.vapid_public_key'), 'privateKey' => config('puntual.vapid_private_key')];
        }
        $value = SystemSetting::getValue('puntual_vapid');

        return $value ? json_decode(Crypt::decryptString($value), true, flags: JSON_THROW_ON_ERROR) : null;
    }

    public function preparar(): void
    {
        if ($this->obtener()) {
            return;
        }
        SystemSetting::firstOrCreate(['key' => 'puntual_vapid'], [
            'value' => Crypt::encryptString(json_encode(VAPID::createVapidKeys(), JSON_THROW_ON_ERROR)),
        ]);
    }
}
