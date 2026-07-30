<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\Gasto;
use App\Models\IncongruenciaRecolector;
use App\Models\PagoRecolector;
use App\Models\RecolectorPrenda;
use App\Models\User;
use App\Notifications\IncongruenciaRecolectorDetectada;
use App\Services\FacturaRecolectorAuditService;
use App\Services\DashboardCacheService;
use App\Services\NumeroOrdenService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RecolectorController extends Controller
{
    public function __construct(
        private readonly FacturaRecolectorAuditService $auditService,
        private readonly NumeroOrdenService $numeroOrdenService,
    ) {}

    public function index()
    {
        $user = Auth::user();
        $periodoActual = Gasto::periodoDesdeFecha(now());
        [$inicioQuincena, $finQuincena] = $this->rangoQuincenaActual();

        // F1: Mostrar clientes propios y clientes aun sin asignar.
        $clientes = Cliente::activos()
            ->visiblesParaRecolector($user->id)
            ->orderBy('nombre')
            ->get();

        $prendas = RecolectorPrenda::activas()->orderBy('nombre')->get();
        $siguienteNumeroFactura = $this->numeroOrdenService->peekSiguiente($user->id);

        $facturas = FacturaRecolector::with(['cliente', 'detalles'])
            ->where('recolector_id', $user->id)
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->get();

        $facturaStatusResumen = $facturas
            ->groupBy(fn (FacturaRecolector $factura) => $factura->estado_factura ?? 'pendiente')
            ->map(fn ($items) => (object) [
                'cantidad' => $items->count(),
                'total' => (float) $items->sum('total'),
            ]);

        $totalFacturasQuincena = FacturaRecolector::query()
            ->noCanceladas()
            ->where('recolector_id', $user->id)
            ->whereBetween('fecha_ingreso', [$inicioQuincena, $finQuincena])
            ->sum('total');

        $gastosQuincena = Gasto::query()
            ->where('user_id', $user->id)
            ->where('periodo', $periodoActual['periodo'])
            ->sum('monto');

        $gastosRecientes = Gasto::query()
            ->where('user_id', $user->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        // F2: Reporte pago = 30% del total de facturas de la quincena
        $reportePagoQuincena = round($totalFacturasQuincena * 0.30, 0);

        return view('recolector.index', [
            'clientes'              => $clientes,
            'prendas'               => $prendas,
            'facturas'              => $facturas,
            'user'                  => $user,
            'fechaIngreso'          => now(),
            'clientePreseleccionado'=> session('cliente_creado_id'),
            'puedeEditarPrecios'    => $user->puedeEditarPrecios(),
            'siguienteNumeroFactura'=> $siguienteNumeroFactura,
            'periodoActual'         => $periodoActual['periodo'],
            'totalFacturasQuincena' => $totalFacturasQuincena,
            'facturaStatusResumen'  => $facturaStatusResumen,
            'gastosQuincena'        => $gastosQuincena,
            'reportePagoQuincena'   => $reportePagoQuincena,
            'gastosRecientes'       => $gastosRecientes,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id'                    => ['required', 'exists:clientes,id'],
            'fecha_entrega'                 => ['nullable', 'date', 'after_or_equal:today'],
            'observaciones'                 => ['nullable', 'array'],
            'observaciones.*'               => ['string'],
            'items'                         => ['nullable', 'array'],
            'items.*.prenda_id'             => ['nullable', 'integer'],
            'items.*.cantidad'              => ['nullable', 'integer', 'min:0'],
            'items.*.precio_unitario'       => ['nullable', 'numeric', 'min:0'],
            'items.*.color_prenda'          => ['nullable', 'string', 'max:50'],
            'items.*.colores'               => ['nullable', 'array'],
            'items.*.colores.*'             => ['string', 'max:30'],
        ]);

        $recolector = $request->user();

        $itemsSeleccionados = collect($request->input('items', []))
            ->filter(fn ($item) => $this->itemSeleccionado($item))
            ->values();

        if ($itemsSeleccionados->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Debes seleccionar al menos una prenda con cantidad.',
            ]);
        }

        if ($itemsSeleccionados->contains(fn ($item) => (int) ($item['cantidad'] ?? 0) < 1)) {
            throw ValidationException::withMessages([
                'items' => 'Cada prenda seleccionada debe tener una cantidad mayor a cero.',
            ]);
        }

        $itemsSeleccionados = $itemsSeleccionados
            ->map(function ($item) {
                $colores = $this->normalizarColoresPrenda($item);

                return [
                    'prenda_id'       => (int) ($item['prenda_id'] ?? 0),
                    'cantidad'        => (int) ($item['cantidad'] ?? 0),
                    'precio_unitario' => isset($item['precio_unitario']) ? (float) $item['precio_unitario'] : null,
                    'color_prenda'    => $colores !== [] ? implode(', ', $colores) : null,
                ];
            })
            ->filter(fn (array $item) => $item['prenda_id'] > 0 && $item['cantidad'] > 0)
            ->values();

        if ($itemsSeleccionados->contains(fn (array $item) => blank($item['color_prenda']))) {
            throw ValidationException::withMessages([
                'items' => 'Cada prenda debe tener al menos un color seleccionado.',
            ]);
        }

        if ($itemsSeleccionados->contains(fn (array $item) => count(explode(', ', $item['color_prenda'])) < $item['cantidad'])) {
            throw ValidationException::withMessages([
                'items' => 'Debes seleccionar un color por cada prenda ingresada.',
            ]);
        }

        if ($itemsSeleccionados->pluck('prenda_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'No puedes registrar la misma prenda dos veces en una factura.',
            ]);
        }

        $prendas = RecolectorPrenda::activas()
            ->whereIn('id', $itemsSeleccionados->pluck('prenda_id'))
            ->get()
            ->keyBy('id');

        if ($prendas->count() !== $itemsSeleccionados->pluck('prenda_id')->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Una de las prendas seleccionadas no existe o esta inhabilitada.',
            ]);
        }

        // F1: El cliente debe estar asignado al recolector o aun sin asignar.
        $cliente = Cliente::activos()
            ->visiblesParaRecolector($recolector->id)
            ->find($data['cliente_id']);

        if (! $cliente) {
            throw ValidationException::withMessages([
                'cliente_id' => 'El cliente seleccionado no esta disponible.',
            ]);
        }

        if ($recolector->puedeEditarPrecios() && $itemsSeleccionados->contains(fn (array $item) => ($item['precio_unitario'] ?? 0) < 0)) {
            throw ValidationException::withMessages([
                'items' => 'El precio unitario no puede ser negativo.',
            ]);
        }

        $fechaIngreso = now();
        $fechaEntrega = ! empty($data['fecha_entrega'])
            ? Carbon::parse($data['fecha_entrega'])
            : $fechaIngreso->copy()->addDays(2);

        $detalles = $itemsSeleccionados->map(function (array $item) use ($prendas, $recolector) {
            $prenda = $prendas->get($item['prenda_id']);
            $valorUnitario = $recolector->puedeEditarPrecios()
                ? (float) ($item['precio_unitario'] ?? $prenda->precio)
                : (float) $prenda->precio;
            $subtotal = $valorUnitario * $item['cantidad'];

            return [
                'recolector_prenda_id' => $prenda->id,
                'prenda_nombre'        => $prenda->nombre,
                'valor_unitario'       => $valorUnitario,
                'cantidad'             => $item['cantidad'],
                'color_prenda'         => $item['color_prenda'],
                'subtotal'             => $subtotal,
            ];
        });

        $totalPrendas = (int) $detalles->sum('cantidad');
        $totalFactura = $detalles->sum('subtotal');

        $factura = DB::transaction(function () use ($cliente, $fechaIngreso, $fechaEntrega, $data, $detalles, $totalPrendas, $totalFactura) {
            // Determinar la quincena de origen al momento de crear la factura
            $periodoOrigen = Gasto::periodoDesdeFecha(\Carbon\Carbon::parse($fechaIngreso));

            $factura = FacturaRecolector::create([
                'numero_orden'   => $this->numeroOrdenService->obtenerSiguiente(Auth::id()),
                'recolector_id'  => Auth::id(),
                'cliente_id'     => $cliente->id,
                'fecha_ingreso'  => $fechaIngreso,
                'fecha_entrega'  => $fechaEntrega->toDateString(),
                'direccion'      => $cliente->direccion,
                'numero_cliente' => $cliente->numero_cliente,
                'celular'        => $cliente->celular,
                'observaciones'  => array_values($data['observaciones'] ?? []),
                'total_prendas'  => $totalPrendas,
                'total'          => $totalFactura,
                'estado_factura' => 'pendiente',
                'quincena_origen'=> $periodoOrigen['periodo'],  // Registro inmutable del origen
            ]);

            $factura->detalles()->createMany($detalles->all());

            $this->registrarIncongruencias($factura);

            return $factura;
        });

        $whatsappStatus = $this->enviarWhatsappSiFueSolicitado($request, $factura, $cliente);
        $successMessage = $whatsappStatus === 'sent'
            ? 'Orden guardada y mensaje de WhatsApp enviado correctamente.'
            : 'Orden guardada correctamente.';

        $redirect = redirect()
            ->route('recolector.index')
            ->with('success', $successMessage)
            ->with('nueva_factura_id', $factura->id);

        if ($whatsappStatus === 'disabled') {
            // with() devuelve una nueva instancia — se debe reasignar
            $redirect = $redirect->with('error', 'La automatizacion de WhatsApp Business no esta habilitada.');
        }

        if ($whatsappStatus === 'error') {
            $redirect = $redirect->with('error', 'La orden fue guardada pero el mensaje de WhatsApp no pudo enviarse.');
        }

        return $redirect;
    }

    public function updateFacturaEstado(Request $request, FacturaRecolector $facturaRecolector)
    {
        if ((int) $facturaRecolector->recolector_id !== (int) $request->user()->id) {
            abort(403);
        }

        $data = $request->validate([
            'estado_factura' => ['required', 'in:pagado,pendiente'],
            'metodo_pago'    => ['nullable', 'required_if:estado_factura,pagado', 'in:efectivo,qr,nequi,llave_breve'],
        ]);

        if ($facturaRecolector->estaPagada()) {
            return back()->with('error', 'Las facturas pagadas ya no se pueden modificar.');
        }

        if ($facturaRecolector->estaCancelada()) {
            return back()->with('error', 'Una factura cancelada solo puede ser modificada por el administrador.');
        }

        $nuevoEstado = $data['estado_factura'];

        // ── Reasignación de quincena al momento del pago (igual que en AdminController) ──
        $camposActualizar = [
            'estado_factura' => $nuevoEstado,
            'metodo_pago'    => $nuevoEstado === 'pagado' ? $data['metodo_pago'] : null,
        ];

        if ($nuevoEstado === 'pagado') {
            $periodoActivo = Gasto::periodoDesdeFecha(now());
            $quincenaPago  = $periodoActivo['periodo'];

            $camposActualizar['quincena_pago'] = $quincenaPago;

            if (empty($facturaRecolector->quincena_origen)) {
                $fechaIngreso = $facturaRecolector->fecha_ingreso ?? now();
                $camposActualizar['quincena_origen'] = Gasto::periodoDesdeFecha(
                    \Carbon\Carbon::parse($fechaIngreso)
                )['periodo'];
            }
        }

        DB::transaction(function () use ($facturaRecolector, $camposActualizar, $nuevoEstado) {
            $facturaRecolector->update($camposActualizar);

            if ($nuevoEstado === 'pagado') {
                PagoRecolector::recalcular(
                    recolectorId: (int) $facturaRecolector->recolector_id,
                    quincena:     $camposActualizar['quincena_pago'],
                    porcentaje:   30.0
                );
            }
        });

        app(DashboardCacheService::class)->flushFacturas();

        return back()->with('success', 'Estatus de factura actualizado correctamente.');
    }

    private function itemSeleccionado(mixed $item): bool
    {
        if (! is_array($item)) {
            return false;
        }

        return filter_var($item['selected'] ?? false, FILTER_VALIDATE_BOOLEAN)
            || in_array(($item['selected'] ?? null), ['1', 1, true, 'true', 'on'], true);
    }

    private function normalizarColoresPrenda(array $item): array
    {
        $coloresPermitidos = [
            'Blanco',
            'Negro',
            'Azul',
            'Rojo',
            'Verde',
            'Amarillo',
            'Gris',
            'Rosa',
            'Cafe',
            'Morado',
            'Naranja',
            'Beige',
            'Violeta',
            'Multicolor',
            'Otro',
        ];

        $colores = $item['colores'] ?? [];

        if (! is_array($colores)) {
            $colores = [];
        }

        if ($colores === [] && ! blank($item['color_prenda'] ?? null)) {
            $colores = explode(',', (string) $item['color_prenda']);
        }

        return collect($colores)
            ->map(fn ($color) => trim((string) $color))
            ->filter(fn ($color) => in_array($color, $coloresPermitidos, true))
            ->values()
            ->all();
    }

    private function rangoQuincenaActual(): array
    {
        $hoy = now();

        if ($hoy->day <= 15) {
            return [
                $hoy->copy()->startOfMonth()->startOfDay(),
                $hoy->copy()->startOfMonth()->day(15)->endOfDay(),
            ];
        }

        return [
            $hoy->copy()->startOfMonth()->day(16)->startOfDay(),
            $hoy->copy()->endOfMonth()->endOfDay(),
        ];
    }

    private function enviarWhatsappSiFueSolicitado(Request $request, FacturaRecolector $factura, Cliente $cliente): ?string
    {
        if (! $request->boolean('enviar_whatsapp')) {
            return null;
        }

        if (! config('services.whatsapp.enabled') || ! config('services.whatsapp.phone_number_id') || ! config('services.whatsapp.token')) {
            return 'disabled';
        }

        $telefono = $this->normalizarTelefonoWhatsapp($cliente->celular);

        if (! $telefono) {
            return 'disabled';
        }

        try {
            Http::withToken((string) config('services.whatsapp.token'))
                ->post(sprintf(
                    'https://graph.facebook.com/%s/%s/messages',
                    config('services.whatsapp.api_version', 'v20.0'),
                    config('services.whatsapp.phone_number_id')
                ), [
                    'messaging_product' => 'whatsapp',
                    'to'                => $telefono,
                    'type'              => 'text',
                    'text'              => [
                        'body' => sprintf(
                            'Hola %s, tu orden de lavanderia #%s fue registrada correctamente. Total prendas: %s. Fecha de entrega: %s.',
                            $cliente->nombre,
                            $factura->id,
                            $factura->total_prendas,
                            optional($factura->fecha_entrega)->format('d/m/Y') ?? $factura->fecha_entrega
                        ),
                    ],
                ])
                ->throw();

            return 'sent';
        } catch (\Throwable $e) {
            // La factura ya fue guardada en DB. Solo se reporta el fallo de WA
            // sin relanzar la excepción para no causar un 500 al usuario.
            report($e);

            return 'error';
        }
    }

    private function normalizarTelefonoWhatsapp(?string $celular): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) $celular);

        if (strlen($digitos) === 10) {
            return '57'.$digitos;
        }

        if (str_starts_with($digitos, '57') && strlen($digitos) === 12) {
            return $digitos;
        }

        return null;
    }

    private function registrarIncongruencias(FacturaRecolector $factura): void
    {
        $incongruencias = $this->auditService->detectarIncongruencias($factura);

        if ($incongruencias === []) {
            return;
        }

        $admins = User::query()
            ->where(function ($query) {
                $query->whereIn('rol', ['admin', 'programador'])
                    ->orWhereHas('rolRelacion', fn ($rolQuery) => $rolQuery->whereIn('nombre', ['Admin', 'Programador']));
            })
            ->where('activo', true)
            ->get();

        foreach ($incongruencias as $dato) {
            $registro = IncongruenciaRecolector::create([
                'factura_recolector_id' => $factura->id,
                'recolector_id'         => $factura->recolector_id,
                'cliente_id'            => $factura->cliente_id,
                'titulo'                => $dato['titulo'],
                'detalle'               => $dato['detalle'],
                'estado'                => 'pendiente',
                'detectada_en'          => now(),
            ]);

            $registro->loadMissing('recolector');
            foreach ($admins as $admin) {
                $admin->notify(new IncongruenciaRecolectorDetectada($registro));
            }
        }
    }
}
