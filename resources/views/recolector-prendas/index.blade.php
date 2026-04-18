@extends('layouts.app')

@section('title', 'Prendas Recolector')

@section('content')
<div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Recolector</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Prendas y tarifas del recolector</h1>
            <p class="mt-2 text-sm text-slate-500">Estas prendas son independientes de las usadas en producción de usuarios.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            Volver al panel
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-8 xl:grid-cols-[380px_1fr]">
        <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
            <h2 class="text-lg font-bold text-slate-900">Nueva prenda</h2>
            <form action="{{ route('recolector-prendas.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <input type="text" name="nombre" placeholder="Nombre de la prenda" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                <input type="text" name="tipo" placeholder="Tipo" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <input type="number" step="0.01" min="0" name="precio" placeholder="Valor unitario" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                    Guardar prenda
                </button>
            </form>
        </div>

        <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">Prendas registradas</h2>
            </div>
            <div class="space-y-4 p-6">
                @forelse ($prendas as $prenda)
                    <div class="rounded-[1.5rem] border border-slate-200 p-4">
                        <form action="{{ route('recolector-prendas.update', $prenda) }}" method="POST" class="grid gap-3 md:grid-cols-3">
                            @csrf
                            @method('PUT')
                            <input name="nombre" type="text" value="{{ $prenda->nombre }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                            <input name="tipo" type="text" value="{{ $prenda->tipo }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                            <input name="precio" type="number" step="0.01" min="0" value="{{ $prenda->precio }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                            <div class="md:col-span-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-slate-500">{{ $prenda->nombre }} | $ {{ number_format($prenda->precio, 0, ',', '.') }}</p>
                                <button class="rounded-full bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                                    Guardar cambios
                                </button>
                            </div>
                        </form>

                        <form action="{{ route('recolector-prendas.destroy', $prenda) }}" method="POST" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('¿Eliminar esta prenda?')" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                                Eliminar
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="px-6 pb-6 text-sm text-slate-500">No hay prendas del recolector registradas todavía.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

