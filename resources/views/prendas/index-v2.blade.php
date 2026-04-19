{{-- ====================================================
     Vista: Prendas v2 (Solo Admin/Programador)
     Versión mejorada con toggle de estado (habilitar/inhabilitar)
     sin necesidad de eliminar la prenda.
     ====================================================  --}}
@extends('layouts.app')

@section('title', 'Prendas')

@section('content')
<div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">

    {{-- Encabezado --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Catálogo</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Gestión de prendas</h1>
            <p class="mt-2 text-sm text-slate-500">Agrega prendas, actualiza precios y usa habilitar o inhabilitar cuando no deban aparecer en producción.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            Volver al panel
        </a>
    </div>

    {{-- Mensaje de éxito --}}
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-8 xl:grid-cols-[360px_1fr]">

        {{-- Formulario: Crear nueva prenda --}}
        <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
            <h2 class="text-lg font-bold text-slate-900">Nueva prenda</h2>
            <form action="{{ route('prendas.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                {{-- Nombre y categoría --}}
                <input type="text" name="nombre" placeholder="Nombre" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                <input type="text" name="tipo" placeholder="Tipo o categoría" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <input type="number" step="0.01" name="precio" placeholder="Precio" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                {{-- Checkbox: la prenda nace habilitada por defecto --}}
                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" value="1" checked class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span>Crear prenda habilitada</span>
                </label>
                <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                    Guardar prenda
                </button>
            </form>
        </div>

        {{-- Listado de prendas con edición y toggle de estado --}}
        <div class="space-y-4">
            @forelse ($prendas as $prenda)
                <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">

                    {{-- Formulario de edición de datos de la prenda --}}
                    <form action="{{ route('prendas.update', $prenda) }}" method="POST" class="grid gap-3 md:grid-cols-3">
                        @csrf
                        @method('PUT')
                        <input type="text" name="nombre" value="{{ $prenda->nombre }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                        <input type="text" name="tipo" value="{{ $prenda->tipo }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                        <input type="number" step="0.01" name="precio" value="{{ $prenda->precio }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                        {{-- Estado actual de la prenda (habilitada/inhabilitada) --}}
                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700 md:col-span-3">
                            <input type="hidden" name="activo" value="0">
                            <input type="checkbox" name="activo" value="1" @checked($prenda->activo) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <span>Prenda habilitada</span>
                        </label>
                        <div class="md:col-span-3 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                                <span>{{ $prenda->nombre }} | $ {{ number_format($prenda->precio, 0, ',', '.') }}</span>
                                {{-- Badge de estado visual --}}
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $prenda->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $prenda->activo ? 'Habilitada' : 'Inhabilitada' }}
                                </span>
                            </div>
                            <button class="rounded-full bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                                Guardar cambios
                            </button>
                        </div>
                    </form>

                    {{-- Acciones adicionales: toggle de estado y eliminación --}}
                    <div class="mt-3 flex flex-wrap gap-2">
                        {{-- Toggle rápido de estado sin recargar el formulario completo --}}
                        <form action="{{ route('prendas.toggle-status', $prenda) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button class="rounded-full border px-4 py-2 text-sm font-semibold {{ $prenda->activo ? 'border-amber-200 text-amber-700 hover:bg-amber-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                                {{ $prenda->activo ? 'Inhabilitar' : 'Habilitar' }}
                            </button>
                        </form>

                        {{-- Eliminar prenda permanentemente --}}
                        <form action="{{ route('prendas.destroy', $prenda) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('¿Eliminar esta prenda?')" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                {{-- Estado vacío --}}
                <div class="rounded-[1.75rem] bg-white p-6 text-sm text-slate-500 shadow-xl ring-1 ring-slate-200">
                    No hay prendas registradas todavía.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
