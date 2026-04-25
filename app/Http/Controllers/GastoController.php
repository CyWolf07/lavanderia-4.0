<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GastoController extends Controller
{
    public function storeFromRecolector(Request $request): RedirectResponse
    {
        $this->storeForAuthenticatedUser($request);

        return redirect()
            ->route('recolector.index')
            ->with('success', 'Gasto registrado correctamente para esta quincena.');
    }

    public function storeFromAdmin(Request $request): RedirectResponse
    {
        $this->storeForAuthenticatedUser($request);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Gasto registrado correctamente para esta quincena.');
    }

    private function storeForAuthenticatedUser(Request $request): void
    {
        $data = $request->validate([
            'concepto' => ['required', 'string', 'max:150'],
            'monto' => ['required', 'numeric', 'min:0.01'],
        ]);

        $fecha = now();
        $periodo = Gasto::periodoDesdeFecha($fecha);

        Gasto::create([
            'user_id' => $request->user()->id,
            'concepto' => $data['concepto'],
            'monto' => (float) $data['monto'],
            'fecha' => $fecha->toDateString(),
            'periodo' => $periodo['periodo'],
            'anio' => $periodo['anio'],
            'mes' => $periodo['mes'],
            'quincena' => $periodo['quincena'],
        ]);
    }
}
