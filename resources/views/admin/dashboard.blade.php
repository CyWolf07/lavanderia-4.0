{{-- ====================================================
     Vista: Panel Administrativo
     Dashboard principal para roles admin y programador.
     Muestra estadísticas, gestión de usuarios, últimos
     registros de producción y quincenas cerradas.
     ====================================================  --}}
@extends('layouts.app')

@section('title', 'Panel administrativo')

@section('content')
<div
    x-data="{
        paymentOpen: false,
        cancelOpen: false,
        orderSummaryOpen: false,
        delegarOpen: false,
        selectedProducciones: [],
        selectedOrder: '',
        paymentAction: '',
        cancelAction: '',
        selectedOrderSummary: {},
        delegarClienteId: null,
        delegarClienteNombre: '',
        delegarAction: '',
        allProduccionesSelected(ids) {
            return ids.length > 0 && ids.every((id) => this.selectedProducciones.includes(String(id)));
        },
        toggleAllProducciones(ids, checked) {
            this.selectedProducciones = checked ? ids.map(String) : [];
        },
        openPayment(action, order) {
            this.paymentAction = action;
            this.selectedOrder = order;
            this.paymentOpen = true;
        },
        openCancel(action, order) {
            this.cancelAction = action;
            this.selectedOrder = order;
            this.cancelOpen = true;
        },
        openOrderSummary(data) {
            this.selectedOrderSummary = data;
            this.orderSummaryOpen = true;
        },
        openDelegar(clienteId, clienteNombre, action) {
            this.delegarClienteId = clienteId;
            this.delegarClienteNombre = clienteNombre;
            this.delegarAction = action;
            this.delegarOpen = true;
        },
        copyEnterpriseCode(code) {
            navigator.clipboard.writeText(code);
        }
    }"
    class="mx-auto max-w-screen-2xl space-y-8 px-4 py-8 sm:px-6 lg:px-8"
