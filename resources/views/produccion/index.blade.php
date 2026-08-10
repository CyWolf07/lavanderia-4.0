@extends('layouts.app')

@section('title', 'Produccion')

@section('content')
@php
    $esLavandero = $user->tieneRol('usuario');
    $modoAvanzado = $esLavandero && ($modoInterfazLavandero ?? 'basica') === 'avanzada';
    $totalPrendasPendientes = $modoAvanzado
        ? $ordenesPendientes->sum(fn ($orden) => $orden->detalles->sum('cantidad'))
        : 0;
@endphp

<div class="mx-auto max-w-screen-2xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-sky-700">Produccion</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Cierre diario de {{ $user->name }}</h1>
            <p class="mt-2 text-sm text-slate-500">
                @if ($modoAvanzado)
                    Marca prendas lavadas desde ordenes de pedido. El administrador puede volver al ingreso manual cuando lo necesite.
                @else
                    Ingresa manualmente las prendas lavadas del dia desde el catalogo autorizado.
                @endif
            </p>
        </div>

        @if ($user->tieneRol('admin', 'programador'))
            <a href="{{ route('admin.dashboard') }}" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                Ir al panel admin
            </a>
        @endif
    </div>

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

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-[1.5rem] bg-slate-900 p-5 text-white shadow-xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-300">Pago validado</p>
            <p class="mt-3 text-3xl font-black">$ {{ number_format($totalQuincena, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-[1.5rem] bg-sky-600 p-5 text-white shadow-xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-100">{{ $modoAvanzado ? 'Ordenes pendientes' : 'Registros activos' }}</p>
            <p class="mt-3 text-4xl font-black">{{ $modoAvanzado ? $ordenesPendientes->count() : $producciones->count() }}</p>
        </div>
        <div class="rounded-[1.5rem] bg-emerald-600 p-5 text-white shadow-xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-100">{{ $modoAvanzado ? 'Prendas pendientes' : 'Prendas validadas' }}</p>
            <p class="mt-3 text-4xl font-black">{{ $modoAvanzado ? $totalPrendasPendientes : $producciones->sum('cantidad_validada') }}</p>
        </div>
    </div>

    @if ($modoAvanzado)
        <div class="space-y-5">
            @forelse ($ordenesPendientes as $orden)
                @php
                    $numeroOrden = str_pad((string) ($orden->numero_orden ?? $orden->id), 6, '0', STR_PAD_LEFT);
                    $prendasOrdenPendientes = $orden->detalles->sum('cantidad');
                @endphp

                <div class="overflow-hidden rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Orden de pedido</p>
                                <h2 class="mt-1 text-2xl font-black text-slate-900">#{{ $numeroOrden }}</h2>
                                <p class="mt-1 text-sm text-slate-500">Recolector: {{ $orden->recolector->name ?? 'Sin recolector' }}</p>
                            </div>
                            <div class="text-left lg:text-right">
                                <p class="text-sm font-semibold text-slate-500">{{ $prendasOrdenPendientes }} prendas pendientes</p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('produccion.ordenes.lavado', $orden) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-left text-slate-500">
                                    <tr>
                                        <th class="px-6 py-4 font-semibold">Lavada</th>
                                        <th class="px-6 py-4 font-semibold">Cantidad</th>
                                        <th class="px-6 py-4 font-semibold">Prenda</th>
                                        <th class="px-6 py-4 font-semibold">Color</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($orden->detalles as $detalle)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-6 py-4">
                                                <input type="checkbox" name="detalles[]" value="{{ $detalle->id }}" class="h-5 w-5 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                            </td>
                                            <td class="px-6 py-4 font-semibold text-slate-900">{{ $detalle->cantidad }}</td>
                                            <td class="px-6 py-4 text-slate-700">{{ $detalle->prenda_nombre }}</td>
                                            <td class="px-6 py-4 text-slate-500">{{ $detalle->color_prenda ?: 'Sin color' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end border-t border-slate-100 px-6 py-4">
                            <button class="rounded-2xl bg-sky-600 px-5 py-3 text-sm font-bold text-white hover:bg-sky-700">
                                Guardar prendas lavadas
                            </button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="rounded-[1.75rem] bg-white p-8 text-center shadow-xl ring-1 ring-slate-200">
                    <h2 class="text-xl font-black text-slate-900">No hay ordenes pendientes</h2>
                    <p class="mt-2 text-sm text-slate-500">Cuando un recolector registre una orden nueva, sus prendas apareceran aqui.</p>
                </div>
            @endforelse
        </div>
    @else
        <div class="overflow-hidden rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">Registro manual del dia</h2>
                <p class="mt-1 text-sm text-slate-500">Selecciona una prenda y registra la cantidad lavada.</p>
            </div>

            <form action="{{ route('produccion.store') }}" method="POST">
                @csrf
                <div class="grid gap-4 border-b border-slate-100 px-6 py-5 lg:grid-cols-[220px_1fr_160px_auto] lg:items-end">
                    <div>
                        <label for="fecha" class="mb-2 block text-sm font-semibold text-slate-700">Fecha</label>
                        <input id="fecha" name="fecha" type="date" value="{{ old('fecha', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                    </div>
                    <div>
                        <label for="prenda_id" class="mb-2 block text-sm font-semibold text-slate-700">Prenda</label>
                        <select id="prenda_id" name="items[0][prenda_id]" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                            <option value="">Selecciona una prenda</option>
                            @foreach ($prendas as $prenda)
                                <option value="{{ $prenda->id }}" @selected((string) old('items.0.prenda_id') === (string) $prenda->id)>
                                    {{ $prenda->nombre }} @if($prenda->tipo) - {{ $prenda->tipo }} @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="cantidad" class="mb-2 block text-sm font-semibold text-slate-700">Cantidad</label>
                        <input id="cantidad" type="number" name="items[0][cantidad]" min="1" value="{{ old('items.0.cantidad', 1) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    </div>
                    <button class="rounded-2xl bg-sky-600 px-5 py-3 text-sm font-bold text-white hover:bg-sky-700">
                        Guardar
                    </button>
                </div>
                <div class="px-6 py-4 text-sm text-slate-500">
                    @if ($prendas->isEmpty())
                        No hay prendas activas configuradas.
                    @else
                        {{ $prendas->count() }} prendas disponibles.
                    @endif
                </div>
            </form>
        </div>
    @endif

    <div class="overflow-hidden rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-6 py-5">
            <h2 class="text-lg font-bold text-slate-900">Registros activos de la quincena</h2>
            <p class="mt-1 text-sm text-slate-500">El pago se calcula con la cantidad validada o aprobada.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Fecha</th>
                        <th class="px-6 py-4 font-semibold">Prenda</th>
                        <th class="px-6 py-4 font-semibold">Reportada</th>
                        <th class="px-6 py-4 font-semibold">Validada</th>
                        <th class="px-6 py-4 font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($producciones as $prod)
                        <tr>
                            <td class="px-6 py-4 text-slate-500">{{ optional($prod->fecha)->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-900">{{ $prod->prenda->nombre ?? 'Sin prenda' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $prod->cantidad }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $prod->cantidad_validada }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ in_array($prod->estado_validacion, ['validado', 'aprobado'], true) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ ucfirst($prod->estado_validacion ?? 'pendiente') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">No hay registros activos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
