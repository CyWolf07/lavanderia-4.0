@extends('layouts.app')

@section('title', 'Reporte de quincena — ' . $periodo)

@section('content')
<div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Reporte de cierre</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">{{ $periodo }}</h1>
            <p class="mt-2 text-sm text-slate-500">Informe agrupado por empleado para impresión y consulta histórica.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Volver al panel
            </a>
            <button onclick="window.print()" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                Imprimir informe
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Paneles de resumen general --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Lavanderos</p>
            <p class="mt-2 text-2xl font-black text-slate-900">$ {{ number_format($totalGeneral, 0, ',', '.') }}</p>
            <p class="text-sm text-slate-500">{{ $totalPrendas }} prendas</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Recolectores</p>
            <p class="mt-2 text-2xl font-black text-slate-900">$ {{ number_format($totalFacturasPeriodo, 0, ',', '.') }}</p>
            <p class="text-sm text-slate-500">{{ $totalPrendasFacturas }} prendas</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Total ingresado</p>
            <p class="mt-2 text-2xl font-black text-emerald-800">$ {{ number_format($totalFacturasPeriodo - $totalGeneral, 0, ',', '.') }}</p>
            <p class="text-sm text-emerald-700">Recolectores - Lavanderos</p>
        </div>
    </div>

    {{-- Paneles financieros detallados --}}
    <div class="grid gap-3 sm:grid-cols-6">
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Órdenes pagadas</p>
            <p class="mt-2 text-xl font-black text-blue-900">$ {{ number_format($ordenesPagadasTotal, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-rose-700">Gastos</p>
            <p class="mt-2 text-xl font-black text-rose-900">$ {{ number_format($gastosPeriodo, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-700">Total Neto</p>
            <p class="mt-2 text-xl font-black text-indigo-900">$ {{ number_format($totalNeto, 0, ',', '.') }}</p>
            <p class="text-xs text-indigo-600">Pagadas − Gastos</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-700">Pago Lavanderos</p>
            <p class="mt-2 text-xl font-black text-slate-900">$ {{ number_format($totalGeneral, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700">Pago Recolectores</p>
            <p class="mt-2 text-xl font-black text-amber-900">$ {{ number_format($total30, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-teal-200 bg-teal-50 p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Total Ganancia</p>
            <p class="mt-2 text-xl font-black text-teal-900">$ {{ number_format($ganancia, 0, ',', '.') }}</p>
            <p class="text-xs text-teal-600">Neto − U. − R.</p>
        </div>
    </div>

    {{-- ─── REGISTROS DE LAVANDEROS (con edición para admin/programador) ─── --}}
    @if ($registrosPorUsuario->isNotEmpty())
        <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">📋 Registros de lavanderos</h2>
                <p class="mt-1 text-sm text-slate-500">Producción registrada y cerrada en este período. Puedes editar registros individuales.</p>
            </div>
            @foreach ($registrosPorUsuario as $registros)
                @php
                    $usuario = $registros->first()->user;
                @endphp
                <div class="overflow-hidden border-b border-slate-100 break-inside-avoid last:border-0">
                    <div class="flex flex-col gap-2 bg-slate-50 px-6 py-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Lavandero</p>
                            <h3 class="text-lg font-bold text-slate-900">{{ $usuario->name ?? 'Lavandero eliminado' }}</h3>
                            <p class="text-sm text-slate-500">
                                Cédula: {{ $usuario->cedula ?? 'No registrada' }} | Contacto: {{ $usuario->contacto ?? 'No registrado' }}
                            </p>
                        </div>
                        <p class="text-lg font-bold text-emerald-700">$ {{ number_format($registros->sum('total'), 0, ',', '.') }}</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Fecha</th>
                                    <th class="px-6 py-4 font-semibold">Prenda</th>
                                    <th class="px-6 py-4 font-semibold">Cantidad</th>
                                    <th class="px-6 py-4 font-semibold">Total</th>
                                    <th class="px-6 py-4 font-semibold print:hidden">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($registros as $registro)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 text-slate-700">{{ optional($registro->fecha)->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $registro->prenda_nombre }}</td>
                                        <td class="px-6 py-4 text-slate-700">{{ $registro->cantidad }}</td>
                                        <td class="px-6 py-4 font-semibold text-emerald-700">$ {{ number_format($registro->total, 0, ',', '.') }}</td>
                                        <td class="px-6 py-4 print:hidden">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('admin.historial.edit', $registro) }}"
                                                   class="rounded-full border border-sky-200 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-50">
                                                    Editar
                                                </a>
                                                <form action="{{ route('admin.historial.destroy', $registro) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        onclick="return confirm('¿Eliminar este registro histórico? Esta acción no se puede deshacer.')"
                                                        class="rounded-full border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-slate-200 bg-emerald-50">
                                <tr>
                                    <td colspan="2" class="px-6 py-3 font-bold text-slate-900">Subtotal</td>
                                    <td class="px-6 py-3 font-bold text-slate-900">{{ $registros->sum('cantidad') }}</td>
                                    <td class="px-6 py-3 font-bold text-emerald-700">$ {{ number_format($registros->sum('total'), 0, ',', '.') }}</td>
                                    <td class="print:hidden"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-[1.75rem] bg-slate-50 p-6 text-center text-sm text-slate-500 ring-1 ring-slate-200">
            No hay registros de lavanderos para este período (posiblemente fue un cierre automático de recolectores).
        </div>
    @endif

    {{-- ─── FACTURAS DE RECOLECTORES (con edición para admin/programador) ─── --}}
    {{-- GASTOS ESPECIFICOS DE LA QUINCENA --}}
    @if ($gastosDetalle->isNotEmpty())
        <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">Gastos especificos</h2>
                <p class="mt-1 text-sm text-slate-500">Gastos registrados dentro de esta quincena.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Fecha</th>
                            <th class="px-6 py-4 font-semibold">Concepto</th>
                            <th class="px-6 py-4 font-semibold">Registrado por</th>
                            <th class="px-6 py-4 text-right font-semibold">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($gastosDetalle as $gasto)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 text-slate-700">{{ optional($gasto->fecha)->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $gasto->concepto }}</td>
                                <td class="px-6 py-4 text-slate-700">{{ $gasto->user->name ?? 'Usuario eliminado' }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-rose-700">$ {{ number_format($gasto->monto, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 bg-rose-50">
                        <tr>
                            <td colspan="3" class="px-6 py-3 font-bold text-slate-900">Total gastos</td>
                            <td class="px-6 py-3 text-right font-bold text-rose-700">$ {{ number_format($gastosDetalle->sum('monto'), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @else
        <div class="rounded-[1.75rem] bg-slate-50 p-6 text-center text-sm text-slate-500 ring-1 ring-slate-200">
            No hay gastos registrados para este periodo.
        </div>
    @endif
    @if ($facturasRecolector->isNotEmpty())
        <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">📦 Facturas de recolectores</h2>
                <p class="mt-1 text-sm text-slate-500">Órdenes pagadas asignadas a este período. Se pueden editar desde el panel.</p>
            </div>
            @foreach ($facturasRecolector->groupBy('recolector_id') as $recolectorId => $facturas)
                @php
                    $recolector = $facturas->first()->recolector;
                @endphp
                <div class="overflow-hidden border-b border-slate-100 break-inside-avoid last:border-0">
                    <div class="flex flex-col gap-2 bg-amber-50 px-6 py-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-700">Recolector</p>
                            <h3 class="text-lg font-bold text-slate-900">{{ $recolector->name ?? 'Recolector eliminado' }}</h3>
                            <p class="text-sm text-slate-500">
                                Cédula: {{ $recolector->cedula ?? 'No registrada' }} | Contacto: {{ $recolector->contacto ?? 'No registrado' }}
                            </p>
                        </div>
                        <p class="text-lg font-bold text-sky-700">$ {{ number_format($facturas->sum('total'), 0, ',', '.') }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Orden</th>
                                    <th class="px-6 py-4 font-semibold">Fecha Ingreso</th>
                                    <th class="px-6 py-4 font-semibold">Fecha Pago</th>
                                    <th class="px-6 py-4 font-semibold">Cliente</th>
                                    <th class="px-6 py-4 font-semibold">Cant. Prendas</th>
                                    <th class="px-6 py-4 font-semibold">Total</th>
                                    <th class="px-6 py-4 font-semibold print:hidden">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($facturas as $factura)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4">
                                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                                                #{{ str_pad((string) ($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-slate-700">{{ optional($factura->fecha_ingreso)->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4">
                                            @if($factura->fecha_pago)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800">
                                                    ✓ {{ $factura->fecha_pago->format('d/m/Y') }}
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $factura->cliente->nombre ?? 'Eliminado' }}</td>
                                        <td class="px-6 py-4 text-slate-700">{{ $factura->total_prendas }}</td>
                                        <td class="px-6 py-4 font-semibold text-sky-700">$ {{ number_format($factura->total, 0, ',', '.') }}</td>
                                        <td class="px-6 py-4 print:hidden">
                                            <a href="{{ route('admin.facturas-recolector.edit', $factura) }}"
                                               class="rounded-full border border-sky-200 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-50">
                                                Editar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-slate-200 bg-sky-50">
                                <tr>
                                    <td colspan="4" class="px-6 py-3 font-bold text-slate-900">Subtotal</td>
                                    <td class="px-6 py-3 font-bold text-slate-900">{{ $facturas->sum('total_prendas') }}</td>
                                    <td class="px-6 py-3 font-bold text-sky-700">$ {{ number_format($facturas->sum('total'), 0, ',', '.') }}</td>
                                    <td class="print:hidden"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@if ($autoPrint)
    <script>
        window.addEventListener('load', () => window.print());
    </script>
@endif
@endsection
