<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Minishlink\WebPush\VAPID;

class PuntualKeys
{
    public function obtener(): ?array
    {
        if (config('puntual.vapid_public_key') && config('puntual.vapid_private_key')) {
            return ['publicKey' => config('puntual.vapid_public_key'), 'privateKey' => config('puntual.vapid_private_key')];
        }
        $value = SystemSetting::getValue($this->settingKey());

        try {
            return $value ? json_decode(Crypt::decryptString($value), true, flags: JSON_THROW_ON_ERROR) : null;
        } catch (DecryptException|\JsonException $exception) {
            return null;
        }
    }

    public function preparar(): void
    {
        if ($this->obtener()) {
            return;
        }
        SystemSetting::firstOrCreate(['key' => $this->settingKey()], [
            'value' => Crypt::encryptString(json_encode(VAPID::createVapidKeys(), JSON_THROW_ON_ERROR)),
        ]);
    }

    private function settingKey(): string
    {
        // Shared databases must not mix encrypted credentials from different installations.
        return 'puntual_vapid_'.hash('sha256', config('app.url').'|'.config('app.key'));
    }
}
