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

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-[1.75rem] bg-emerald-600 p-6 text-white shadow-xl">
            <p class="text-sm uppercase tracking-[0.25em] text-emerald-100">Total general</p>
            <p class="mt-3 text-4xl font-black">$ {{ number_format($totalGeneral, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-[1.75rem] bg-sky-600 p-6 text-white shadow-xl">
            <p class="text-sm uppercase tracking-[0.25em] text-sky-100">Prendas registradas</p>
            <p class="mt-3 text-4xl font-black">{{ $totalPrendas }}</p>
        </div>
    </div>

    @foreach ($registrosPorUsuario as $registros)
        @php
            $usuario = $registros->first()->user;
        @endphp
        <div class="overflow-hidden rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
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
                            @if (auth()->user()->tieneRol('programador'))
                                <th class="px-6 py-4 font-semibold">Acci?n</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($registros as $registro)
                            <tr>
                                <td class="px-6 py-4 text-slate-700">{{ optional($registro->fecha)->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $registro->prenda_nombre }}</td>
                                <td class="px-6 py-4 text-slate-700">{{ $registro->cantidad }}</td>
                                <td class="px-6 py-4 font-semibold text-emerald-700">$ {{ number_format($registro->total, 0, ',', '.') }}</td>
                                @if (auth()->user()->tieneRol('programador'))
                                    <td class="px-6 py-4">
                                        <form action="{{ route('programador.historial.destroy', $registro) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button onclick="return confirm('Â¿Eliminar este registro historico?')" class="rounded-full border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                @endif
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

