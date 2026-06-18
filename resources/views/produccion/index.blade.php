@extends('layouts.app')

@section('title', 'Produccion')

@section('content')
@php
    $esUsuario = $user->tieneRol('usuario');
    $totalPrendasPendientes = $esUsuario
        ? $ordenesPendientes->sum(fn ($orden) => $orden->detalles->sum('cantidad'))
        : 0;
@endphp

<div x-data="produccionForm()" class="mx-auto max-w-screen-2xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-sky-700">Produccion</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Registro personal de {{ $user->name }}</h1>
            <p class="mt-2 text-sm text-slate-500">
                @if ($esUsuario)
                    Marca como lavadas las prendas de cada orden de pedido. Las prendas guardadas desaparecen de esta lista.
                @else
                    Registra produccion manual y consulta los totales activos antes del cierre.
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

    @if ($esUsuario)
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-[1.5rem] bg-slate-900 p-5 text-white shadow-xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-300">Ordenes pendientes</p>
                <p class="mt-3 text-4xl font-black">{{ $ordenesPendientes->count() }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-sky-600 p-5 text-white shadow-xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-100">Prendas pendientes</p>
                <p class="mt-3 text-4xl font-black">{{ $totalPrendasPendientes }}</p>
            </div>
            <div class="rounded-[1.5rem] bg-emerald-600 p-5 text-white shadow-xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-100">Total final acumulado</p>
                <p class="mt-3 text-3xl font-black">$ {{ number_format($totalQuincena, 0, ',', '.') }}</p>
            </div>
        </div>

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
                                <p class="mt-1 text-sm text-slate-500">
                                    Recolector: {{ $orden->recolector->name ?? 'Sin recolector' }}
                                </p>
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
                                        <th class="px-6 py-4 font-semibold"># prendas</th>
                                        <th class="px-6 py-4 font-semibold">Tipo de prenda</th>
                                        <th class="px-6 py-4 font-semibold">Color</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($orden->detalles as $detalle)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-6 py-4">
                                                <input
                                                    type="checkbox"
                                                    name="detalles[]"
                                                    value="{{ $detalle->id }}"
                                                    class="h-5 w-5 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                                >
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
                            <button class="rounded-full bg-sky-600 px-5 py-3 text-sm font-bold text-white hover:bg-sky-700">
                                Guardar prendas lavadas
                            </button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="rounded-[1.75rem] bg-white p-8 text-center shadow-xl ring-1 ring-slate-200">
                    <h2 class="text-xl font-black text-slate-900">No hay ordenes pendientes</h2>
                    <p class="mt-2 text-sm text-slate-500">Cuando un recolector registre una orden nueva, sus prendas apareceran aqui para marcarlas como lavadas.</p>
                </div>
            @endforelse
        </div>

        <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">Prendas lavadas por dia</h2>
                <p class="mt-1 text-sm text-slate-500">Resumen de lo que ya guardaste desde las ordenes de pedido.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Dia</th>
                            <th class="px-6 py-4 font-semibold">Prendas registradas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($porDia as $d)
                            <tr>
                                <td class="px-6 py-4 text-slate-700">{{ \Carbon\Carbon::parse($d->dia)->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 font-semibold text-slate-900">{{ $d->total_prendas }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-8 text-center text-slate-500">Todavia no hay prendas lavadas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="grid min-w-0 gap-8 2xl:grid-cols-[minmax(320px,360px)_minmax(0,1fr)]">
            <div class="min-w-0 space-y-6">
                <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">Nuevo registro</h2>
                    <p class="mt-1 text-sm text-slate-500">Selecciona la prenda y escribe la cantidad producida.</p>

                    <form action="{{ route('produccion.store') }}" method="POST" class="mt-6 space-y-5">
                        @csrf

                        <div>
                            <label for="prenda_id" class="mb-2 block text-sm font-semibold text-slate-700">Prenda</label>
                            <select id="prenda_id" name="prenda_id" x-model="selectedPrendaId" @change="updatePrice()" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" required>
                                <option value="">Selecciona una prenda</option>
                                @foreach($prendas as $prenda)
                                    <option value="{{ $prenda->id }}" data-precio="{{ $prenda->precio }}">
                                        {{ $prenda->nombre }} - $ {{ number_format($prenda->precio, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="cantidad" class="mb-2 block text-sm font-semibold text-slate-700">Cantidad</label>
                            <input id="cantidad" type="number" name="cantidad" x-model="cantidad" @input="updatePrice()" min="1" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" required>
                        </div>

                        <div class="rounded-2xl bg-slate-900 px-4 py-4 text-white">
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Total estimado</p>
                            <p class="mt-2 text-3xl font-black">$ <span x-text="total.toLocaleString('es-CO')">0</span></p>
                        </div>

                        <button type="submit" class="w-full rounded-2xl bg-sky-600 px-4 py-3 text-sm font-semibold text-white hover:bg-sky-700">
                            Guardar produccion
                        </button>
                    </form>
                </div>

                <div class="min-w-0 rounded-[1.75rem] bg-emerald-600 p-5 text-white shadow-xl sm:p-6">
                    <p class="break-words text-xs font-semibold uppercase tracking-[0.18em] text-emerald-100 sm:text-sm sm:tracking-[0.22em]">Quincena activa</p>
                    <p class="mt-3 break-words text-3xl font-black sm:text-4xl">$ {{ number_format($totalQuincena, 0, ',', '.') }}</p>
                    <p class="mt-2 text-sm text-emerald-50">Solo admin y programador pueden cerrar manualmente la quincena.</p>
                </div>
            </div>

            <div class="min-w-0 space-y-8">
                <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <h2 class="text-lg font-bold text-slate-900">Registros actuales</h2>
                        <p class="mt-1 text-sm text-slate-500">Aqui ves solo la produccion activa antes del cierre de quincena.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Prenda</th>
                                    <th class="px-6 py-4 font-semibold">Cantidad</th>
                                    <th class="px-6 py-4 font-semibold">Total</th>
                                    <th class="px-6 py-4 font-semibold">Fecha</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($producciones as $prod)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-6 py-4 font-medium text-slate-900">{{ $prod->prenda->nombre ?? 'Sin prenda' }}</td>
                                        <td class="px-6 py-4 text-slate-600">{{ $prod->cantidad }}</td>
                                        <td class="px-6 py-4 font-semibold text-emerald-700">$ {{ number_format($prod->total, 0, ',', '.') }}</td>
                                        <td class="px-6 py-4 text-slate-500">{{ optional($prod->fecha)->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-slate-500">No hay registros en la quincena activa.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function produccionForm() {
    return {
        selectedPrendaId: '',
        cantidad: 1,
        total: 0,
        updatePrice() {
            const select = document.querySelector('select[name="prenda_id"]');
            if (!select) return;
            const option = select.options[select.selectedIndex];
            const precio = parseFloat(option ? option.getAttribute('data-precio') : 0) || 0;
            this.total = precio * this.cantidad;
        }
    }
}
</script>
@endsection
