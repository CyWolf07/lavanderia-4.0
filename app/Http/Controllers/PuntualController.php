<?php

namespace App\Http\Controllers;

use App\Models\FacturaRecolector;
use App\Models\PuntualRecordatorio;
use App\Services\PuntualKeys;
use App\Services\PuntualService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PuntualController extends Controller
{
    public function index(Request $request, PuntualService $service)
    {
        $data = $request->validate([
            'vista' => ['nullable', 'in:pendientes,hoy,manana,vencidas,entregadas,todas'],
            'orden' => ['nullable', 'integer'],
        ]);
        $service->generar();
        $admin = $request->user()->esAdmin();
        $hoy = now(config('puntual.timezone'))->startOfDay();
        $base = FacturaRecolector::query()->noCanceladas()
            ->when(! $admin, fn ($q) => $q->where('recolector_id', $request->user()->id));
        $conteos = [
            'hoy' => (clone $base)->whereNull('entregado_en')->whereDate('fecha_entrega', $hoy->toDateString())->count(),
            'manana' => (clone $base)->whereNull('entregado_en')->whereDate('fecha_entrega', $hoy->copy()->addDay()->toDateString())->count(),
            'vencidas' => (clone $base)->whereNull('entregado_en')->whereDate('fecha_entrega', '<', $hoy->toDateString())->count(),
        ];
        $vista = $data['vista'] ?? 'pendientes';
        $ordenes = (clone $base)->with(['cliente', 'recolector', 'detalles'])
            ->when(isset($data['orden']), fn ($q) => $q->whereKey($data['orden']))
            ->when(! in_array($vista, ['todas', 'entregadas']), fn ($q) => $q->whereNull('entregado_en'))
            ->when($vista === 'entregadas', fn ($q) => $q->whereNotNull('entregado_en'))
            ->when($vista === 'hoy', fn ($q) => $q->whereDate('fecha_entrega', $hoy->toDateString()))
            ->when($vista === 'manana', fn ($q) => $q->whereDate('fecha_entrega', $hoy->copy()->addDay()->toDateString()))
            ->when($vista === 'vencidas', fn ($q) => $q->whereDate('fecha_entrega', '<', $hoy->toDateString()))
            ->orderBy('fecha_entrega')->orderBy('id')->paginate(20)->withQueryString();
        $avisos = PuntualRecordatorio::vigentes()->with('factura')
            ->where('user_id', $request->user()->id)->whereNull('leido_en')
            ->whereDate('fecha_aviso', $hoy->toDateString())->get();

        $pushKey = app(PuntualKeys::class)->obtener()['publicKey'] ?? '';

        return response()->view('puntual.index', compact('admin', 'hoy', 'conteos', 'vista', 'ordenes', 'avisos', 'pushKey'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function entregar(Request $request, FacturaRecolector $factura)
    {
        abort_unless($request->user()->esAdmin() || $factura->recolector_id === $request->user()->id, 403);
        abort_if($factura->estaCancelada(), 422, 'La orden está cancelada.');
        FacturaRecolector::whereKey($factura->id)->whereNull('entregado_en')->update([
            'entregado_en' => now(), 'entregado_por' => $request->user()->id,
        ]);

        return back()->with('status', 'Entrega confirmada.');
    }

    public function leer(Request $request, PuntualRecordatorio $aviso)
    {
        abort_unless($aviso->user_id === $request->user()->id, 403);
        $aviso->update(['leido_en' => now()]);

        return back();
    }

    public function suscribir(Request $request)
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'size:87', 'regex:/^[A-Za-z0-9_-]+$/'],
            'keys.auth' => ['required', 'string', 'size:22', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);
        // Only browser push services may receive server-side requests.
        $host = parse_url($data['endpoint'], PHP_URL_HOST);
        abort_unless(in_array($host, ['fcm.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com'], true)
            || str_ends_with($host, '.notify.windows.com'), 422);
        abort_if(parse_url($data['endpoint'], PHP_URL_PORT) || parse_url($data['endpoint'], PHP_URL_USER), 422);
        $hash = hash('sha256', $data['endpoint']);
        $existing = DB::table('puntual_suscripciones')->where('endpoint_hash', $hash)->first();
        abort_if($existing && $existing->user_id !== $request->user()->id, 409);
        DB::table('puntual_suscripciones')->updateOrInsert(['endpoint_hash' => $hash], [
            'user_id' => $request->user()->id, 'endpoint' => $data['endpoint'],
            'public_key' => $data['keys']['p256dh'], 'auth_token' => $data['keys']['auth'],
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $request->session()->put('puntual_endpoint_hash', $hash);

        return response()->json(['ok' => true]);
    }

    public function desuscribir(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'string']]);
        DB::table('puntual_suscripciones')->where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))->delete();

        return response()->noContent();
    }
}
