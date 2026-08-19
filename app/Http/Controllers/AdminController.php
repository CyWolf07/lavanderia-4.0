<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Cliente;
use App\Models\FacturaRecolector;
use App\Models\Gasto;
use App\Models\HistorialProduccion;
use App\Models\IncongruenciaProduccion;
use App\Models\IncongruenciaRecolector;
use App\Models\PagoRecolector;
use App\Models\Prenda;
use App\Models\Produccion;
use App\Models\RecolectorPrenda;
use App\Models\Rol;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DashboardCacheService;
use App\Services\DeviceAccessService;
use App\Services\EnterpriseCodeService;
use App\Services\ProduccionValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard(EnterpriseCodeService $enterpriseCodes, DeviceAccessService $deviceAccess, DashboardCacheService $cache)
    {
        $periodoActual = Gasto::periodoDesdeFecha(now());
        [$inicioQuincena, $finQuincena] = $this->rangoQuincenaActual();
        $periodoKey = $periodoActual['periodo'];
        $modoInterfazLavandero = SystemSetting::getValue('produccion_interfaz_lavandero', 'basica');

        // ── Estadísticas de cabecera ─────────────────────────────────────────
        $totalUsuarios = User::count();
        $totalProduccionesActivas = Produccion::query()
            ->whereBetween('fecha', [$inicioQuincena, $finQuincena])
            ->count();
        $ingresosProduccionActiva = Produccion::query()
            ->pagables()
            ->whereBetween('fecha', [$inicioQuincena, $finQuincena])
            ->sum('total_validado');

        $totalFacturasActivas = FacturaRecolector::query()
            ->noCanceladas()
            ->whereBetween('fecha_ingreso', [$inicioQuincena, $finQuincena])
            ->count();

        $ingresoRecolectoresActivo = FacturaRecolector::query()
            ->where(function ($q) {
                $q->whereNull('estado_factura')->orWhere('estado_factura', 'pendiente');
            })
            ->sum('total');

        $totalProducciones = $totalProduccionesActivas + $totalFacturasActivas;

        // ── Panel financiero de la quincena ──────────────────────────────────
        $ordenesPagadasTotal = FacturaRecolector::query()
            ->pagadasEnQuincena($periodoKey)
            ->sum('total');

        $ordenesPagadasCantidad = FacturaRecolector::query()
            ->pagadasEnQuincena($periodoKey)
            ->count();

        $gastosQuincena = Gasto::query()
            ->where('periodo', $periodoKey)
            ->sum('monto');

        $totalNeto = $ordenesPagadasTotal - $gastosQuincena;

        // ── Comisiones del 30% ───────────────────────────────────────────────
        $pagosRecolectorQuincena = PagoRecolector::query()
            ->deQuincena($periodoKey)
            ->with('recolector')
            ->get();

        if ($pagosRecolectorQuincena->isNotEmpty()) {
            $recolectoresConFacturas = $pagosRecolectorQuincena->map(function ($pago) {
                return [
                    'nombre' => $pago->recolector?->name ?? 'Sin nombre',
                    'total' => (float) $pago->total_facturas,
                    'pago30' => (int) $pago->monto_comision,
                    'cantidad' => $pago->cantidad_facturas,
                    'pagado' => $pago->pagado_al_recolector,
                    'pagado_en' => $pago->pagado_en,
                    'recolector_id' => $pago->recolector_id,
                ];
            })->values();
        } else {
            // Fallback: calcular desde facturas si no hay registros en pagos_recolector
            $recolectoresConFacturas = FacturaRecolector::query()
                ->pagadasEnQuincena($periodoKey)
                ->with('recolector')
                ->get()
                ->groupBy('recolector_id')
                ->map(function ($facturas) {
                    $recolector = $facturas->first()->recolector;
                    $totalRecolector = (float) $facturas->sum('total');

                    return [
                        'nombre' => $recolector?->name ?? 'Sin nombre',
                        'total' => $totalRecolector,
                        'pago30' => (int) round($totalRecolector * 0.30),
                        'cantidad' => $facturas->count(),
                        'pagado' => false,
                        'pagado_en' => null,
                        'recolector_id' => $facturas->first()->recolector_id,
                    ];
                })
                ->values();
        }

        $total30PorCiento = $recolectoresConFacturas->sum('pago30');
        $pagoUsuarios = $ingresosProduccionActiva;
        $ganancia = $totalNeto - $pagoUsuarios - $total30PorCiento;

        // ── Historial de pagos recolectores ──────────────────────────────────
        $historialPagosRecolectores = PagoRecolector::query()
            ->with('recolector')
            ->orderByDesc('quincena')
            ->orderBy('recolector_id')
            ->limit(50)
            ->get();

        // ── Resumen financiero ───────────────────────────────────────────────
        // Solo facturas marcadas como pagadas en la quincena actual (por quincena_pago).
        // Incluye órdenes creadas en quincenas anteriores pero cobradas ahora.
        $totalFacturasQuincena = FacturaRecolector::query()
            ->where('recolector_id', '!=', null)  // todas
            ->pagadasEnQuincena($periodoKey)
            ->sum('total');

        $reportePagoQuincena = $totalFacturasQuincena - $gastosQuincena;

        $gastosRecientes = Gasto::query()
            ->with('user')
            ->where('periodo', $periodoKey)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->take(8)
            ->get();

        $periodoActual = $periodoKey;

        // ── Registros de producción activa ───────────────────────────────────
        $ultimasProducciones = Produccion::with(['user', 'prenda'])
            ->whereBetween('fecha', [$inicioQuincena, $finQuincena])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        // ── Facturas recolector activas + quincena ───────────────────────────
        $facturasEstatusQuincena = function ($q) use ($periodoKey, $inicioQuincena, $finQuincena) {
            $q->where(function ($pendientes) {
                $pendientes->whereNull('estado_factura')
                    ->orWhere('estado_factura', 'pendiente');
            })
                ->orWhere(function ($pagadas) use ($periodoKey, $inicioQuincena, $finQuincena) {
                    $pagadas->where('estado_factura', 'pagado')
                        ->where(function ($periodoPago) use ($periodoKey, $inicioQuincena, $finQuincena) {
                            $periodoPago->where('quincena_pago', $periodoKey)
                                ->orWhere(function ($legacy) use ($inicioQuincena, $finQuincena) {
                                    $legacy->whereNull('quincena_pago')
                                        ->whereBetween('updated_at', [$inicioQuincena, $finQuincena]);
                                });
                        });
                })
                ->orWhere(function ($canceladas) use ($inicioQuincena, $finQuincena) {
                    $canceladas->where('estado_factura', 'cancelado')
                        ->whereBetween('updated_at', [$inicioQuincena, $finQuincena]);
                });
        };

        $ultimasFacturasRecolector = FacturaRecolector::with(['recolector', 'cliente', 'detalles'])
            ->where($facturasEstatusQuincena)
            ->orderByDesc('updated_at')
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->get();

        // ── JSON pre-computado para el modal de resumen de factura ───────────
        $facturasRecolectorResumen = $ultimasFacturasRecolector->mapWithKeys(function ($factura) {
            $estadoFactura = $factura->estado_factura ?? 'pendiente';
            $ordenFactura  = '#' . str_pad((string) ($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT);
            $detalles = [];
            foreach ($factura->detalles as $d) {
                $detalles[] = [
                    'prenda_nombre' => $d->prenda_nombre,
                    'cantidad'      => $d->cantidad,
                    'color_prenda'  => $d->color_prenda ?? '',
                    'subtotal'      => number_format((float) $d->subtotal, 0, ',', '.'),
                ];
            }
            return [
                $factura->id => [
                    'factura_id'     => $factura->id,
                    'numero_orden'   => $ordenFactura,
                    'cliente_nombre' => optional($factura->cliente)->nombre ?? 'Sin cliente',
                    'celular'        => $factura->celular ?? '',
                    'total'          => number_format((float) $factura->total, 0, ',', '.'),
                    'total_prendas'  => $factura->total_prendas,
                    'estado'         => $estadoFactura,
                    'recolector'     => optional($factura->recolector)->name ?? 'Sin recolector',
                    'detalles'       => $detalles,
                ],
            ];
        });

        $facturaStatusResumen = FacturaRecolector::query()
            ->where($facturasEstatusQuincena)
            ->selectRaw("COALESCE(estado_factura, 'pendiente') as estado, COUNT(*) as cantidad, SUM(total) as total")
            ->groupBy('estado')
            ->get()
            ->keyBy('estado');

        // ── Gráficas ─────────────────────────────────────────────────────────
        $ingresoFacturasPorDia = $ultimasFacturasRecolector
            ->groupBy(fn ($factura) => optional($factura->fecha_ingreso)->format('d/m') ?? 'Sin fecha')
            ->map(fn ($facturas, $dia) => [
                'dia' => $dia,
                'cantidad' => $facturas->count(),
                'total' => (float) $facturas->sum('total'),
            ])
            ->values();

        $produccionUsuariosPorDia = $ultimasProducciones
            ->whereBetween('fecha', [$inicioQuincena, $finQuincena])
            ->groupBy(fn ($produccion) => optional($produccion->fecha)->format('d/m') ?? 'Sin fecha')
            ->map(fn ($producciones, $dia) => [
                'dia' => $dia,
                'cantidad' => (int) $producciones->sum('cantidad'),
                'total' => (float) $producciones->sum('total_validado'),
            ])
            ->values();

        // ── Prendas por mes ───────────────────────────────────────────────────
        $resumenMensualPrendas = $this->resumenMensualPrendas();

        // ── Períodos cerrados (historial manual + cierres automáticos) ────────
        $periodosHistorial = HistorialProduccion::query()
            ->selectRaw('periodo, SUM(total) as total_general, SUM(cantidad) as total_prendas')
            ->groupBy('periodo')
            ->get()
            ->keyBy('periodo');

        $periodosRecolector = FacturaRecolector::query()
            ->whereNotNull('quincena_pago')
            ->where('estado_factura', 'pagado')
            ->selectRaw('quincena_pago as periodo, SUM(total) as total_facturas_rec, COUNT(*) as cant_facturas_rec')
            ->groupBy('quincena_pago')
            ->get()
            ->keyBy('periodo');

        $todosPeriodos = $periodosHistorial->keys()
            ->merge($periodosRecolector->keys())
            ->unique()
            ->values();

        $periodosCerrados = $todosPeriodos->map(function ($periodo) use ($periodosHistorial, $periodosRecolector) {
            $hist = $periodosHistorial->get($periodo);
            $rec = $periodosRecolector->get($periodo);

            return (object) [
                'periodo' => $periodo,
                'total_general' => (float) ($hist->total_general ?? 0) + (float) ($rec->total_facturas_rec ?? 0),
                'total_prendas' => (int) ($hist->total_prendas ?? 0),
                'tiene_historial' => $hist !== null,
                'tiene_facturas' => $rec !== null,
            ];
        })->sortByDesc('periodo')->values();

        // ── Incongruencias y notificaciones ──────────────────────────────────
        $incongruenciasPendientes = IncongruenciaRecolector::query()
            ->with(['recolector', 'factura'])
            ->where('estado', 'pendiente')
            ->orderByDesc('detectada_en')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $incongruenciasProduccionPendientes = IncongruenciaProduccion::query()
            ->with(['user', 'prenda', 'produccion'])
            ->where('estado', 'pendiente')
            ->orderByDesc('detectada_en')
            ->orderByDesc('id')
            ->take(15)
            ->get();

        $notificacionesIncongruencias = auth()->user()
            ->unreadNotifications()
            ->where('type', 'App\\Notifications\\IncongruenciaRecolectorDetectada')
            ->latest()
            ->take(10)
            ->get();

        // ── Panel programador ─────────────────────────────────────────────────
        $codigoEmpresarial = auth()->user()->esProgramador()
            ? $enterpriseCodes->current()
            : null;
        $dispositivosBloqueados = auth()->user()->esProgramador()
            ? $deviceAccess->lockedDevices()
            : collect();

        // ── Delegación y listados ─────────────────────────────────────────────
        $usuarios = User::query()->orderBy('name')->get();

        $recolectores = User::query()
            ->where('rol', 'recolector')
            ->where('activo', true)
            ->orderBy('name')
            ->get();

        $clientesConRecolector = Cliente::with('recolector')
            ->orderBy('nombre')
            ->get();

        $ingresosTotales = $ingresosProduccionActiva + FacturaRecolector::query()
            ->noCanceladas()
            ->whereBetween('fecha_ingreso', [$inicioQuincena, $finQuincena])
            ->sum('total');

        return view('admin.dashboard', compact(
            'totalUsuarios',
            'totalProducciones',
            'ingresosTotales',
            'ingresoRecolectoresActivo',
            'ordenesPagadasTotal',
            'ordenesPagadasCantidad',
            'pagoUsuarios',
            'ganancia',
            'recolectoresConFacturas',
            'total30PorCiento',
            'totalNeto',
            'totalFacturasQuincena',
            'gastosQuincena',
            'reportePagoQuincena',
            'periodoActual',
            'gastosRecientes',
            'incongruenciasPendientes',
            'incongruenciasProduccionPendientes',
            'notificacionesIncongruencias',
            'ultimasProducciones',
            'ultimasFacturasRecolector',
            'facturasRecolectorResumen',
            'facturaStatusResumen',
            'ingresoFacturasPorDia',
            'usuarios',
            'resumenMensualPrendas',
            'produccionUsuariosPorDia',
            'periodosCerrados',
            'codigoEmpresarial',
            'dispositivosBloqueados',
            'recolectores',
            'clientesConRecolector',
            'historialPagosRecolectores',
            'modoInterfazLavandero'
        ));
    }

    public function updateProduccionInterface(Request $request)
    {
        $data = $request->validate([
            'modo' => ['required', Rule::in(['basica', 'avanzada'])],
        ]);

        SystemSetting::setValue('produccion_interfaz_lavandero', $data['modo']);
        app(DashboardCacheService::class)->flush();

        return back()->with('success', 'Interfaz del lavandero actualizada correctamente.');
    }

    public function incongruencias()
    {
        $incongruencias = IncongruenciaRecolector::query()
            ->with(['recolector', 'cliente', 'factura'])
            ->orderByDesc('detectada_en')
            ->orderByDesc('id')
            ->paginate(40);

        return view('admin.incongruencias', [
            'incongruencias' => $incongruencias,
        ]);
    }

    public function markNotificationAsRead(string $notificationId)
    {
        $notification = auth()->user()->notifications()->whereKey($notificationId)->firstOrFail();
        $notification->markAsRead();

        return back()->with('success', 'Notificación marcada como leída.');
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'cedula' => ['nullable', 'string', 'max:50', 'unique:users,cedula'],
            'contacto' => ['nullable', 'string', 'max:50'],
            'rol' => ['required', 'in:admin,programador,usuario,recolector'],
            'activo' => ['nullable', 'boolean'],
            'puede_editar_precios' => ['nullable', 'boolean'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $rol = $this->resolverRol($data['rol']);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'cedula' => $data['cedula'] ?? null,
            'contacto' => $data['contacto'] ?? null,
            'rol' => $data['rol'],
            'rol_id' => $rol->id,
            'activo' => $request->boolean('activo', true),
            'puede_editar_precios' => $data['rol'] === 'recolector' && $request->boolean('puede_editar_precios'),
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('admin.dashboard')->with('success', 'Usuario creado correctamente.');
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'cedula' => ['nullable', 'string', 'max:50', Rule::unique('users', 'cedula')->ignore($user->id)],
            'contacto' => ['nullable', 'string', 'max:50'],
            'rol' => ['required', 'in:admin,programador,usuario,recolector'],
            'activo' => ['nullable', 'boolean'],
            'puede_editar_precios' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $rol = $this->resolverRol($data['rol']);

        // Detectar si cambian credenciales sensibles
        $emailCambiado = $user->email !== $data['email'];
        $passwordCambiada = ! empty($data['password']);
        $credencialesCambiadas = $emailCambiado || $passwordCambiada;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'cedula' => $data['cedula'] ?? null,
            'contacto' => $data['contacto'] ?? null,
            'rol' => $data['rol'],
            'rol_id' => $rol->id,
            'activo' => $request->boolean('activo', true),
            'puede_editar_precios' => $data['rol'] === 'recolector' && $request->boolean('puede_editar_precios'),
        ]);

        if ($passwordCambiada) {
            $user->password = Hash::make($data['password']);
        }

        if ($user->id === auth()->id() && ! $user->activo) {
            return redirect()->route('admin.dashboard')->with('error', 'No puedes inhabilitar tu propia cuenta desde aqui.');
        }

        $user->save();

        // Invalidar sesiones activas del usuario si cambiaron sus credenciales.
        // Si el admin se está editando a sí mismo, se conserva su sesión actual.
        if ($credencialesCambiadas && $user->id !== auth()->id()) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();
        }

        $mensaje = 'Usuario actualizado correctamente.';
        if ($credencialesCambiadas && $user->id !== auth()->id()) {
            $mensaje .= ' Las sesiones activas de este usuario han sido cerradas.';
        }

        return redirect()->route('admin.dashboard')->with('success', $mensaje);
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.dashboard')->with('error', 'No puedes eliminar tu propia cuenta desde aqui.');
        }

        $user->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Usuario eliminado correctamente.');
    }

    public function toggleUserStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.dashboard')->with('error', 'No puedes cambiar el estado de tu propia cuenta desde aqui.');
        }

        $user->activo = ! $user->activo;
        $user->save();

        return back()->with(
            'success',
            $user->activo ? 'Usuario habilitado correctamente.' : 'Usuario inhabilitado correctamente.'
        );
    }

    public function toggleRecolectorPriceEdit(User $user)
    {
        if (! $user->esRecolector()) {
            return back()->with('error', 'Solo los recolectores pueden tener permiso de editar precios.');
        }

        $user->puede_editar_precios = ! $user->puede_editar_precios;
        $user->save();

        return back()->with(
            'success',
            $user->puede_editar_precios
                ? 'Edición de precios habilitada para el recolector.'
                : 'Edición de precios inhabilitada para el recolector.'
        );
    }

    public function printReports(Request $request)
    {
        [$inicioQuincena, $finQuincena] = $this->rangoQuincenaActual();
        $tomadoHasta = now();

        $producciones = Produccion::with(['user', 'prenda'])
            ->orderBy('fecha')
            ->orderBy('user_id')
            ->orderBy('id')
            ->get();

        // Facturas pagadas en la quincena actual: filtramos por quincena_pago
        // (incluye órdenes de quincenas anteriores cobradas en esta quincena)
        $periodoActual = Gasto::periodoDesdeFecha(now())['periodo'];
        $facturasRecolector = FacturaRecolector::with(['recolector', 'cliente', 'detalles'])
            ->pagadasEnQuincena($periodoActual)
            ->orderBy('fecha_pago')
            ->orderBy('recolector_id')
            ->orderBy('id')
            ->get();

        $resumenUsuarios = $this->resumenProduccionUsuarios($producciones);
        $resumenRecolectores = $this->resumenRecolectores($facturasRecolector);

        $tipoReporte = $request->input('tipo_reporte', 'detallado');
        $grupo = $request->input('grupo', 'usuarios');
        $registroId = $request->filled('registro_id') ? (int) $request->input('registro_id') : null;

        $detalleUsuarios = $this->detalleProduccionUsuarios($producciones);
        $detalleRecolectores = $this->detalleFacturasRecolector($facturasRecolector);
        $resumenDiarioUsuarios = $this->resumenDiarioProduccionUsuarios($producciones);
        $resumenDiarioRecolectores = $this->resumenDiarioRecolectores($facturasRecolector);

        // Datos financieros para el resumen de impresión
        $gastosQuincena = Gasto::where('periodo', Gasto::periodoDesdeFecha(now())['periodo'])->sum('monto');

        // Buscamos las órdenes pagadas en esta quincena usando updated_at para incluir órdenes de quincenas pasadas que se pagaron ahora
        $ordenesPagadas = $facturasRecolector;
        $ordenesPagadasTotal = (float) $ordenesPagadas->sum('total');

        $totalNeto = $ordenesPagadasTotal - (float) $gastosQuincena;

        $resumen30Recolectores = $ordenesPagadas
            ->groupBy('recolector_id')
            ->map(function ($facturas) {
                $rec = $facturas->first()->recolector;
                $total = (float) $facturas->sum('total');

                return [
                    'nombre' => $rec?->name ?? 'Sin nombre',
                    'total' => $total,
                    'pago30' => round($total * 0.30),
                ];
            })->values();

        $total30 = $resumen30Recolectores->sum('pago30');
        $pagoUsuarios = $resumenUsuarios->sum('total');

        $ganancia = $totalNeto - $pagoUsuarios - $total30;

        if ($registroId) {
            $detalleUsuarios = $detalleUsuarios->where('id', $registroId)->values();
            $detalleRecolectores = $detalleRecolectores->where('id', $registroId)->values();
            $resumenDiarioUsuarios = $resumenDiarioUsuarios->where('id', $registroId)->values();
            $resumenDiarioRecolectores = $resumenDiarioRecolectores->where('id', $registroId)->values();
        }

        return view('admin.reportes-impresion', [
            'tipoReporte' => $tipoReporte,
            'grupo' => $grupo,
            'registroId' => $registroId,
            'tomadoHasta' => $tomadoHasta,
            'inicioQuincena' => $inicioQuincena,
            'finQuincena' => $finQuincena,
            'resumenUsuarios' => $resumenUsuarios,
            'resumenRecolectores' => $resumenRecolectores,
            'detalleUsuarios' => $detalleUsuarios,
            'detalleRecolectores' => $detalleRecolectores,
            'resumenDiarioUsuarios' => $resumenDiarioUsuarios,
            'resumenDiarioRecolectores' => $resumenDiarioRecolectores,
            'totalGeneralUsuarios' => $resumenUsuarios->sum('total'),
            'totalGeneralRecolectores' => $resumenRecolectores->sum('total'),
            'totalPrendasUsuarios' => $resumenUsuarios->sum('cantidad'),
            'totalPrendasRecolectores' => $resumenRecolectores->sum('cantidad'),
            'gastosQuincena' => $gastosQuincena,
            'ordenesPagadasTotal' => $ordenesPagadasTotal,
            'ganancia' => $ganancia,
            'resumen30Recolectores' => $resumen30Recolectores,
            'total30' => $total30,
            'totalNeto' => $totalNeto,
            'autoPrint' => $request->boolean('imprimir'),
        ]);
    }

    public function destroyProduccion(Produccion $produccion, ProduccionValidationService $validationService)
    {
        $fecha = $produccion->fecha?->toDateString();
        $produccion->delete();

        if ($fecha) {
            $validationService->recalcularFecha($fecha);
        }

        return redirect()->route('admin.dashboard')->with('success', 'Registro de usuario eliminado correctamente.');
    }

    public function destroyProducciones(Request $request, ProduccionValidationService $validationService)
    {
        $data = $request->validate([
            'produccion_ids' => ['required', 'array', 'min:1'],
            'produccion_ids.*' => ['integer', 'exists:producciones,id'],
        ]);

        $fechas = Produccion::query()
            ->whereIn('id', $data['produccion_ids'])
            ->pluck('fecha')
            ->filter()
            ->map(fn ($fecha) => Carbon::parse($fecha)->toDateString())
            ->unique();

        $deleted = Produccion::whereIn('id', $data['produccion_ids'])->delete();

        foreach ($fechas as $fecha) {
            $validationService->recalcularFecha($fecha);
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Se eliminaron {$deleted} registros de usuarios correctamente.");
    }

    public function destroyFacturaRecolector(FacturaRecolector $facturaRecolector)
    {
        DB::transaction(function () use ($facturaRecolector) {
            AuditEvent::query()->create([
                'actor_id' => auth()->id(),
                'auditable_type' => FacturaRecolector::class,
                'auditable_id' => $facturaRecolector->id,
                'action' => 'factura_recolector.deleted',
                'summary' => 'Factura de recolector eliminada desde panel administrativo.',
                'metadata' => [
                    'numero_orden' => $facturaRecolector->numero_orden,
                    'estado' => $facturaRecolector->estado,
                    'total' => $facturaRecolector->total,
                    'total_prendas' => $facturaRecolector->total_prendas,
                ],
            ]);

            $facturaRecolector->delete();
        });

        return redirect()->route('admin.dashboard')->with('success', 'Registro del recolector eliminado correctamente.');
    }

    public function destroyHistorial(HistorialProduccion $historialProduccion)
    {
        $historialProduccion->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Registro historico eliminado correctamente.');
    }

    // ── EDICIÓN DE FACTURAS DE RECOLECTOR ─────────────────────────────────────

    public function editFacturaRecolector(FacturaRecolector $facturaRecolector)
    {
        if ($facturaRecolector->estaPagada()) {
            return redirect()->route('admin.dashboard')->with('error', 'Las facturas pagadas no se pueden editar.');
        }

        $facturaRecolector->load(['cliente', 'detalles', 'recolector']);
        $clientes = Cliente::activos()->orderBy('nombre')->get();
        $prendas = RecolectorPrenda::activas()->orderBy('nombre')->get();

        return view('admin.facturas-recolector-edit', [
            'factura' => $facturaRecolector,
            'clientes' => $clientes,
            'prendas' => $prendas,
        ]);
    }

    public function updateFacturaRecolector(Request $request, FacturaRecolector $facturaRecolector)
    {
        if ($facturaRecolector->estaPagada()) {
            return redirect()->route('admin.dashboard')->with('error', 'Las facturas pagadas no se pueden editar.');
        }

        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'fecha_entrega' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'array'],
            'observaciones.*' => ['string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.prenda_id' => ['required', 'integer', 'exists:recolector_prendas,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        $cliente = Cliente::findOrFail($data['cliente_id']);

        $detalles = collect($data['items'])->map(function ($item) {
            $prenda = RecolectorPrenda::findOrFail($item['prenda_id']);
            $subtotal = $item['cantidad'] * $item['precio_unitario'];

            return [
                'recolector_prenda_id' => $prenda->id,
                'prenda_nombre' => $prenda->nombre,
                'valor_unitario' => (float) $item['precio_unitario'],
                'cantidad' => (int) $item['cantidad'],
                'subtotal' => $subtotal,
            ];
        });

        $totalPrendas = (int) $detalles->sum('cantidad');
        $totalFactura = $detalles->sum('subtotal');

        DB::transaction(function () use ($facturaRecolector, $cliente, $data, $detalles, $totalPrendas, $totalFactura) {
            $facturaRecolector->update([
                'cliente_id' => $cliente->id,
                'direccion' => $cliente->direccion,
                'numero_cliente' => $cliente->numero_cliente,
                'celular' => $cliente->celular,
                'fecha_entrega' => $data['fecha_entrega'] ?? null,
                'observaciones' => array_values($data['observaciones'] ?? []),
                'total_prendas' => $totalPrendas,
                'total' => $totalFactura,
            ]);

            // Reemplazar detalles por completo
            $facturaRecolector->detalles()->delete();
            $facturaRecolector->detalles()->createMany($detalles->all());
        });

        return redirect()->route('admin.dashboard')->with('success', 'Orden de recolector actualizada correctamente.');
    }

    public function updateFacturaEstado(Request $request, FacturaRecolector $facturaRecolector)
    {
        $data = $request->validate([
            'estado_factura' => ['required', 'in:pagado,pendiente,cancelado'],
            'metodo_pago' => ['nullable', 'required_if:estado_factura,pagado', 'in:efectivo,qr,nequi,llave_breve'],
        ]);

        $nuevoEstado = $data['estado_factura'];
        $usuario = $request->user();

        if ($facturaRecolector->estaPagada()) {
            return back()->with('error', 'Las facturas pagadas ya no se pueden modificar.');
        }

        if (($facturaRecolector->estaCancelada() || $nuevoEstado === 'cancelado') && ! $usuario->esAdmin()) {
            return back()->with('error', 'Solo el administrador puede cambiar facturas canceladas.');
        }

        // ── REGLA DE NEGOCIO: Reasignación de quincena al momento del pago ─────────
        //
        // Cuando se marca una factura como PAGADA:
        //  1. La quincena_origen permanece intacta (quincena en que se creó).
        //  2. Se asigna quincena_pago = quincena activa actual (donde ocurre el cobro).
        //  3. Se recalcula el 30% de comisión para el recolector en esa quincena de pago.
        //
        // Esto resuelve el caso en que una factura de una quincena cerrada se paga
        // en la quincena activa: el dinero queda en la quincena correcta.

        $camposActualizar = [
            'estado_factura' => $nuevoEstado,
            'metodo_pago' => $nuevoEstado === 'pagado' ? $data['metodo_pago'] : null,
        ];

        if ($nuevoEstado === 'pagado') {
            // Determinar la quincena activa actual (donde se efectúa el pago)
            $ahoraPago     = now();
            $periodoActivo = Gasto::periodoDesdeFecha($ahoraPago);
            $quincenaPago  = $periodoActivo['periodo'];

            $camposActualizar['quincena_pago'] = $quincenaPago;
            $camposActualizar['fecha_pago']    = $ahoraPago;  // timestamp exacto del cobro

            // Si la factura aún no tenía quincena_origen, la backfilleamos ahora
            if (empty($facturaRecolector->quincena_origen)) {
                $fechaIngreso = $facturaRecolector->fecha_ingreso ?? $ahoraPago;
                $camposActualizar['quincena_origen'] = Gasto::periodoDesdeFecha(
                    Carbon::parse($fechaIngreso)
                )['periodo'];
            }
        }

        DB::transaction(function () use ($facturaRecolector, $camposActualizar, $nuevoEstado) {
            $facturaRecolector->update($camposActualizar);

            if ($nuevoEstado === 'pagado') {
                PagoRecolector::recalcular(
                    recolectorId: (int) $facturaRecolector->recolector_id,
                    quincena: $camposActualizar['quincena_pago'],
                    porcentaje: 30.0
                );
            }
        });

        // Invalidar caché del dashboard (datos financieros cambiaron)
        app(DashboardCacheService::class)->flushFacturas();

        return back()->with('success', 'Estatus de factura actualizado correctamente.');
    }

    // ── EDICIÓN DE REGISTROS DE PRODUCCIÓN ────────────────────────────────────

    public function editProduccion(Produccion $produccion)
    {
        $produccion->load(['user', 'prenda']);
        $prendas = Prenda::orderBy('nombre')->get();
        $usuarios = User::orderBy('name')->get();

        return view('admin.produccion-edit', [
            'produccion' => $produccion,
            'prendas' => $prendas,
            'usuarios' => $usuarios,
        ]);
    }

    public function updateProduccion(Request $request, Produccion $produccion, ProduccionValidationService $validationService)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'prenda_id' => ['required', 'exists:prendas,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'fecha' => ['required', 'date'],
        ]);

        $prenda = Prenda::findOrFail($data['prenda_id']);
        $total = $data['cantidad'] * $prenda->precio;

        $produccion->update([
            'user_id' => $data['user_id'],
            'prenda_id' => $data['prenda_id'],
            'cantidad' => $data['cantidad'],
            'total' => $total,
            'fecha' => $data['fecha'],
            'cantidad_validada' => 0,
            'total_validado' => 0,
            'estado_validacion' => 'pendiente',
            'validado_en' => null,
        ]);

        $validationService->recalcularFecha($data['fecha']);

        // Invalidar caché del dashboard (producción cambió)
        app(DashboardCacheService::class)->flushProduccion();

        return redirect()->route('admin.dashboard')->with('success', 'Registro de producción actualizado correctamente.');
    }

    public function aprobarIncongruenciaProduccion(
        IncongruenciaProduccion $incongruenciaProduccion,
        ProduccionValidationService $validationService,
    ) {
        DB::transaction(function () use ($incongruenciaProduccion) {
            $incongruenciaProduccion->update([
                'estado' => 'aprobada',
                'aprobado_por' => auth()->id(),
                'aprobado_en' => now(),
            ]);

            $produccion = $incongruenciaProduccion->produccion;

            if ($produccion) {
                $precio = (float) ($produccion->prenda?->precio ?? 0);
                $produccion->update([
                    'cantidad_validada' => $produccion->cantidad,
                    'total_validado' => $precio * (int) $produccion->cantidad,
                    'estado_validacion' => 'aprobado',
                    'validado_en' => now(),
                ]);
            }
        });

        if ($incongruenciaProduccion->fecha) {
            $validationService->recalcularFecha($incongruenciaProduccion->fecha);
        }

        app(DashboardCacheService::class)->flushProduccion();

        return back()->with('success', 'Incongruencia de produccion aprobada correctamente.');
    }

    private function resolverRol(string $rol): Rol
    {
        $rolNormalizado = strtolower(trim($rol));

        $rolExistente = Rol::query()
            ->whereRaw('LOWER(nombre) = ?', [$rolNormalizado])
            ->first();

        if ($rolExistente) {
            return $rolExistente;
        }

        return Rol::create([
            'nombre' => ucfirst($rolNormalizado),
            'descripcion' => 'Rol '.$rolNormalizado.' del sistema',
        ]);
    }

    private function resumenMensualPrendas()
    {
        $activos = Produccion::with('prenda')
            ->whereYear('fecha', now()->year)
            ->whereMonth('fecha', now()->month)
            ->get()
            ->groupBy(fn ($item) => $item->prenda?->nombre ?? 'Sin nombre')
            ->map(fn ($items, $nombre) => [
                'prenda' => $nombre,
                'cantidad' => $items->sum('cantidad_validada'),
                'total' => $items->sum('total_validado'),
            ]);

        $historicos = HistorialProduccion::query()
            ->where('anio', now()->year)
            ->where('mes', now()->month)
            ->get()
            ->groupBy('prenda_nombre')
            ->map(fn ($items, $nombre) => [
                'prenda' => $nombre,
                'cantidad' => $items->sum('cantidad'),
                'total' => $items->sum('total'),
            ]);

        return $activos
            ->mergeRecursive($historicos)
            ->map(function ($item, $nombre) {
                $cantidad = is_array($item['cantidad']) ? array_sum($item['cantidad']) : $item['cantidad'];
                $total = is_array($item['total']) ? array_sum($item['total']) : $item['total'];

                return [
                    'prenda' => $nombre,
                    'cantidad' => $cantidad,
                    'total' => $total,
                ];
            })
            ->sortByDesc('cantidad')
            ->values();
    }

    private function resumenProduccionUsuarios(Collection $producciones): Collection
    {
        return $producciones
            ->groupBy('user_id')
            ->map(function (Collection $registros, $userId) {
                $usuario = $registros->first()->user;

                return [
                    'id' => (int) $userId,
                    'nombre' => $usuario?->name ?? 'Usuario eliminado',
                    'rol' => $usuario?->obtenerRol() ?? 'usuario',
                    'cantidad' => (int) $registros->sum('cantidad_validada'),
                    'total' => (float) $registros->sum('total_validado'),
                ];
            })
            ->sortBy('nombre')
            ->values();
    }

    private function resumenRecolectores(Collection $facturas): Collection
    {
        return $facturas
            ->groupBy('recolector_id')
            ->map(function (Collection $registros, $recolectorId) {
                $recolector = $registros->first()->recolector;

                return [
                    'id' => (int) $recolectorId,
                    'nombre' => $recolector?->name ?? 'Recolector eliminado',
                    'rol' => $recolector?->obtenerRol() ?? 'recolector',
                    'cantidad' => (int) $registros->sum('total_prendas'),
                    'total' => (float) $registros->sum('total_validado'),
                ];
            })
            ->sortBy('nombre')
            ->values();
    }

    private function detalleProduccionUsuarios(Collection $producciones): Collection
    {
        return $producciones
            ->groupBy('user_id')
            ->map(function (Collection $registros, $userId) {
                $usuario = $registros->first()->user;

                return [
                    'id' => (int) $userId,
                    'nombre' => $usuario?->name ?? 'Usuario eliminado',
                    'rol' => $usuario?->obtenerRol() ?? 'usuario',
                    'cedula' => $usuario?->cedula ?? 'No registrada',
                    'contacto' => $usuario?->contacto ?? 'No registrado',
                    'cantidad' => (int) $registros->sum('cantidad_validada'),
                    'total' => (float) $registros->sum('total'),
                    'registros' => $registros,
                ];
            })
            ->sortBy('nombre')
            ->values();
    }

    private function detalleFacturasRecolector(Collection $facturas): Collection
    {
        return $facturas
            ->groupBy('recolector_id')
            ->map(function (Collection $registros, $recolectorId) {
                $recolector = $registros->first()->recolector;

                return [
                    'id' => (int) $recolectorId,
                    'nombre' => $recolector?->name ?? 'Recolector eliminado',
                    'rol' => $recolector?->obtenerRol() ?? 'recolector',
                    'cedula' => $recolector?->cedula ?? 'No registrada',
                    'contacto' => $recolector?->contacto ?? 'No registrado',
                    'cantidad' => (int) $registros->sum('total_prendas'),
                    'total' => (float) $registros->sum('total'),
                    'registros' => $registros,
                ];
            })
            ->sortBy('nombre')
            ->values();
    }

    private function resumenDiarioProduccionUsuarios(Collection $producciones): Collection
    {
        return $producciones
            ->groupBy('user_id')
            ->map(function (Collection $registros, $userId) {
                $usuario = $registros->first()->user;
                $dias = $registros
                    ->groupBy(fn (Produccion $registro) => optional($registro->fecha)->toDateString() ?? 'Sin fecha')
                    ->map(function (Collection $registrosDia, string $fecha) {
                        $detalle = $registrosDia
                            ->groupBy(fn (Produccion $registro) => $registro->prenda?->nombre ?? 'Sin prenda')
                            ->map(function (Collection $registrosPrenda, string $nombrePrenda) {
                                return [
                                    'nombre' => $nombrePrenda,
                                    'cantidad' => (int) $registrosPrenda->sum('cantidad_validada'),
                                    'total' => (float) $registrosPrenda->sum('total_validado'),
                                ];
                            })
                            ->sortBy('nombre')
                            ->values();

                        return [
                            'fecha' => $fecha,
                            'cantidad' => (int) $registrosDia->sum('cantidad_validada'),
                            'total' => (float) $registrosDia->sum('total_validado'),
                            'detalle' => $detalle,
                        ];
                    })
                    ->sortBy('fecha')
                    ->values();

                return [
                    'id' => (int) $userId,
                    'nombre' => $usuario?->name ?? 'Usuario eliminado',
                    'rol' => $usuario?->obtenerRol() ?? 'usuario',
                    'dias' => $dias,
                    'cantidad' => (int) $registros->sum('cantidad_validada'),
                    'total' => (float) $registros->sum('total_validado'),
                ];
            })
            ->sortBy('nombre')
            ->values();
    }

    private function resumenDiarioRecolectores(Collection $facturas): Collection
    {
        return $facturas
            ->groupBy('recolector_id')
            ->map(function (Collection $registros, $recolectorId) {
                $recolector = $registros->first()->recolector;
                $dias = $registros
                    ->groupBy(fn (FacturaRecolector $factura) => optional($factura->fecha_ingreso)->toDateString() ?? 'Sin fecha')
                    ->map(function (Collection $registrosDia, string $fecha) {
                        $detalle = $registrosDia
                            ->map(function (FacturaRecolector $factura) {
                                return [
                                    'factura' => $factura->id,
                                    'cliente' => $factura->cliente?->nombre ?? 'Cliente eliminado',
                                    'cantidad' => (int) $factura->total_prendas,
                                    'total' => (float) $factura->total,
                                    'prendas' => $factura->detalles
                                        ->map(fn ($detalle) => $detalle->prenda_nombre.' x '.$detalle->cantidad)
                                        ->implode(', '),
                                ];
                            })
                            ->values();

                        return [
                            'fecha' => $fecha,
                            'cantidad' => (int) $registrosDia->sum('total_prendas'),
                            'total' => (float) $registrosDia->sum('total'),
                            'detalle' => $detalle,
                        ];
                    })
                    ->sortBy('fecha')
                    ->values();

                return [
                    'id' => (int) $recolectorId,
                    'nombre' => $recolector?->name ?? 'Recolector eliminado',
                    'rol' => $recolector?->obtenerRol() ?? 'recolector',
                    'dias' => $dias,
                    'cantidad' => (int) $registros->sum('total_prendas'),
                    'total' => (float) $registros->sum('total'),
                ];
            })
            ->sortBy('nombre')
            ->values();
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
}
