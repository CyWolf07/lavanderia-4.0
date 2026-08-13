<?php

namespace App\Http\Controllers;

use App\Models\FacturaRecolector;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacturaRecolectorPrintController extends Controller
{
    public function __invoke(Request $request, FacturaRecolector $facturaRecolector): View
    {
        $user = $request->user();

        if ($user->tieneRol('recolector') && (int) $facturaRecolector->recolector_id !== (int) $user->id) {
            abort(403);
        }

        if (! $user->tieneRol('recolector', 'admin', 'programador')) {
            abort(403);
        }

        $formato = $request->query('formato', 'carta');
        if (! in_array($formato, ['carta', 'media-carta', 'ticket'], true)) {
            $formato = 'carta';
        }

        $facturaRecolector->load(['cliente', 'detalles', 'recolector']);

        return view('facturas-recolector.imprimir', [
            'factura' => $facturaRecolector,
            'formato' => $formato,
        ]);
    }
}
