<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnterpriseAccessControl extends Model
{
    public const AREA_CODE = 'enterprise_code';

    public const AREA_LOGIN = 'login';

    protected $fillable = [
        'device_id',
        'area',
        'attempts',
        'locked_at',
        'unlocked_at',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'unlocked_at' => 'datetime',
            'last_attempt_at' => 'datetime',
        ];
    }

    public function estaBloqueado(): bool
    {
        return $this->locked_at !== null;
    }
}
