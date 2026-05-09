<?php

namespace App\Http\Controllers;

use App\Models\EnterpriseAccessControl;
use App\Services\DeviceAccessService;
use App\Services\EnterpriseCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EnterpriseAccessController extends Controller
{
    public function create(Request $request, DeviceAccessService $devices): View|RedirectResponse
    {
        if ((bool) $request->session()->get('enterprise_code_validated', false)) {
            return redirect()->route('login');
        }

        return view('auth.enterprise-code', [
            'bloqueado' => $devices->isLocked($request, EnterpriseAccessControl::AREA_CODE),
        ]);
    }

    public function store(
        Request $request,
        EnterpriseCodeService $codes,
        DeviceAccessService $devices,
    ): RedirectResponse {
        if ($devices->isLocked($request, EnterpriseAccessControl::AREA_CODE)) {
            return back()->withErrors([
                'codigo_empresarial' => 'Este dispositivo esta bloqueado. Solo el programador puede desbloquearlo.',
            ]);
        }

        $data = $request->validate([
            'codigo_empresarial' => ['required', 'string', 'max:255'],
        ]);

        if (! $codes->matches($data['codigo_empresarial'])) {
            $control = $devices->registerFailure($request, EnterpriseAccessControl::AREA_CODE);
            $remaining = max(0, DeviceAccessService::MAX_ATTEMPTS - $control->attempts);

            return back()->withErrors([
                'codigo_empresarial' => $control->estaBloqueado()
                    ? 'Este dispositivo quedo bloqueado. Solo el programador puede desbloquearlo.'
                    : "Codigo empresarial incorrecto. Te quedan {$remaining} intento(s).",
            ]);
        }

        $devices->clear($request, EnterpriseAccessControl::AREA_CODE);
        $request->session()->put('enterprise_code_validated', true);

        return redirect()->route('login');
    }

    public function unlock(EnterpriseAccessControl $accessControl): RedirectResponse
    {
        if (! auth()->user()?->esProgramador()) {
            return redirect()->route('admin.dashboard')->with('error', 'Solo el programador puede desbloquear dispositivos.');
        }

        $accessControl->update([
            'attempts' => 0,
            'locked_at' => null,
            'unlocked_at' => now(),
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Dispositivo desbloqueado correctamente.');
    }

    public function regenerate(Request $request, EnterpriseCodeService $codes): RedirectResponse
    {
        if (! $request->user()?->esProgramador()) {
            return redirect()->route('admin.dashboard')->with('error', 'Solo el programador puede regenerar el codigo empresarial.');
        }

        $codes->regenerate();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('enterprise-code.create')
            ->with('success', 'Codigo empresarial regenerado. Todos deben validar el nuevo codigo para ingresar.');
    }
}
