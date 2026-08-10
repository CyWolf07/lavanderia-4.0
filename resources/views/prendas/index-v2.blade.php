@extends('layouts.app')

@section('title', 'Prendas lavandero')

@section('content')
<div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Catalogo</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Prendas lavandero</h1>
            <p class="mt-2 text-sm text-slate-500">Este listado se toma del recolector. Aqui solo se ajusta el valor que se paga al lavandero.</p>
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

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($prendas as $prenda)
            <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <form action="{{ route('prendas.update', $prenda) }}" method="POST" class="grid gap-3 md:grid-cols-[1fr_180px_180px_auto] md:items-end">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Prenda</label>
                        <input type="text" value="{{ $prenda->nombre }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" readonly>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Tipo</label>
                        <input type="text" value="{{ $prenda->tipo ?: 'Sin tipo' }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" readonly>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Valor lavandero</label>
                        <input type="number" step="0.01" min="0" name="precio" value="{{ $prenda->precio }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    </div>

                    <button class="rounded-2xl bg-sky-600 px-4 py-3 text-sm font-semibold text-white hover:bg-sky-700">
                        Guardar
                    </button>
                </form>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <span>$ {{ number_format($prenda->precio, 0, ',', '.') }}</span>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $prenda->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                        {{ $prenda->activo ? 'Habilitada por recolector' : 'Inhabilitada por recolector' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-[1.75rem] bg-white p-6 text-sm text-slate-500 shadow-xl ring-1 ring-slate-200">
                No hay prendas del recolector para sincronizar.
            </div>
        @endforelse
    </div>
</div>
@endsection
