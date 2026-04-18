@extends('layouts.app')

@section('title', 'Producción')

@section('content')
@php($esUsuario = $user->tieneRol('usuario'))
<div x-data="produccionForm()" class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-sky-700">Producción</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Registro personal de {{ $user->name }}</h1>
            <p class="mt-2 text-sm text-slate-500">
                @if ($user->tieneRol('usuario'))
                    Solo puedes registrar producción y consultar tus pagos por quincena.
                @else
                    También puedes ir al panel administrativo para gestionar usuarios, prendas y cierres.
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

    <div class="grid gap-8 xl:grid-cols-[360px_1fr]">
        <div class="space-y-6">
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
                                    {{ $prenda->nombre }}@unless($esUsuario) - $ {{ number_format($prenda->precio, 0, ',', '.') }}@endunless
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="cantidad" class="mb-2 block text-sm font-semibold text-slate-700">Cantidad</label>
                        <input id="cantidad" type="number" name="cantidad" x-model="cantidad" @input="updatePrice()" min="1" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200" required>
                    </div>

                    @unless($esUsuario)
                        <div class="rounded-2xl bg-slate-900 px-4 py-4 text-white">
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Total estimado</p>
                            <p class="mt-2 text-3xl font-black">$ <span x-text="total.toLocaleString('es-CO')">0</span></p>
                        </div>
                    @else
                        <div class="rounded-2xl bg-slate-100 px-4 py-4 text-slate-900">
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500">Cantidad del registro</p>
                            <p class="mt-2 text-3xl font-black" x-text="cantidad || 0">0</p>
                        </div>
                    @endunless

                    <button type="submit" class="w-full rounded-2xl bg-sky-600 px-4 py-3 text-sm font-semibold text-white hover:bg-sky-700">
                        Guardar producción
                    </button>
                </form>
            </div>

            <div class="rounded-[1.75rem] bg-emerald-600 p-6 text-white shadow-xl">
                <p class="text-sm uppercase tracking-[0.25em] text-emerald-100">Quincena activa</p>
                <p class="mt-3 text-4xl font-black">$ {{ number_format($totalQuincena, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-emerald-50">Este valor se reinicia visualmente al cerrar la quincena, pero queda guardado en el historial.</p>
            </div>

            <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Pagos por quincena</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($historialQuincenas as $periodo)
                        @php
                            preg_match('/^(\d{4})\/(\d{2})\/QUINCENA([12])$/', $periodo->periodo, $matches);
                            $periodoLabel = $matches ? 'Quincena ' . $matches[3] : $periodo->periodo;
                        @endphp
                        <div class="rounded-2xl border border-slate-200 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-800">{{ $periodoLabel }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $periodo->total_prendas }} prendas registradas</p>
                            <p class="mt-2 text-xl font-bold text-emerald-700">$ {{ number_format($periodo->total_periodo, 0, ',', '.') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aún no tienes quincenas cerradas en el historial.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-8">
            <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">Registros actuales</h2>
                    <p class="mt-1 text-sm text-slate-500">Aquí ves solo la producción activa antes del cierre de quincena.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Prenda</th>
                                <th class="px-6 py-4 font-semibold">Cantidad</th>
                                @unless($esUsuario)
                                    <th class="px-6 py-4 font-semibold">Total</th>
                                @endunless
                                <th class="px-6 py-4 font-semibold">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($producciones as $prod)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $prod->prenda->nombre ?? 'Sin prenda' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $prod->cantidad }}</td>
                                    @unless($esUsuario)
                                        <td class="px-6 py-4 font-semibold text-emerald-700">$ {{ number_format($prod->total, 0, ',', '.') }}</td>
                                    @endunless
                                    <td class="px-6 py-4 text-slate-500">{{ optional($prod->fecha)->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $esUsuario ? 3 : 4 }}" class="px-6 py-8 text-center text-slate-500">No hay registros en la quincena activa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">{{ $esUsuario ? 'Prendas por día' : 'Totales diarios' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $esUsuario ? 'Aquí solo ves cuántas prendas registraste por día dentro de la quincena activa.' : 'La tabla resume el ingreso total de cada día dentro de la quincena activa.' }}
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Día</th>
                                <th class="px-6 py-4 font-semibold">{{ $esUsuario ? 'Prendas registradas' : 'Ingreso total' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($porDia as $d)
                                <tr>
                                    <td class="px-6 py-4 text-slate-700">{{ \Carbon\Carbon::parse($d->dia)->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 font-semibold text-slate-900">
                                        @if ($esUsuario)
                                            {{ $d->total_prendas }}
                                        @else
                                            $ {{ number_format($d->total, 0, ',', '.') }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-8 text-center text-slate-500">Todavía no hay resumen diario.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
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
