@extends('layouts.app')

@section('title', 'Informes imprimibles')

@section('content')
@php
    $opcionesRegistro = $grupo === 'recolectores' ? $resumenRecolectores : $resumenUsuarios;
    $detalleActual = $grupo === 'recolectores' ? $detalleRecolectores : $detalleUsuarios;
    $totalGrupoActual = $grupo === 'recolectores' ? $totalGeneralRecolectores : $totalGeneralUsuarios;
    $totalPrendasGrupoActual = $grupo === 'recolectores' ? $totalPrendasRecolectores : $totalPrendasUsuarios;
    $totalCierreResumen = $totalGeneralUsuarios + $totalGeneralRecolectores;
    $totalPrendasResumen = $totalPrendasUsuarios + $totalPrendasRecolectores;
@endphp
<style>
    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
        .print-page { max-width: none !important; padding: 0 !important; }
        .print-card { box-shadow: none !important; border: 1px solid #d1d5db !important; break-inside: avoid; }
    }
</style>
<div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <div class="no-print flex flex-col gap-4 rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Informes imprimibles</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Usuarios y recolectores</h1>
            <p class="mt-2 text-sm text-slate-500">Selecciona si deseas un informe detallado o general y, si aplica, filtra por una persona concreta.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            Volver al panel
        </a>
    </div>

    <div class="no-print rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
        <form method="GET" action="{{ route('admin.reportes.impresion') }}" class="grid gap-4 xl:grid-cols-[1fr_1fr_1fr_auto_auto]">
            <div>
                <label for="tipo_reporte" class="mb-2 block text-sm font-semibold text-slate-700">Tipo de informe</label>
                <select id="tipo_reporte" name="tipo_reporte" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="resumen_diario" @selected($tipoReporte === 'resumen_diario')>Resumen por dia</option>
                    <option value="detallado" @selected($tipoReporte === 'detallado')>Informe detallado</option>
                    <option value="general" @selected($tipoReporte === 'general')>Informe general</option>
                </select>
            </div>

            <div>
                <label for="grupo" class="mb-2 block text-sm font-semibold text-slate-700">Ver registros de</label>
                <select id="grupo" name="grupo" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="usuarios" @selected($grupo === 'usuarios')>Usuarios</option>
                    <option value="recolectores" @selected($grupo === 'recolectores')>Recolectores</option>
                </select>
            </div>

            <div>
                <label for="registro_id" class="mb-2 block text-sm font-semibold text-slate-700">Filtro espec?fico</label>
                <select id="registro_id" name="registro_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="">Todos</option>
                    @foreach ($opcionesRegistro as $opcion)
                        <option value="{{ $opcion['id'] }}" @selected($registroId === $opcion['id'])>
                            {{ $opcion['nombre'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                Ver informe
            </button>

            <button type="submit" name="imprimir" value="1" class="rounded-2xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-700">
                Imprimir
            </button>
        </form>
    </div>

    @if ($tipoReporte === 'resumen_diario')
        <div class="print-page space-y-6">
            <div class="print-card rounded-2xl bg-white p-8 shadow-xl ring-1 ring-slate-200">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.3em] text-slate-500">Lavanderia Exclusiva</p>
                        <h1 class="mt-2 text-3xl font-black text-slate-950">Resumen de cierre</h1>
                        <p class="mt-2 text-sm text-slate-600">
                            Tomado hasta {{ optional($tomadoHasta)->format('d/m/Y H:i') }}.
                            Facturas de recolector entre {{ optional($inicioQuincena)->format('d/m/Y') }} y {{ optional($finQuincena)->format('d/m/Y') }}.
                        </p>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="text-sm font-semibold text-slate-500">Ingreso total registrado</p>
                        <p class="text-3xl font-black text-emerald-700">$ {{ number_format($totalCierreResumen, 0, ',', '.') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $totalPrendasResumen }} prendas registradas</p>
                    </div>
                </div>

                {{-- Paneles de resumen general --}}
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Usuarios</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">$ {{ number_format($totalGeneralUsuarios, 0, ',', '.') }}</p>
                        <p class="text-sm text-slate-500">{{ $totalPrendasUsuarios }} prendas</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Recolectores</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">$ {{ number_format($totalGeneralRecolectores, 0, ',', '.') }}</p>
                        <p class="text-sm text-slate-500">{{ $totalPrendasRecolectores }} prendas</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Total ingresado</p>
                        <p class="mt-2 text-2xl font-black text-emerald-800">$ {{ number_format($totalCierreResumen, 0, ',', '.') }}</p>
                        <p class="text-sm text-emerald-700">Usuarios + Recolectores</p>
                    </div>
                </div>

                {{-- Paneles financieros detallados --}}
                <div class="mt-4 grid gap-3 sm:grid-cols-5">
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Órdenes pagadas</p>
                        <p class="mt-2 text-xl font-black text-blue-900">$ {{ number_format($ordenesPagadasTotal, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-rose-700">Gastos</p>
                        <p class="mt-2 text-xl font-black text-rose-900">$ {{ number_format($gastosQuincena, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl border border-teal-200 bg-teal-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Ganancia</p>
                        <p class="mt-2 text-xl font-black text-teal-900">$ {{ number_format($ganancia, 0, ',', '.') }}</p>
                        <p class="text-xs text-teal-600">Pagadas − Gastos</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700">30% Recolectores</p>
                        <p class="mt-2 text-xl font-black text-amber-900">$ {{ number_format($total30, 0, ',', '.') }}</p>
                        @foreach ($resumen30Recolectores as $r30)
                            <p class="text-xs text-amber-700">{{ $r30['nombre'] }}: ${{ number_format($r30['pago30'], 0, ',', '.') }}</p>
                        @endforeach
                    </div>
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-700">Total Neto</p>
                        <p class="mt-2 text-xl font-black text-indigo-900">$ {{ number_format($totalNeto, 0, ',', '.') }}</p>
                        <p class="text-xs text-indigo-600">Ganancia − 30%</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="print-card rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                    <h2 class="text-xl font-black text-slate-950">Usuarios</h2>
                    <div class="mt-4 space-y-5">
                        @forelse ($resumenDiarioUsuarios as $persona)
                            <div class="break-inside-avoid rounded-xl border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900">{{ $persona['nombre'] }}</h3>
                                        <p class="text-sm text-slate-500">{{ ucfirst($persona['rol']) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-black text-emerald-700">$ {{ number_format($persona['total'], 0, ',', '.') }}</p>
                                        <p class="text-sm text-slate-500">{{ $persona['cantidad'] }} prendas</p>
                                    </div>
                                </div>
                                <table class="mt-4 w-full text-sm">
                                    <thead class="border-y border-slate-200 bg-slate-50 text-left text-slate-600">
                                        <tr>
                                            <th class="px-3 py-2 font-semibold">Dia</th>
                                            <th class="px-3 py-2 font-semibold">Datos de ingreso</th>
                                            <th class="px-3 py-2 text-right font-semibold">Prendas</th>
                                            <th class="px-3 py-2 text-right font-semibold">Valor del dia</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($persona['dias'] as $dia)
                                            <tr>
                                                <td class="px-3 py-3 align-top font-semibold text-slate-800">{{ $dia['fecha'] === 'Sin fecha' ? 'Sin fecha' : \Carbon\Carbon::parse($dia['fecha'])->format('d/m/Y') }}</td>
                                                <td class="px-3 py-3 align-top text-slate-700">
                                                    <div class="space-y-1">
                                                        @foreach ($dia['detalle'] as $detalle)
                                                            <p>
                                                                {{ $detalle['nombre'] }}:
                                                                {{ $detalle['cantidad'] }} prendas,
                                                                $ {{ number_format($detalle['total'], 0, ',', '.') }}
                                                            </p>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td class="px-3 py-3 align-top text-right text-slate-700">{{ $dia['cantidad'] }}</td>
                                                <td class="px-3 py-3 align-top text-right font-bold text-slate-950">$ {{ number_format($dia['total'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="border-t border-slate-300 bg-slate-50">
                                        <tr>
                                            <td colspan="2" class="px-3 py-2 font-bold text-slate-900">Total {{ $persona['nombre'] }}</td>
                                            <td class="px-3 py-2 text-right font-bold text-slate-900">{{ $persona['cantidad'] }}</td>
                                            <td class="px-3 py-2 text-right font-black text-emerald-700">$ {{ number_format($persona['total'], 0, ',', '.') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No hay registros activos de usuarios.</p>
                        @endforelse
                    </div>
                </div>

                <div class="print-card rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-200">
                    <h2 class="text-xl font-black text-slate-950">Recolectores</h2>
                    <div class="mt-4 space-y-5">
                        @forelse ($resumenDiarioRecolectores as $persona)
                            <div class="break-inside-avoid rounded-xl border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900">{{ $persona['nombre'] }}</h3>
                                        <p class="text-sm text-slate-500">{{ ucfirst($persona['rol']) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-black text-emerald-700">$ {{ number_format($persona['total'], 0, ',', '.') }}</p>
                                        <p class="text-sm text-slate-500">{{ $persona['cantidad'] }} prendas</p>
                                    </div>
                                </div>
                                <table class="mt-4 w-full text-sm">
                                    <thead class="border-y border-slate-200 bg-slate-50 text-left text-slate-600">
                                        <tr>
                                            <th class="px-3 py-2 font-semibold">Dia</th>
                                            <th class="px-3 py-2 font-semibold">Datos de ingreso</th>
                                            <th class="px-3 py-2 text-right font-semibold">Prendas</th>
                                            <th class="px-3 py-2 text-right font-semibold">Valor del dia</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($persona['dias'] as $dia)
                                            <tr>
                                                <td class="px-3 py-3 align-top font-semibold text-slate-800">{{ $dia['fecha'] === 'Sin fecha' ? 'Sin fecha' : \Carbon\Carbon::parse($dia['fecha'])->format('d/m/Y') }}</td>
                                                <td class="px-3 py-3 align-top text-slate-700">
                                                    <div class="space-y-2">
                                                        @foreach ($dia['detalle'] as $detalle)
                                                            <div>
                                                                <p class="font-semibold text-slate-900">
                                                                    Factura #{{ str_pad((string) $detalle['factura'], 6, '0', STR_PAD_LEFT) }} - {{ $detalle['cliente'] }}
                                                                </p>
                                                                <p class="text-slate-600">{{ $detalle['prendas'] ?: 'Sin detalle de prendas' }}</p>
                                                                <p class="text-slate-600">
                                                                    {{ $detalle['cantidad'] }} prendas,
                                                                    $ {{ number_format($detalle['total'], 0, ',', '.') }}
                                                                </p>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td class="px-3 py-3 align-top text-right text-slate-700">{{ $dia['cantidad'] }}</td>
                                                <td class="px-3 py-3 align-top text-right font-bold text-slate-950">$ {{ number_format($dia['total'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="border-t border-slate-300 bg-slate-50">
                                        <tr>
                                            <td colspan="2" class="px-3 py-2 font-bold text-slate-900">Total {{ $persona['nombre'] }}</td>
                                            <td class="px-3 py-2 text-right font-bold text-slate-900">{{ $persona['cantidad'] }}</td>
                                            <td class="px-3 py-2 text-right font-black text-emerald-700">$ {{ number_format($persona['total'], 0, ',', '.') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No hay facturas de recolectores en esta quincena.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @elseif ($tipoReporte === 'general')
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-[1.75rem] bg-emerald-600 p-6 text-white shadow-xl">
                <p class="text-sm uppercase tracking-[0.25em] text-emerald-100">Total usuarios</p>
                <p class="mt-3 text-4xl font-black">$ {{ number_format($totalGeneralUsuarios, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-emerald-50">{{ $totalPrendasUsuarios }} prendas registradas</p>
            </div>
            <div class="rounded-[1.75rem] bg-amber-500 p-6 text-white shadow-xl">
                <p class="text-sm uppercase tracking-[0.25em] text-amber-100">Total recolectores</p>
                <p class="mt-3 text-4xl font-black">$ {{ number_format($totalGeneralRecolectores, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-amber-50">{{ $totalPrendasRecolectores }} prendas registradas</p>
            </div>
        </div>

        <div class="grid gap-8 xl:grid-cols-2">
            <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">Informe general de usuarios</h2>
                    <p class="mt-1 text-sm text-slate-500">Muestra solo el valor total y la cantidad total de cada usuario.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Usuario</th>
                                <th class="px-6 py-4 font-semibold">Rol</th>
                                <th class="px-6 py-4 font-semibold">Prendas</th>
                                <th class="px-6 py-4 font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($resumenUsuarios as $item)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $item['nombre'] }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ ucfirst($item['rol']) }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $item['cantidad'] }}</td>
                                    <td class="px-6 py-4 font-semibold text-emerald-700">$ {{ number_format($item['total'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">No hay registros activos de usuarios.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">Informe general de recolectores</h2>
                    <p class="mt-1 text-sm text-slate-500">Muestra solo el valor total y la cantidad total de cada recolector.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Recolector</th>
                                <th class="px-6 py-4 font-semibold">Rol</th>
                                <th class="px-6 py-4 font-semibold">Prendas</th>
                                <th class="px-6 py-4 font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($resumenRecolectores as $item)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $item['nombre'] }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ ucfirst($item['rol']) }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $item['cantidad'] }}</td>
                                    <td class="px-6 py-4 font-semibold text-emerald-700">$ {{ number_format($item['total'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">No hay facturas activas de recolectores.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-[1.75rem] bg-slate-900 p-6 text-white shadow-xl">
                <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Grupo actual</p>
                <p class="mt-3 text-4xl font-black">{{ ucfirst($grupo) }}</p>
                <p class="mt-2 text-sm text-slate-300">{{ $registroId ? 'Mostrando solo el registro seleccionado' : 'Mostrando todos los registros del grupo' }}</p>
            </div>
            <div class="rounded-[1.75rem] bg-emerald-600 p-6 text-white shadow-xl">
                <p class="text-sm uppercase tracking-[0.25em] text-emerald-100">Total del informe</p>
                <p class="mt-3 text-4xl font-black">$ {{ number_format($totalGrupoActual, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-emerald-50">{{ $totalPrendasGrupoActual }} prendas registradas</p>
            </div>
        </div>

        @forelse ($detalleActual as $item)
            <div class="overflow-hidden rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">{{ $item['nombre'] }}</h2>
                            <p class="text-sm text-slate-500">
                                Rol: {{ ucfirst($item['rol']) }} | Cédula: {{ $item['cedula'] }} | Contacto: {{ $item['contacto'] }}
                            </p>
                        </div>
                        <div class="text-left lg:text-right">
                            <p class="text-sm text-slate-500">{{ $item['cantidad'] }} prendas</p>
                            <p class="text-2xl font-black text-emerald-700">$ {{ number_format($item['total'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                @if ($grupo === 'usuarios')
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Fecha</th>
                                    <th class="px-6 py-4 font-semibold">Prenda</th>
                                    <th class="px-6 py-4 font-semibold">Cantidad</th>
                                    <th class="px-6 py-4 font-semibold">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($item['registros'] as $registro)
                                    <tr>
                                        <td class="px-6 py-4 text-slate-700">{{ optional($registro->fecha)->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $registro->prenda->nombre ?? 'Sin prenda' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $registro->cantidad }}</td>
                                        <td class="px-6 py-4 font-semibold text-emerald-700">$ {{ number_format($registro->total, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="space-y-4 p-6">
                        @foreach ($item['registros'] as $factura)
                            <div class="rounded-[1.5rem] border border-slate-200 p-5">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="text-lg font-bold text-slate-900">{{ $factura->cliente->nombre ?? 'Cliente eliminado' }}</p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            Ingreso {{ optional($factura->fecha_ingreso)->format('d/m/Y H:i') }} |
                                            Entrega {{ optional($factura->fecha_entrega)->format('d/m/Y') }}
                                        </p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ $factura->direccion ?: 'Sin direcci?n' }} |
                                            {{ $factura->nit_cedula ?: 'Sin documento' }} |
                                            {{ $factura->celular ?: 'Sin celular' }}
                                        </p>
                                    </div>
                                    <div class="text-left lg:text-right">
                                        <p class="text-sm text-slate-500">{{ $factura->total_prendas }} prendas</p>
                                        <p class="text-2xl font-black text-emerald-700">$ {{ number_format($factura->total, 0, ',', '.') }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="text-left text-slate-500">
                                            <tr>
                                                <th class="pb-3 font-semibold">Prenda</th>
                                                <th class="pb-3 font-semibold">Cantidad</th>
                                                <th class="pb-3 font-semibold">Valor unitario</th>
                                                <th class="pb-3 font-semibold">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                            @foreach ($factura->detalles as $detalle)
                                                <tr>
                                                    <td class="py-3 font-medium text-slate-900">{{ $detalle->prenda_nombre }}</td>
                                                    <td class="py-3 text-slate-600">{{ $detalle->cantidad }}</td>
                                                    <td class="py-3 text-slate-600">$ {{ number_format($detalle->valor_unitario, 0, ',', '.') }}</td>
                                                    <td class="py-3 font-semibold text-slate-900">$ {{ number_format($detalle->subtotal, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-[1.75rem] bg-white p-6 text-sm text-slate-500 shadow-xl ring-1 ring-slate-200">
                No hay datos disponibles para el filtro seleccionado.
            </div>
        @endforelse
    @endif
</div>

@if ($autoPrint)
    <script>
        window.addEventListener('load', () => window.print());
    </script>
@endif
@endsection

