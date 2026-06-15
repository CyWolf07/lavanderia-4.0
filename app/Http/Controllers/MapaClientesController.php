<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapaClientesController extends Controller
{
    /**
     * Mapa completo: código postal → nombre de zona representativa.
     * Fuente: codigopostaldecolombia.com / codigo-postal.co (Pasto, Nariño)
     */
    public const ZONAS_PASTO = [
        '520001' => 'Centro / Agualongo',
        '520002' => 'La Estrella / Briceño',
        '520003' => 'Mijitayo / Bellavista',
        '520004' => 'Torobajo / Aranda',
        '520005' => 'El Rosario / Obrero Sur',
        '520006' => 'Obrero / La Esmeralda',
        '520007' => 'Lorenzo / San Felipe',
        '520008' => 'Niza / Pinar del Río',
        '520009' => 'Vipri / Santa Bárbara',
        '520010' => 'Morasurco / Miraflores',
        '520011' => 'Aranda / Sindagua',
        '520012' => 'Chapal / San Vicente',
        '520013' => 'Las Cuadras / El Dorado',
        '520014' => 'Pinares de Belén',
        '520015' => 'Pinar del Río',
        '520016' => 'Santa Bárbara',
        '520017' => 'San Vicente / Jongovito',
        '520018' => 'Bombona / Tamasagra',
        '520019' => 'El Carmen / La Aurora',
        '520020' => 'La Aurora / Las Colinas',
        '520021' => 'Tamasagra / El Tejar',
        '520022' => 'Las Colinas / Madrigal',
        '520023' => 'Sindagua / Palermo',
        '520024' => 'San Felipe / Jamondino',
        '520025' => 'Briceño / Calatrava',
        '520026' => 'Madrigal / La Rivera',
        '520027' => 'El Tejar / Bachué',
        '520028' => 'Palermo / Anganoy',
        '520029' => 'Jamondino / Jongovito',
        '520030' => 'Jongovito / Rural Norte',
    ];

    /**
     * Mapa inverso: palabra clave del barrio → código postal.
     * Para autodetección al escribir el nombre del barrio.
     */
    public const BARRIOS_CP = [
        // 520001 — Centro
        'agualongo'     => '520001', 'altamira'    => '520001', 'anganoy'     => '520001',
        'bachué'        => '520001', 'bacue'       => '520001', 'caicedo'     => '520001',
        'el bosque'     => '520001', 'centro'      => '520001', 'ancizar'     => '520001',
        // 520002 — La Estrella / Briceño
        'achalay'       => '520002', 'briceño'     => '520002', 'briceno'     => '520002',
        'calatrava'     => '520002', 'camino real' => '520002', 'castilla'    => '520002',
        'el dorado'     => '520002', 'las cuadras' => '520002', 'la estrella' => '520002',
        // 520003 — Mijitayo / Bellavista
        'atahualpa'     => '520003', 'bellavista'  => '520003', 'boyacá'      => '520003',
        'boyaca'        => '520003', 'mijitayo'    => '520003', 'batallon'    => '520003',
        // 520004 — Torobajo / Aranda
        'alcázares'     => '520004', 'alcazares'   => '520004', 'altos de la carolina' => '520004',
        'aranda'        => '520004', 'belalcázar'  => '520004', 'belalcazar'  => '520004',
        'torobajo'      => '520004', 'alameda'     => '520004',
        // 520005 — El Rosario
        'el rosario'    => '520005', 'obrero sur'  => '520005', 'naranjal'    => '520005',
        // 520006 — Obrero / La Esmeralda
        'alejandría'    => '520006', 'alejandria'  => '520006', 'arnulfo'     => '520006',
        'baviera'       => '520006', 'bernal'      => '520006', 'betania'     => '520006',
        'caicedonia'    => '520006', 'casaloma'    => '520006', 'la esmeralda'=> '520006',
        'las lajas'     => '520006', 'obrero'      => '520006',
        // 520007 — Lorenzo / San Felipe
        'lorenzo'       => '520007', 'san felipe'  => '520007', 'caldas'      => '520007',
        // 520008 — Niza / Pinar del Río
        'niza'          => '520008', 'pinar del rio'=> '520008','pinar del río'=> '520008',
        // 520009 — Vipri / Santa Bárbara
        'vipri'         => '520009', 'santa bárbara'=> '520009','santa barbara'=> '520009',
        // 520010 — Morasurco / Miraflores
        'altos de chapalito' => '520010', 'altos del campo' => '520010',
        'belén'         => '520010', 'belen'       => '520010', 'cantarana'   => '520010',
        'el porvenir'   => '520010', 'la rosa'     => '520010', 'miraflores'  => '520010',
        'morasurco'     => '520010', 'praga'       => '520010',
        // 520011 — Aranda / Sindagua
        'sindagua'      => '520011', 'antonio nariño' => '520011',
        // 520012 — Chapal / San Vicente
        'chapal'        => '520012', 'chapalito'   => '520012',
        // 520013 — Las Cuadras / El Dorado
        'buenos aires'  => '520013', 'villa del río'=> '520013',
        // 520014 — Pinares de Belén
        'pinares de belen' => '520014', 'pinares de belén' => '520014',
        // 520016 — Santa Bárbara
        'villa lucia'   => '520016',
        // 520017 — San Vicente / Jongovito
        'san vicente'   => '520017',
        // 520018 — Bombona / Tamasagra
        'bombona'       => '520018', 'bombonà'     => '520018', 'tamasagra'   => '520018',
        // 520019 — El Carmen / La Aurora
        'el carmen'     => '520019', 'la aurora'   => '520019',
        // 520020 — Las Colinas
        'las colinas'   => '520020', 'el recuerdo' => '520020',
        // 520021 — Tamasagra / El Tejar
        'el tejar'      => '520021',
        // 520022 — Madrigal
        'madrigal'      => '520022',
        // 520023 — Sindagua
        'palermo'       => '520023',
        // 520024 — Jamondino
        'jamondino'     => '520024',
        // 520025 — Briceño / Calatrava
        'la rivera'     => '520025', 'la riviera'  => '520025',
        // 520026 — Madrigal
        'el jardín'     => '520026', 'el jardin'   => '520026',
        // 520028 — Anganoy
        'las mercedes'  => '520028',
        // 520029 — Jongovito
        'jongovito'     => '520029',
        // 520030 — Rural Norte
        'mocondino'     => '520030', 'catambuco'   => '520030', 'mapachico'   => '520030',
        'santa barbara rural' => '520030',
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

    /**
     * Proxy hacia Nominatim/OSM para geocodificar desde el servidor.
     * Evita bloqueos CORS y User-Agent requeridos en producción.
     */
    public function geocodificar(Request $request)
    {
        $query = $request->validate(['q' => ['required', 'string', 'max:300']])['q'];

        $url = 'https://nominatim.openstreetmap.org/search';

        try {
            // Usar Http Facade de Laravel que usa cURL y evita problemas de allow_url_fopen
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent'      => 'LavanderiaPastoApp/1.0 (contacto@lavanderia.co)',
                'Accept-Language' => 'es',
            ])->timeout(8)->get($url, [
                'format' => 'json',
                'limit'  => 1,
                'q'      => $query,
            ]);

            if ($response->failed()) {
                return response()->json(['error' => 'No se pudo contactar con OSM Nominatim (Error HTTP ' . $response->status() . ').'], 502);
            }

            $data = $response->json();

            if (empty($data)) {
                return response()->json(['found' => false]);
            }

            return response()->json([
                'found'   => true,
                'lat'     => (float) $data[0]['lat'],
                'lon'     => (float) $data[0]['lon'],
                'display' => $data[0]['display_name'] ?? '',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error de conexión al geocodificar: ' . $e->getMessage()], 500);
        }
    }
}