>

    {{-- Encabezado principal con acciones rápidas --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Panel administrativo</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Gestión general del sistema</h1>
            <p class="mt-2 text-sm text-slate-500">
                Administra usuarios, revisa la producción mensual, cierra quincenas y genera el informe de cada empleado con un clic.
            </p>
        </div>

        {{-- Botones de acceso rápido a módulos --}}
        <div class="hidden">
            <a href="{{ route('produccion.index') }}" class="rounded-full border border-sky-200 bg-sky-50 px-5 py-3 text-sm font-semibold text-sky-700 hover:bg-sky-100">
                Ingresar a produccion
            </a>
            {{-- Navegar a gestión de prendas de producción --}}
            <a href="{{ route('prendas.index') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Gestionar prendas
            </a>
            {{-- Navegar a gestión de clientes --}}
            <a href="{{ route('clientes.index') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Gestionar clientes
            </a>
            {{-- Navegar a prendas del módulo recolector --}}
            <a href="{{ route('recolector-prendas.index') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Prendas recolector
            </a>
            <a href="{{ route('admin.incongruencias.index') }}" class="rounded-full border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                Informe incongruencias
            </a>
            {{-- Imprimir resumen de quincena directamente desde el navegador --}}
            <a href="{{ route('admin.reportes.impresion', ['tipo_reporte' => 'resumen_diario', 'imprimir' => 1]) }}" target="_blank" class="rounded-full border border-sky-200 bg-sky-50 px-5 py-3 text-sm font-semibold text-sky-700 hover:bg-sky-100">
                Imprimir resumen
            </a>
            {{-- Cerrar la quincena activa y generar el historial --}}
            <form action="{{ route('produccion.cerrar') }}" method="POST">
                @csrf
                <button type="submit" onclick="return confirm('¿Cerrar la quincena actual y generar informe imprimible?')" class="rounded-full bg-rose-600 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-700">
                    Cerrar quincena
                </button>
            </form>
        </div>
    </div>

    {{-- Mensajes flash de sesión --}}
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    @if (auth()->user()->esProgramador())
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(22rem,28rem)]">
            <div class="min-w-0 rounded-[1.75rem] bg-slate-900 p-5 text-white shadow-xl sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-sky-200">Codigo empresarial</p>
                        <p class="mt-3 break-all rounded-2xl border border-white/10 bg-white/10 px-4 py-3 font-mono text-lg font-black text-white">
                            {{ $codigoEmpresarial }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        <button
                            type="button"
                            @click="copyEnterpriseCode('{{ $codigoEmpresarial }}')"
                            class="rounded-2xl bg-white px-4 py-3 text-sm font-bold text-slate-900 hover:bg-sky-50"
                        >
                            Copiar
                        </button>
                        <form action="{{ route('admin.codigo-empresarial.regenerate') }}" method="POST">
                            @csrf
                            <button
                                onclick="return confirm('Regenerar el codigo empresarial cerrara todas las sesiones activas. Deseas continuar?')"
                                class="rounded-2xl border border-amber-500 bg-amber-600 px-4 py-3 text-sm font-bold text-white hover:bg-amber-700"
                            >
                                Regenerar
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="min-w-0 rounded-[1.75rem] bg-white p-5 shadow-xl ring-1 ring-slate-200 sm:p-6">
                <h2 class="text-lg font-bold text-slate-900">Dispositivos bloqueados</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($dispositivosBloqueados as $bloqueo)
                        <div class="rounded-2xl border border-slate-200 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                                        {{ $bloqueo->area === 'enterprise_code' ? 'Codigo empresarial' : 'Inicio de sesion' }}
                                    </p>
                                    <p class="mt-1 truncate font-mono text-xs text-slate-500">{{ $bloqueo->device_id }}</p>
                                    <p class="mt-1 text-xs text-rose-600">{{ optional($bloqueo->locked_at)->format('d/m/Y H:i') }}</p>
                                </div>
                                <form action="{{ route('admin.dispositivos.unlock', $bloqueo) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-full border border-emerald-200 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-50">
                                        Desbloquear
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-slate-50 px-4 py-4 text-sm text-slate-500">No hay dispositivos bloqueados.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- Tarjetas de estadísticas globales --}}
    <div class="grid min-w-0 gap-6 xl:grid-cols-[260px_minmax(0,1fr)]">
        <aside class="xl:sticky xl:top-24 xl:self-start">
            <div class="rounded-[1.5rem] border border-sky-100 bg-white/90 p-3 shadow-xl shadow-sky-100">
                <nav class="grid gap-3">
                    <a href="{{ route('produccion.index') }}" class="rounded-[1.35rem] border border-sky-200 bg-sky-50 px-5 py-4 text-sm font-bold text-sky-700 shadow-sm hover:-translate-y-0.5 hover:bg-sky-100">Ingresar a produccion</a>
                    <a href="{{ route('prendas.index') }}" class="rounded-[1.35rem] border border-slate-200 bg-white px-5 py-4 text-sm font-bold text-slate-700 shadow-sm hover:-translate-y-0.5 hover:bg-slate-50">Gestionar prendas</a>
                    <a href="{{ route('clientes.index') }}" class="rounded-[1.35rem] border border-slate-200 bg-white px-5 py-4 text-sm font-bold text-slate-700 shadow-sm hover:-translate-y-0.5 hover:bg-slate-50">Gestionar clientes</a>
                    <a href="#delegacion-clientes" class="rounded-[1.35rem] border border-violet-200 bg-violet-50 px-5 py-4 text-sm font-bold text-violet-700 shadow-sm hover:-translate-y-0.5 hover:bg-violet-100">👥 Delegación de clientes</a>
                    <a href="{{ route('recolector-prendas.index') }}" class="rounded-[1.35rem] border border-slate-200 bg-white px-5 py-4 text-sm font-bold text-slate-700 shadow-sm hover:-translate-y-0.5 hover:bg-slate-50">Prendas recolector</a>
                    {{-- F6: Botón mapa de clientes --}}
                    <a href="{{ route('admin.mapa-clientes') }}" class="rounded-[1.35rem] border border-violet-200 bg-violet-50 px-5 py-4 text-sm font-bold text-violet-700 shadow-sm hover:-translate-y-0.5 hover:bg-violet-100">🗺 Mapa de clientes</a>
                    <a href="{{ route('admin.incongruencias.index') }}" class="rounded-[1.35rem] border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700 shadow-sm hover:-translate-y-0.5 hover:bg-rose-100">Informe incongruencias</a>
                    <a href="{{ route('admin.reportes.impresion', ['tipo_reporte' => 'resumen_diario', 'imprimir' => 1]) }}" target="_blank" class="rounded-[1.35rem] border border-sky-200 bg-white px-5 py-4 text-sm font-bold text-sky-700 shadow-sm hover:-translate-y-0.5 hover:bg-sky-50">Imprimir resumen</a>
                    <form action="{{ route('produccion.cerrar') }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('Cerrar la quincena actual y generar informe imprimible?')" class="w-full rounded-[1.35rem] bg-rose-600 px-5 py-4 text-sm font-black text-white shadow-lg shadow-rose-100 hover:-translate-y-0.5 hover:bg-rose-700">
                            Cerrar quincena
                        </button>
                    </form>
                </nav>
            </div>
        </aside>

        <section class="min-w-0 space-y-8">
	    <div class="grid min-w-0 gap-5 [grid-template-columns:repeat(auto-fit,minmax(13rem,1fr))]">
        {{-- Total de usuarios registrados en el sistema --}}
        <div class="min-w-0 rounded-[1.75rem] bg-slate-900 p-5 text-white shadow-xl sm:p-6">
            <p class="break-words text-xs font-semibold uppercase tracking-[0.18em] text-slate-300 sm:text-sm sm:tracking-[0.22em]">Usuarios registrados</p>
            <p class="mt-3 break-words text-4xl font-black">{{ $totalUsuarios }}</p>
        </div>
        {{-- Registros de producción activos (quincena actual) --}}
        <div class="min-w-0 rounded-[1.75rem] bg-sky-600 p-5 text-white shadow-xl sm:p-6">
            <p class="break-words text-xs font-semibold uppercase tracking-[0.18em] text-sky-100 sm:text-sm sm:tracking-[0.22em]">Registros activos</p>
            <p class="mt-3 break-words text-4xl font-black">{{ $totalProducciones }}</p>
        </div>
        {{-- Ingreso activo = SOLO lo que ingresan los recolectores (ordenes no pagadas) --}}
        <div class="min-w-0 rounded-[1.75rem] bg-emerald-600 p-5 text-white shadow-xl sm:p-6">
            <p class="break-words text-xs font-semibold uppercase tracking-[0.18em] text-emerald-100 sm:text-sm sm:tracking-[0.22em]">Ingreso activo</p>
            <p class="mt-3 break-words text-3xl font-black sm:text-4xl">$ {{ number_format($ingresoRecolectoresActivo, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-emerald-200">Órdenes pendientes de pago</p>
        </div>
	    </div>

        {{-- ─── NUEVOS PANELES FINANCIEROS (ORDEN SOLICITADO POR USUARIO) ─────────────────────────────── --}}
        <div class="grid min-w-0 gap-4 [grid-template-columns:repeat(auto-fit,minmax(11rem,1fr))]">
            {{-- 1. Órdenes Pagadas --}}
            <div class="min-w-0 rounded-[1.75rem] bg-blue-600 p-5 text-white shadow-xl sm:p-6">
                <p class="break-words text-xs font-semibold uppercase tracking-[0.16em] text-blue-100">Órdenes Pagadas</p>
                <p class="mt-2 break-words text-2xl font-black sm:text-3xl">$ {{ number_format($ordenesPagadasTotal, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-blue-200">{{ $ordenesPagadasCantidad }} órdenes cobradas</p>
            </div>
            {{-- 2. Gastos --}}
            <div class="min-w-0 rounded-[1.75rem] bg-rose-600 p-5 text-white shadow-xl sm:p-6">
                <p class="break-words text-xs font-semibold uppercase tracking-[0.16em] text-rose-100">Gastos</p>
                <p class="mt-2 break-words text-2xl font-black sm:text-3xl">$ {{ number_format($gastosQuincena, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-rose-200">Gastos de quincena</p>
            </div>
            {{-- 3. Total Neto = Órdenes Pagadas - Gastos --}}
            <div class="min-w-0 rounded-[1.75rem] @if ($totalNeto >= 0) bg-indigo-700 @else bg-red-700 @endif p-5 text-white shadow-xl sm:p-6">
                <p class="break-words text-xs font-semibold uppercase tracking-[0.16em] opacity-80">Total Neto</p>
                <p class="mt-2 break-words text-2xl font-black sm:text-3xl">$ {{ number_format($totalNeto, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs opacity-75">Pagadas − Gastos</p>
            </div>
            {{-- 4. Pago Usuarios --}}
            <div class="min-w-0 rounded-[1.75rem] bg-slate-700 p-5 text-white shadow-xl sm:p-6">
                <p class="break-words text-xs font-semibold uppercase tracking-[0.16em] text-slate-300">Pago Usuarios</p>
                <p class="mt-2 break-words text-2xl font-black sm:text-3xl">$ {{ number_format($pagoUsuarios, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-400">Total a lavanderos</p>
            </div>
            {{-- 5. Pago Recolectores (30%) --}}
            <div class="min-w-0 rounded-[1.75rem] bg-amber-500 p-5 text-white shadow-xl sm:p-6">
                <p class="break-words text-xs font-semibold uppercase tracking-[0.16em] text-amber-100">Pago Recolectores</p>
                <p class="mt-2 break-words text-2xl font-black sm:text-3xl">$ {{ number_format($total30PorCiento, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-amber-100">30% de órdenes pagadas</p>
            </div>
            {{-- 6. Total Ganancia = Total Neto - Pago Usuarios - Pago Recolectores --}}
            <div class="min-w-0 rounded-[1.75rem] @if ($ganancia >= 0) bg-teal-600 @else bg-orange-600 @endif p-5 text-white shadow-xl sm:p-6">
                <p class="break-words text-xs font-semibold uppercase tracking-[0.16em] opacity-80">Total Ganancia</p>
                <p class="mt-2 break-words text-2xl font-black sm:text-3xl">$ {{ number_format($ganancia, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs opacity-75">Neto − U. − R.</p>
            </div>
        </div>

        @php
            $maxFacturasDia = max(1, $ingresoFacturasPorDia->max('cantidad') ?? 1);
            $maxProduccionDia = max(1, $produccionUsuariosPorDia->max('cantidad') ?? 1);
            $estadoClases = [
                'pagado' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                'pendiente' => 'bg-amber-100 text-amber-700 ring-amber-200',
                'cancelado' => 'bg-rose-100 text-rose-700 ring-rose-200',
            ];
        @endphp

        <div class="grid min-w-0 gap-5 2xl:grid-cols-2">
            <div class="min-w-0 rounded-[1.75rem] bg-white p-5 shadow-xl ring-1 ring-slate-200 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Ingreso de facturas</h2>
                        <p class="mt-1 text-sm text-slate-500">Cantidad registrada por dia en la quincena.</p>
                    </div>
                    <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-sky-700 ring-1 ring-sky-100">{{ $periodoActual }}</span>
                </div>
                <div class="mt-6 flex h-56 items-end gap-3 border-b border-slate-200">
                    @forelse ($ingresoFacturasPorDia as $dia)
                        <div class="flex min-w-10 flex-1 flex-col items-center justify-end gap-2">
                            <div class="w-full rounded-t-xl bg-sky-500" style="height: {{ max(8, ($dia['cantidad'] / $maxFacturasDia) * 190) }}px"></div>
                            <span class="text-xs font-semibold text-slate-500">{{ $dia['dia'] }}</span>
                        </div>
                    @empty
                        <div class="flex h-full w-full items-center justify-center text-sm text-slate-500">Sin facturas en esta quincena.</div>
                    @endforelse
                </div>
            </div>

            <div class="min-w-0 rounded-[1.75rem] bg-white p-5 shadow-xl ring-1 ring-slate-200 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Sistema de usuarios</h2>
                        <p class="mt-1 text-sm text-slate-500">Prendas ingresadas por dia en la quincena.</p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700 ring-1 ring-emerald-100">{{ $totalProducciones }} registros</span>
                </div>
                <div class="mt-6 flex h-56 items-end gap-3 border-b border-slate-200">
                    @forelse ($produccionUsuariosPorDia as $dia)
                        <div class="flex min-w-10 flex-1 flex-col items-center justify-end gap-2">
                            <div class="w-full rounded-t-xl bg-emerald-500" style="height: {{ max(8, ($dia['cantidad'] / $maxProduccionDia) * 190) }}px"></div>
                            <span class="text-xs font-semibold text-slate-500">{{ $dia['dia'] }}</span>
                        </div>
                    @empty
                        <div class="flex h-full w-full items-center justify-center text-sm text-slate-500">Sin produccion en esta quincena.</div>
                    @endforelse
                </div>
            </div>
        </div>

	    <div class="min-w-0 rounded-[1.75rem] bg-white p-5 shadow-xl ring-1 ring-slate-200 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Gastos y reporte de pago quincenal</h2>
                <p class="mt-1 text-sm text-slate-500">Disponible para admin y programador. Fórmula: total facturas metidas - gastos = reporte de pago.</p>
                <p class="mt-2 text-xs uppercase tracking-[0.22em] text-slate-400">{{ $periodoActual }}</p>
            </div>
            <form action="{{ route('admin.gastos.store') }}" method="POST" class="grid w-full gap-3 xl:max-w-2xl xl:grid-cols-[minmax(0,1fr)_minmax(8rem,11rem)_auto]">
                @csrf
                <input name="concepto" type="text" placeholder="Concepto del gasto" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                <input name="monto" type="number" min="0.01" step="0.01" placeholder="Monto" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                <button class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Registrar gasto</button>
            </form>
        </div>

        <div class="mt-5 grid min-w-0 gap-3 [grid-template-columns:repeat(auto-fit,minmax(11rem,1fr))]">
            <div class="min-w-0 rounded-2xl bg-slate-50 px-4 py-4 ring-1 ring-slate-200">
                <p class="break-words text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Facturas quincena</p>
                <p class="mt-2 break-words text-2xl font-black text-slate-900">$ {{ number_format($totalFacturasQuincena, 0, ',', '.') }}</p>
            </div>
            <div class="min-w-0 rounded-2xl bg-rose-50 px-4 py-4 ring-1 ring-rose-200">
                <p class="break-words text-xs font-semibold uppercase tracking-[0.16em] text-rose-600">Gastos quincena</p>
                <p class="mt-2 break-words text-2xl font-black text-rose-700">$ {{ number_format($gastosQuincena, 0, ',', '.') }}</p>
            </div>
            <div class="min-w-0 rounded-2xl bg-emerald-50 px-4 py-4 ring-1 ring-emerald-200">
                <p class="break-words text-xs font-semibold uppercase tracking-[0.16em] text-emerald-600">Reporte de pago</p>
                <p class="mt-2 break-words text-2xl font-black text-emerald-700">$ {{ number_format($reportePagoQuincena, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200">
            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">Últimos gastos del periodo</div>
            <div class="divide-y divide-slate-100">
                @forelse ($gastosRecientes as $gasto)
                    <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $gasto->concepto }}</p>
                            <p class="text-slate-500">{{ $gasto->user->name ?? 'Usuario eliminado' }} | {{ optional($gasto->fecha)->format('d/m/Y') }}</p>
                        </div>
                        <p class="font-semibold text-rose-700">$ {{ number_format($gasto->monto, 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="px-4 py-4 text-sm text-slate-500">No hay gastos registrados en esta quincena.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid min-w-0 gap-6 2xl:grid-cols-2">
        <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">Notificaciones de incongruencias</h2>
                <p class="mt-1 text-sm text-slate-500">Se generan automáticamente cuando el sistema detecta datos que no concuerdan.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($notificacionesIncongruencias as $notificacion)
                    <div class="px-6 py-4">
                        <p class="text-sm font-semibold text-rose-700">{{ $notificacion->data['titulo'] ?? 'Incongruencia detectada' }}</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $notificacion->data['detalle'] ?? '' }}</p>
                        <p class="mt-1 text-xs text-slate-500">Usuario: {{ $notificacion->data['recolector'] ?? 'No disponible' }}</p>
                        <form action="{{ route('admin.notificaciones.read', $notificacion->id) }}" method="POST" class="mt-2">
                            @csrf
                            @method('PATCH')
                            <button class="rounded-full border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                Marcar como leída
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="px-6 py-6 text-sm text-slate-500">No hay notificaciones pendientes.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">Incongruencias pendientes</h2>
                <p class="mt-1 text-sm text-slate-500">Nombre del usuario, título del error y detalle para corrección rápida.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($incongruenciasPendientes as $item)
                    <div class="px-6 py-4">
                        <p class="text-sm font-semibold text-slate-900">{{ $item->recolector->name ?? 'Recolector eliminado' }} | Factura #{{ str_pad((string) $item->factura_recolector_id, 6, '0', STR_PAD_LEFT) }}</p>
                        <p class="mt-1 text-sm font-semibold text-rose-700">{{ $item->titulo }}</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $item->detalle }}</p>
                    </div>
                @empty
                    <p class="px-6 py-6 text-sm text-slate-500">No hay incongruencias pendientes.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid min-w-0 gap-8 2xl:grid-cols-[minmax(360px,420px)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-8">

            {{-- Formulario: Crear nuevo usuario --}}
            <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Crear usuario</h2>
                <p class="mt-1 text-sm text-slate-500">Desde aquí defines rol, cédula, contacto y contraseña.</p>

                <form action="{{ route('admin.usuarios.store') }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    {{-- Datos personales del nuevo usuario --}}
                    <input name="name" type="text" placeholder="Nombre completo" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    <input name="email" type="email" placeholder="Correo" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    <input name="cedula" type="text" placeholder="Cédula" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                    <input name="contacto" type="text" placeholder="Contacto" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">

                    {{-- Rol del usuario: determina qué módulos puede ver --}}
                    <select name="rol" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                        <option value="usuario">Usuario</option>
                        <option value="recolector">Recolector</option>
                        <option value="admin">Administrador</option>
                        <option value="programador">Programador</option>
                    </select>

                    {{-- Contraseña con botón ojo --}}
                    <div x-data="{ showP: false, showPC: false }" class="space-y-3">
                        <div class="relative">
                            <input id="create-pwd" name="password" :type="showP ? 'text' : 'password'"
                                   placeholder="Contraseña"
                                   class="w-full rounded-2xl border border-slate-300 px-4 py-3 pr-12 text-sm" required>
                            <button type="button" @click="showP = !showP" tabindex="-1"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center px-1 text-slate-400 hover:text-slate-700" aria-label="Ver contraseña">
                                <svg x-show="!showP" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showP" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                        <div class="relative">
                            <input id="create-pwd-c" name="password_confirmation" :type="showPC ? 'text' : 'password'"
                                   placeholder="Confirmar contraseña"
                                   class="w-full rounded-2xl border border-slate-300 px-4 py-3 pr-12 text-sm" required>
                            <button type="button" @click="showPC = !showPC" tabindex="-1"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center px-1 text-slate-400 hover:text-slate-700" aria-label="Ver confirmación">
                                <svg x-show="!showPC" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showPC" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>

                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        Guardar usuario
                    </button>
                </form>
            </div>

            {{-- Resumen de producción mensual por tipo de prenda --}}
            <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Prendas por mes</h2>
                <p class="mt-1 text-sm text-slate-500">Suma prendas del mes actual incluyendo periodos ya cerrados.</p>

                <div class="mt-4 space-y-3">
                    @forelse ($resumenMensualPrendas as $item)
                        <div class="rounded-2xl border border-slate-200 px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $item['prenda'] }}</p>
                                    <p class="text-sm text-slate-500">{{ $item['cantidad'] }} prendas</p>
                                </div>
                                <p class="text-sm font-semibold text-emerald-700">$ {{ number_format($item['total'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        {{-- Estado vacío: sin producción en el mes --}}
                        <p class="text-sm text-slate-500">Aún no hay producción registrada este mes.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-8">

            {{-- Tabla de usuarios con opción de editar datos y rol --}}
            <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">Usuarios registrados</h2>
                    <p class="mt-1 text-sm text-slate-500">Nombre, cédula, contacto y rol con opción de editar o borrar.</p>
                </div>
                <div class="space-y-4 p-6">
                    @forelse ($usuarios as $usuario)
                        <div class="rounded-[1.5rem] border border-slate-200 p-4">

                            {{-- Formulario de edición del usuario --}}
                            <form action="{{ route('admin.usuarios.update', $usuario) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="grid gap-3 md:grid-cols-2">
                                    <input name="name" type="text" value="{{ $usuario->name }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                                    <input name="email" type="email" value="{{ $usuario->email }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                                    <input name="cedula" type="text" value="{{ $usuario->cedula }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                                    <input name="contacto" type="text" value="{{ $usuario->contacto }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                                    {{-- Selector de rol con el rol actual preseleccionado --}}
                                    <select name="rol" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                                        <option value="usuario" @selected($usuario->obtenerRol() === 'usuario')>Usuario</option>
                                        <option value="recolector" @selected($usuario->obtenerRol() === 'recolector')>Recolector</option>
                                        <option value="admin" @selected($usuario->obtenerRol() === 'admin')>Administrador</option>
                                        <option value="programador" @selected($usuario->obtenerRol() === 'programador')>Programador</option>
                                    </select>
                                    {{-- Campo opcional para cambiar la contraseña --}}
                                    <div x-data="{ showEP: false, showEPC: false }" class="contents">
                                        <div class="relative">
                                            <input name="password" :type="showEP ? 'text' : 'password'"
                                                   placeholder="Nueva contraseña opcional"
                                                   class="w-full rounded-2xl border border-slate-300 px-4 py-3 pr-12 text-sm">
                                            <button type="button" @click="showEP = !showEP" tabindex="-1"
                                                    class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center px-1 text-slate-400 hover:text-slate-700" aria-label="Ver contraseña">
                                                <svg x-show="!showEP" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <svg x-show="showEP" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                            </button>
                                        </div>
                                        <div class="relative md:col-span-2">
                                            <input name="password_confirmation" :type="showEPC ? 'text' : 'password'"
                                                   placeholder="Confirmar nueva contraseña"
                                                   class="w-full rounded-2xl border border-slate-300 px-4 py-3 pr-12 text-sm">
                                            <button type="button" @click="showEPC = !showEPC" tabindex="-1"
                                                    class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center px-1 text-slate-400 hover:text-slate-700" aria-label="Ver confirmación">
                                                <svg x-show="!showEPC" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <svg x-show="showEPC" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="text-sm text-slate-500">
                                        {{ $usuario->name }} | {{ $usuario->cedula ?: 'Sin cédula' }} | {{ $usuario->contacto ?: 'Sin contacto' }}
                                    </div>
                                    <button class="rounded-full bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                                        Guardar cambios
                                    </button>
                                </div>
                            </form>

                            {{-- Formulario de eliminación del usuario --}}
                            <form action="{{ route('admin.usuarios.destroy', $usuario) }}" method="POST" class="mt-3">
                                @csrf
                                @method('DELETE')
                                <button onclick="return confirm('¿Eliminar este usuario?')" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No hay usuarios registrados.</p>
                    @endforelse
                </div>
            </div>

            {{-- ─── F1: DELEGACIÓN DE CLIENTES A RECOLECTORES ─────────────────── --}}
            <div id="delegacion-clientes" class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Delegación de clientes</h2>
                            <p class="mt-1 text-sm text-slate-500">Asigna o reasigna cada cliente a un recolector específico. Los clientes sin asignar son visibles solo para el admin.</p>
                        </div>
                        <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-700 ring-1 ring-violet-200">{{ $clientesConRecolector->count() }} clientes</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-6 py-4 font-semibold">N°</th>
                                <th class="px-6 py-4 font-semibold">Nombre</th>
                                <th class="px-6 py-4 font-semibold">Barrio</th>
                                <th class="px-6 py-4 font-semibold">Celular</th>
                                <th class="px-6 py-4 font-semibold">Recolector asignado</th>
                                <th class="px-6 py-4 font-semibold">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($clientesConRecolector as $cli)
                                <tr>
                                    <td class="px-6 py-4">
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800"># {{ $cli->numero_cliente }}</span>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-slate-900">{{ $cli->nombre }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $cli->barrio ?: '—' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $cli->celular ?: '—' }}</td>
                                    <td class="px-6 py-4">
                                        @if ($cli->recolector)
                                            <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-800">{{ $cli->recolector->name }}</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <button
                                            type="button"
                                            @click="openDelegar({{ $cli->id }}, '{{ addslashes($cli->nombre) }}', '{{ route('clientes.delegar', $cli) }}')"
                                            class="rounded-full border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100"
                                        >
                                            Asignar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">No hay clientes registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tabla de últimos registros de producción activos --}}

            <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                @php($produccionIds = $ultimasProducciones->pluck('id')->values())
                <form id="bulk-delete-producciones" action="{{ route('admin.produccion.destroy-bulk') }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Registros activos de usuarios</h2>
                        <p class="mt-1 text-sm text-slate-500">Produccion pendiente de cierre con opcion de borrar si hubo error.</p>
                    </div>
                    @if ($ultimasProducciones->isNotEmpty())
                        <button
                            type="submit"
                            form="bulk-delete-producciones"
                            x-bind:disabled="selectedProducciones.length === 0"
                            onclick="return confirm('Eliminar los registros de produccion seleccionados?')"
                            class="rounded-full border border-rose-200 px-4 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400 disabled:hover:bg-transparent"
                        >
                            Eliminar seleccionados
                        </button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="w-12 px-4 py-4 text-center font-semibold">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-rose-600"
                                        x-bind:checked="allProduccionesSelected(@js($produccionIds))"
                                        @change="toggleAllProducciones(@js($produccionIds), $event.target.checked)"
                                        aria-label="Seleccionar todos los registros activos de usuarios"
                                    >
                                </th>
                                <th class="min-w-36 px-4 py-4 font-semibold">Usuario</th>
                                <th class="min-w-36 px-4 py-4 font-semibold">Prenda</th>
                                <th class="w-24 px-4 py-4 text-center font-semibold">Cantidad</th>
                                <th class="w-28 px-4 py-4 font-semibold">Total</th>
                                <th class="min-w-40 px-4 py-4 font-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($ultimasProducciones as $item)
                                <tr>
                                    <td class="px-4 py-4 text-center">
                                        <input
                                            form="bulk-delete-producciones"
                                            type="checkbox"
                                            name="produccion_ids[]"
                                            value="{{ $item->id }}"
                                            x-model="selectedProducciones"
                                            class="h-4 w-4 rounded border-slate-300 text-rose-600"
                                            aria-label="Seleccionar registro de produccion {{ $item->id }}"
                                        >
                                    </td>
                                    <td class="px-4 py-4 font-medium leading-tight text-slate-900">{{ $item->user->name ?? 'Sin usuario' }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ $item->prenda->nombre ?? 'Sin prenda' }}</td>
                                    <td class="px-4 py-4 text-center text-slate-600">{{ $item->cantidad }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 font-semibold text-emerald-700">$ {{ number_format($item->total, 0, ',', '.') }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('admin.produccion.edit', $item) }}"
                                               class="rounded-full border border-sky-200 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-50">
                                                Editar
                                            </a>
                                            <form action="{{ route('admin.produccion.destroy', $item) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button onclick="return confirm('Eliminar este registro de produccion?')" class="rounded-full border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">No hay producción activa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tabla de ultimos registros activos de recolectores --}}
	            <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
	                <div class="border-b border-slate-200 px-6 py-5">
	                    <h2 class="text-lg font-bold text-slate-900">Estatus factura</h2>
	                    <p class="mt-1 text-sm text-slate-500">Facturas metidas por recolector con orden, cliente, total y estado de pago.</p>
	                </div>
                    <div class="grid gap-3 p-6 pb-0 md:grid-cols-3">
                        @foreach (['pendiente' => 'Pendientes', 'pagado' => 'Pagadas', 'cancelado' => 'Canceladas'] as $estado => $label)
                            @php($resumenEstado = $facturaStatusResumen->get($estado))
                            <div class="rounded-2xl px-4 py-3 ring-1 {{ $estadoClases[$estado] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                <p class="text-xs font-bold uppercase tracking-[0.18em]">{{ $label }}</p>
                                <p class="mt-1 text-2xl font-black">{{ $resumenEstado->cantidad ?? 0 }}</p>
                            </div>
                        @endforeach
                    </div>
	                <div class="overflow-x-auto">
	                    <table class="min-w-full text-sm">
	                        <thead class="bg-slate-50 text-left text-slate-500">
	                            <tr>
	                                <th class="px-6 py-4 font-semibold">Orden #</th>
	                                <th class="px-6 py-4 font-semibold">Recolector</th>
	                                <th class="px-6 py-4 font-semibold">Cliente</th>
	                                <th class="px-6 py-4 font-semibold">Total</th>
	                                <th class="px-6 py-4 font-semibold">Estatus</th>
	                                <th class="px-6 py-4 font-semibold">Cambiar estado</th>
	                                <th class="px-6 py-4 font-semibold">Acciones</th>
	                            </tr>
	                        </thead>
		                        <tbody class="divide-y divide-slate-100">
                                    <?php if ($ultimasFacturasRecolector->isEmpty()): ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-8 text-center text-slate-500">No hay facturas de recolectores en esta quincena.</td>
                                        </tr>
                                    <?php else: ?>
		                            <?php foreach ($ultimasFacturasRecolector as $factura): ?>
                                    <?php
                                        $estadoFactura = $factura->estado_factura ?? 'pendiente';
                                        $ordenFactura = '#'.str_pad((string) ($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT);
                                        $estatusAction = route('admin.facturas-recolector.estatus', $factura);
                                        $facturaResumenJson = json_encode([
                                            'numero_orden'   => $ordenFactura,
                                            'cliente_nombre' => $factura->cliente->nombre ?? 'Sin cliente',
                                            'celular'        => $factura->celular ?? '',
                                            'total'          => number_format((float)$factura->total, 0, ',', '.'),
                                            'total_prendas'  => $factura->total_prendas,
                                            'estado'         => $estadoFactura,
                                            'recolector'     => $factura->recolector->name ?? 'Sin recolector',
                                            'detalles'       => $factura->detalles->map(fn($d) => [
                                                'prenda_nombre' => $d->prenda_nombre,
                                                'cantidad'      => $d->cantidad,
                                                'color_prenda'  => $d->color_prenda ?? '',
                                                'subtotal'      => number_format((float)$d->subtotal, 0, ',', '.'),
                                            ])->values(),
                                        ]);
                                    ?>
	                                	<tr>
                                	    <td class="px-6 py-4">
                                	        {{-- F3: Clic en número de orden abre modal resumen --}}
                                	        <button type="button" @click="openOrderSummary({{ $facturaResumenJson }})" class="rounded-full bg-amber-100 px-3 py-1.5 text-sm font-bold text-amber-800 transition hover:bg-amber-200 hover:shadow-sm">{{ $ordenFactura }}</button>
                                	    </td>
	                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $factura->recolector->name ?? 'Sin recolector' }}</td>
	                                    <td class="px-6 py-4 text-slate-600">{{ $factura->cliente->nombre ?? 'Sin cliente' }}</td>
	                                    <td class="px-6 py-4 font-semibold text-emerald-700">$ {{ number_format($factura->total, 0, ',', '.') }}</td>
	                                    <td class="px-6 py-4">
                                            <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] ring-1 {{ $estadoClases[$estadoFactura] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                                {{ $estadoFactura }}
                                            </span>
                                            @if ($factura->metodo_pago)
                                                <p class="mt-2 text-xs font-semibold text-slate-500">{{ str_replace('_', ' ', $factura->metodo_pago) }}</p>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    type="button"
                                                    @click="openPayment('{{ $estatusAction }}', '{{ $ordenFactura }}')"
                                                    @disabled($estadoFactura !== 'pendiente')
                                                    class="rounded-full bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500"
                                                >
                                                    Pagado
                                                </button>
                                                <form action="{{ $estatusAction }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="estado_factura" value="pendiente">
                                                    <button @disabled($estadoFactura === 'pagado') class="rounded-full border border-amber-200 px-3 py-2 text-xs font-bold text-amber-700 hover:bg-amber-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400">
                                                        Pendiente
                                                    </button>
                                                </form>
                                                @if (auth()->user()->esAdmin())
                                                    <button
                                                        type="button"
                                                        @click="openCancel('{{ $estatusAction }}', '{{ $ordenFactura }}')"
                                                        @disabled($estadoFactura === 'pagado')
                                                        class="rounded-full border border-rose-200 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400"
                                                    >
                                                        Cancelado
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
	                                    <td class="px-6 py-4">
	                                        <div class="flex items-center gap-2">
                                                @if ($estadoFactura === 'pagado')
                                                    <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-500">Bloqueada</span>
                                                @else
	                                                <a href="{{ route('admin.facturas-recolector.edit', $factura) }}"
	                                                   class="rounded-full border border-sky-200 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-50">
	                                                    Editar
	                                                </a>
                                                @endif
                                                @if (auth()->user()->tieneRol('admin', 'programador'))
    	                                            <form action="{{ route('admin.facturas-recolector.destroy', $factura) }}" method="POST">
    	                                                @csrf
    	                                                @method('DELETE')
    	                                                <button onclick="return confirm('Eliminar este registro del recolector? Tambien se borraran sus detalles.')" class="rounded-full border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">
    	                                                    Eliminar
    	                                                </button>
    	                                            </form>
                                                @endif
	                                        </div>
	                                    </td>
                                </tr>
	                            <?php endforeach; ?>
                                    <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Lista de quincenas cerradas con enlace al reporte detallado --}}
            <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">Quincenas cerradas</h2>
                    {{-- El formato del periodo es: AÑO/MES/QUINCENA (ej: 2025/04/QUINCENA1) --}}
                    <p class="mt-1 text-sm text-slate-500">Consulta periodos guardados como AÑO/MES/QUINCENA.</p>
                </div>
                <div class="space-y-3 p-6">
                    @forelse ($periodosCerrados as $periodo)
                        {{-- Enlace al reporte detallado de cada quincena --}}
                        <a href="{{ route('admin.reportes.periodo', $periodo->periodo) }}" class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-4 transition hover:bg-slate-50">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $periodo->periodo }}</p>
                                <p class="text-sm text-slate-500">{{ $periodo->total_prendas }} prendas registradas</p>
                            </div>
                            <p class="text-sm font-semibold text-emerald-700">$ {{ number_format($periodo->total_general, 0, ',', '.') }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">Todavía no se ha cerrado ninguna quincena.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
        </section>
    </div>

    <div x-cloak x-show="paymentOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4">
        <div @click.outside="paymentOpen = false" class="w-full max-w-md rounded-[1.5rem] bg-white p-6 shadow-2xl">
            <h2 class="text-xl font-black text-slate-900">Metodo de Pago</h2>
            <p class="mt-1 text-sm text-slate-500">Orden <span x-text="selectedOrder"></span></p>
            <form :action="paymentAction" method="POST" class="mt-5 space-y-3">
                @csrf
                @method('PATCH')
                <input type="hidden" name="estado_factura" value="pagado">
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <input type="radio" name="metodo_pago" value="efectivo" class="h-4 w-4 text-emerald-600" required>
                    Efectivo
                </label>
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <input type="radio" name="metodo_pago" value="qr" class="h-4 w-4 text-emerald-600" required>
                    Qr
                </label>
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <input type="radio" name="metodo_pago" value="nequi" class="h-4 w-4 text-emerald-600" required>
                    Nequi
                </label>
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <input type="radio" name="metodo_pago" value="llave_breve" class="h-4 w-4 text-emerald-600" required>
                    LLave Breve
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="paymentOpen = false" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Aceptar</button>
                </div>
            </form>
        </div>
    </div>

    <div x-cloak x-show="cancelOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4">
        <div @click.outside="cancelOpen = false" class="w-full max-w-md rounded-[1.5rem] bg-white p-6 shadow-2xl">
            <h2 class="text-xl font-black text-slate-900">Confirmar cancelacion</h2>
            <p class="mt-3 text-sm text-slate-600">
                Confirmar cancelacion de la orden de pedido <span class="font-bold text-slate-900" x-text="selectedOrder"></span>, esta informacion no se podra recuperar desea seguir con el proceso.
            </p>
            <form :action="cancelAction" method="POST" class="mt-6 flex justify-end gap-3">
                @csrf
                @method('PATCH')
                <input type="hidden" name="estado_factura" value="cancelado">
                <button type="button" @click="cancelOpen = false" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</button>
                <button class="rounded-full bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700">Aceptar</button>
            </form>
        </div>
    </div>

    {{-- ─── F3: MODAL RESUMEN DE ORDEN ──────────────────────────────────────── --}}
    <div x-cloak x-show="orderSummaryOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4">
        <div @click.outside="orderSummaryOpen = false" class="w-full max-w-lg rounded-[1.75rem] bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-700">Resumen de Orden</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-900" x-text="selectedOrderSummary.numero_orden"></h2>
                </div>
                <button @click="orderSummaryOpen = false" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="mt-4 grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Cliente</p>
                    <p class="mt-1 font-bold text-slate-900" x-text="selectedOrderSummary.cliente_nombre"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Celular</p>
                    <p class="mt-1 font-bold text-slate-900" x-text="selectedOrderSummary.celular || 'Sin celular'"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Recolector</p>
                    <p class="mt-1 font-bold text-slate-900" x-text="selectedOrderSummary.recolector"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Total prendas</p>
                    <p class="mt-1 font-bold text-slate-900" x-text="selectedOrderSummary.total_prendas"></p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Valor total</p>
                    <p class="mt-1 text-2xl font-black text-emerald-700">$ <span x-text="selectedOrderSummary.total"></span></p>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Detalle de prendas</p>
                <div class="mt-2 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                    <template x-for="(d, i) in (selectedOrderSummary.detalles || [])" :key="i">
                        <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                            <div>
                                <p class="font-semibold text-slate-900" x-text="d.prenda_nombre"></p>
                                <p class="text-slate-500">
                                    <span x-text="'x' + d.cantidad"></span>
                                    <span x-show="d.color_prenda" class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs" x-text="d.color_prenda"></span>
                                </p>
                            </div>
                            <p class="font-semibold text-slate-900">$ <span x-text="d.subtotal"></span></p>
                        </div>
                    </template>
                </div>
            </div>
            <button @click="orderSummaryOpen = false" class="mt-5 w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-bold text-white hover:bg-slate-800">
                Cerrar
            </button>
        </div>
    </div>

    {{-- ─── F1: MODAL DELEGACIÓN DE CLIENTES ────────────────────────────────── --}}
    <div x-cloak x-show="delegarOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4">
        <div @click.outside="delegarOpen = false" class="w-full max-w-md rounded-[1.75rem] bg-white p-6 shadow-2xl">
            <h2 class="text-xl font-black text-slate-900">Asignar cliente a recolector</h2>
            <p class="mt-1 text-sm text-slate-500">Cliente: <span class="font-semibold text-slate-900" x-text="delegarClienteNombre"></span></p>
            <form :action="delegarAction" method="POST" class="mt-5 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Recolector asignado</label>
                    <select name="recolector_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                        <option value="">Sin asignar</option>
                        @foreach ($recolectores as $rec)
                            <option value="{{ $rec->id }}">{{ $rec->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="delegarOpen = false" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button class="rounded-full bg-violet-600 px-4 py-2 text-sm font-bold text-white hover:bg-violet-700">Guardar</button>
                </div>
            </form>
        </div>
    </div>

</div>

{{-- ─── F1: SECCIÓN DELEGACIÓN DE CLIENTES (fuera del main div para no conflicto de Alpine) --}}
@push('after-content')
@endpush

@endsection

