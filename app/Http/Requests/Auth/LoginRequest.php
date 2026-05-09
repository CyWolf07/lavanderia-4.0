<?php

namespace App\Http\Requests\Auth;

use App\Models\EnterpriseAccessControl;
use App\Services\DeviceAccessService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $login = trim((string) ($this->input('login') ?: $this->input('email')));

        if ($login === '') {
            return;
        }

        $this->merge([
            'login' => filter_var($login, FILTER_VALIDATE_EMAIL)
                ? Str::lower($login)
                : (preg_replace('/\D+/', '', $login) ?: $login),
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['nullable', 'required_without:email', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:login', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $devices = app(DeviceAccessService::class);

        if ($devices->isLocked($this, EnterpriseAccessControl::AREA_LOGIN)) {
            throw ValidationException::withMessages([
                'login' => 'Este dispositivo esta bloqueado por intentos fallidos. Solo el programador puede desbloquearlo.',
            ]);
        }

        $this->ensureIsNotRateLimited();

        $login = trim((string) ($this->input('login') ?: $this->input('email')));
        $campo = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'cedula';

        if (! Auth::attempt([$campo => $login, 'password' => $this->string('password')->toString()], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            $control = $devices->registerFailure($this, EnterpriseAccessControl::AREA_LOGIN);
            $remaining = max(0, DeviceAccessService::MAX_ATTEMPTS - $control->attempts);

            throw ValidationException::withMessages([
                'login' => $control->estaBloqueado()
                    ? 'Este dispositivo quedo bloqueado por intentos fallidos. Solo el programador puede desbloquearlo.'
                    : trans('auth.failed').' Te quedan '.$remaining.' intento(s).',
            ]);
        }

        if (! optional(Auth::user())->estaActivo()) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());
            $control = $devices->registerFailure($this, EnterpriseAccessControl::AREA_LOGIN);
            $remaining = max(0, DeviceAccessService::MAX_ATTEMPTS - $control->attempts);

            throw ValidationException::withMessages([
                'login' => $control->estaBloqueado()
                    ? 'Este dispositivo quedo bloqueado por intentos fallidos. Solo el programador puede desbloquearlo.'
                    : 'Tu cuenta se encuentra inhabilitada. Contacta al administrador. Te quedan '.$remaining.' intento(s).',
            ]);
        }

        $devices->clear($this, EnterpriseAccessControl::AREA_LOGIN);
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), DeviceAccessService::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $login = (string) ($this->input('login') ?: $this->input('email'));

        return Str::transliterate(Str::lower($login).'|'.$this->ip());
    }
}
