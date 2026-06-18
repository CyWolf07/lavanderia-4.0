@extends('layouts.app')

@section('title', 'Reporte de quincena')

@section('content')
<div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Reporte de cierre</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">{{ $periodo }}</h1>
            <p class="mt-2 text-sm text-slate-500">Informe agrupado por empleado para impresi?n y consulta hist?rica.</p>
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

    {{-- Paneles de resumen general --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Usuarios</p>
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
            <p class="text-sm text-emerald-700">Recolectores - Usuarios</p>
        </div>
    </div>

    {{-- Paneles financieros detallados (ORDEN SOLICITADO) --}}
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
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-700">Pago Usuarios</p>
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

    @foreach ($registrosPorUsuario as $registros)
        @php
            $usuario = $registros->first()->user;
        @endphp
        <div class="overflow-hidden rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200 break-inside-avoid">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Usuario (Lavandero)</p>
                        <h2 class="text-xl font-bold text-slate-900">{{ $usuario->name ?? 'Usuario eliminado' }}</h2>
                        <p class="text-sm text-slate-500">
                            Cédula: {{ $usuario->cedula ?? 'No registrada' }} | Contacto: {{ $usuario->contacto ?? 'No registrado' }}
                        </p>
                    </div>
                    <p class="text-lg font-bold text-emerald-700">$ {{ number_format($registros->sum('total'), 0, ',', '.') }}</p>
                </div>
            </div>

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
                        @foreach ($registros as $registro)
                            <tr>
                                <td class="px-6 py-4 text-slate-700">{{ optional($registro->fecha)->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $registro->prenda_nombre }}</td>
                                <td class="px-6 py-4 text-slate-700">{{ $registro->cantidad }}</td>
                                <td class="px-6 py-4 font-semibold text-emerald-700">$ {{ number_format($registro->total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    @foreach ($facturasRecolector->groupBy('recolector_id') as $recolectorId => $facturas)
        @php
            $recolector = $facturas->first()->recolector;
        @endphp
        <div class="overflow-hidden rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200 break-inside-avoid">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Recolector</p>
                        <h2 class="text-xl font-bold text-slate-900">{{ $recolector->name ?? 'Recolector eliminado' }}</h2>
                        <p class="text-sm text-slate-500">
                            Cédula: {{ $recolector->cedula ?? 'No registrada' }} | Contacto: {{ $recolector->contacto ?? 'No registrado' }}
                        </p>
                    </div>
                    <p class="text-lg font-bold text-sky-700">$ {{ number_format($facturas->sum('total'), 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Fecha Ingreso</th>
                            <th class="px-6 py-4 font-semibold">Cliente</th>
                            <th class="px-6 py-4 font-semibold">Cant. Prendas</th>
                            <th class="px-6 py-4 font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($facturas as $factura)
                            <tr>
                                <td class="px-6 py-4 text-slate-700">{{ optional($factura->fecha_ingreso)->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $factura->cliente->nombre ?? 'Eliminado' }}</td>
                                <td class="px-6 py-4 text-slate-700">{{ $factura->total_prendas }}</td>
                                <td class="px-6 py-4 font-semibold text-sky-700">$ {{ number_format($factura->total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

@if ($autoPrint)
    <script>
        window.addEventListener('load', () => window.print());
    </script>
@endif
@endsection
