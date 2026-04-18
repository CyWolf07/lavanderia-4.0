@extends('layouts.app')

@section('title', 'Prendas')

@section('content')
<div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Cat?logo</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Gesti?n de prendas</h1>
            <p class="mt-2 text-sm text-slate-500">Agrega, edita o elimina prendas y sus precios base.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            Volver al panel
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-8 xl:grid-cols-[360px_1fr]">
        <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
            <h2 class="text-lg font-bold text-slate-900">Nueva prenda</h2>
            <form action="{{ route('prendas.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <input type="text" name="nombre" placeholder="Nombre" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                <input type="text" name="tipo" placeholder="Tipo o categor?a" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <input type="number" step="0.01" name="precio" placeholder="Precio" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                    Guardar prenda
                </button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($prendas as $prenda)
                <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                    <form action="{{ route('prendas.update', $prenda) }}" method="POST" class="grid gap-3 md:grid-cols-3">
                        @csrf
                        @method('PUT')
                        <input type="text" name="nombre" value="{{ $prenda->nombre }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                        <input type="text" name="tipo" value="{{ $prenda->tipo }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                        <input type="number" step="0.01" name="precio" value="{{ $prenda->precio }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                        <div class="md:col-span-3">
                            <button class="rounded-full bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                                Actualizar
                            </button>
                        </div>
                    </form>
                    <form action="{{ route('prendas.destroy', $prenda) }}" method="POST" class="mt-3">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('¿Eliminar esta prenda?')" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                            Eliminar
                        </button>
                    </form>
                </div>
            @empty
                <div class="rounded-[1.75rem] bg-white p-6 text-sm text-slate-500 shadow-xl ring-1 ring-slate-200">
                    No hay prendas registradas todav?a.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection


