<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;

class EnterpriseCodeService
{
    private const SETTING_KEY = 'enterprise_code';

    private const DEFAULT_CODE = 'Lavanderia2026!*';

    public function current(): string
    {
        return DB::transaction(function () {
            $setting = SystemSetting::query()
                ->where('key', self::SETTING_KEY)
                ->lockForUpdate()
                ->first();

            if ($setting) {
                return (string) $setting->value;
            }

            return self::DEFAULT_CODE;
        });
    }

    public function matches(string $code): bool
    {
        return hash_equals($this->current(), trim($code));
    }

    public function regenerate(): string
    {
        return DB::transaction(function () {
            $code = $this->generate();

            SystemSetting::updateOrCreate(
                ['key' => self::SETTING_KEY],
                ['value' => $code]
            );

            return $code;
        });
    }

    private function generate(): string
    {
        $groups = [
            'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'abcdefghijkmnopqrstuvwxyz',
            '23456789',
            '!@#$%&*?',
        ];

        $chars = [];

        foreach ($groups as $group) {
            $chars[] = $group[random_int(0, strlen($group) - 1)];
        }

        $pool = implode('', $groups);

        while (count($chars) < 18) {
            $chars[] = $pool[random_int(0, strlen($pool) - 1)];
        }

        shuffle($chars);

        return implode('', $chars);
    }
}
