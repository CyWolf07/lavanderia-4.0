<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapaClientesController extends Controller
{
    /**
     * Barrios de Pasto con sus códigos postales (fuente: codigo-postal.co)
     */
    public const ZONAS_PASTO = [
        '520001' => 'Centro',
        '520002' => 'La Estrella',
        '520003' => 'Mijitayo',
        '520004' => 'Torobajo',
        '520005' => 'El Rosario',
        '520006' => 'Obrero',
        '520007' => 'Lorenzo',
        '520008' => 'Niza',
        '520009' => 'Vipri',
        '520010' => 'Morasurco',
        '520011' => 'Aranda',
        '520012' => 'Chapal',
        '520013' => 'Las Cuadras',
        '520014' => 'Pinares de Belen',
        '520015' => 'Pinar del Rio',
        '520016' => 'Santa Barbara',
        '520017' => 'San Vicente',
        '520018' => 'Bombona',
        '520019' => 'El Carmen',
        '520020' => 'La Aurora',
        '520021' => 'Tamasagra',
        '520022' => 'Las Colinas',
        '520023' => 'Sindagua',
        '520024' => 'San Felipe',
        '520025' => 'Briceño',
        '520026' => 'Madrigal',
        '520027' => 'El Tejar',
        '520028' => 'Palermo',
        '520029' => 'Jamondino',
        '520030' => 'Jongovito',
    ];

    public function index()
    {
        $clientes = Cliente::with('recolector')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($c) => [
                'id'             => $c->id,
                'nombre'         => $c->nombre,
                'numero_cliente' => $c->numero_cliente,
                'celular'        => $c->celular,
                'direccion'      => $c->direccion ?? '',
                'barrio'         => $c->barrio ?? '',
                'codigo_postal'  => $c->codigo_postal ?? '',
                'latitud'        => $c->latitud ? (float) $c->latitud : null,
                'longitud'       => $c->longitud ? (float) $c->longitud : null,
                'recolector'     => $c->recolector?->name ?? 'Sin asignar',
                'activo'         => $c->activo,
            ]);

        $zonas = self::ZONAS_PASTO;

        return view('admin.mapa-clientes', compact('clientes', 'zonas'));
    }

    /**
     * Endpoint JSON para actualizar coordenadas de un cliente (geocodificación manual o auto).
     */
    public function updateCoordenadas(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'latitud'       => ['required', 'numeric', 'between:-90,90'],
            'longitud'      => ['required', 'numeric', 'between:-180,180'],
            'codigo_postal' => ['nullable', 'string', 'max:10'],
        ]);

        $cliente->update($data);

        return response()->json(['ok' => true]);
    }
}
