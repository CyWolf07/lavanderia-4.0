<?php

namespace App\Http\Controllers;

use App\Models\FacturaRecolector;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrdenPedidoController extends Controller
{
    public function imprimir(Request $request, FacturaRecolector $facturaRecolector): View
    {
        $user = $request->user();

        $puedeImprimir = $user->tieneRol('admin', 'programador')
            || ($user->tieneRol('recolector')
                && (int) $facturaRecolector->recolector_id === (int) $user->id);

        abort_unless($puedeImprimir, 403);

        $facturaRecolector->loadMissing(['cliente', 'recolector', 'detalles']);

        return view('ordenes.imprimir', [
            'factura' => $facturaRecolector,
            'imprimirAutomaticamente' => $request->boolean('imprimir'),
        ]);
    }
}
