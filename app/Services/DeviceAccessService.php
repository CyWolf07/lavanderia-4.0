<?php

namespace App\Services;

use App\Models\EnterpriseAccessControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class DeviceAccessService
{
    public const COOKIE_NAME = 'lavanderia_device_id';
    public const MAX_ATTEMPTS = 3;

    public function deviceId(Request $request): string
    {
        $deviceId = (string) $request->cookie(self::COOKIE_NAME);

        if ($deviceId === '') {
            $deviceId = (string) Str::uuid();
            Cookie::queue(cookie(self::COOKIE_NAME, $deviceId, 60 * 24 * 365));
        }

        return $deviceId;
    }

    public function control(Request $request, string $area): EnterpriseAccessControl
    {
        return EnterpriseAccessControl::firstOrCreate([
            'device_id' => $this->deviceId($request),
            'area' => $area,
        ]);
    }

    public function isLocked(Request $request, string $area): bool
    {
        return $this->control($request, $area)->estaBloqueado();
    }

    public function registerFailure(Request $request, string $area): EnterpriseAccessControl
    {
        $control = $this->control($request, $area);

        if ($control->estaBloqueado()) {
            return $control;
        }

        $control->attempts++;
        $control->last_attempt_at = now();

        if ($control->attempts >= self::MAX_ATTEMPTS) {
            $control->locked_at = now();
        }

        $control->save();

        return $control;
    }

    public function clear(Request $request, string $area): void
    {
        $this->control($request, $area)->update([
            'attempts' => 0,
            'locked_at' => null,
            'unlocked_at' => now(),
        ]);
    }

    public function lockedDevices()
    {
        return EnterpriseAccessControl::query()
            ->whereNotNull('locked_at')
            ->orderByDesc('locked_at')
            ->get();
    }
}
