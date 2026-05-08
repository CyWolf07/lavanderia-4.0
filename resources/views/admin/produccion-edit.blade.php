@extends('layouts.app')

@section('title', 'Editar registro de producción #' . $produccion->id)

@section('content')
<div class="mx-auto max-w-xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">

    {{-- Cabecera --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            ← Volver
        </a>
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-sky-700">Editar producción</p>
            <h1 class="mt-1 text-2xl font-black text-slate-900">Registro #{{ $produccion->id }}</h1>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-[1.75rem] bg-white p-8 shadow-xl ring-1 ring-slate-200">
        <form action="{{ route('admin.produccion.update', $produccion) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Usuario --}}
            <div>
                <label for="user_id" class="mb-2 block text-sm font-semibold text-slate-700">Usuario</label>
                <select id="user_id" name="user_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario->id }}" @selected(old('user_id', $produccion->user_id) == $usuario->id)>
                            {{ $usuario->name }} ({{ $usuario->obtenerRol() }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Prenda --}}
            <div>
                <label for="prenda_id" class="mb-2 block text-sm font-semibold text-slate-700">Prenda</label>
                <select id="prenda_id" name="prenda_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    @foreach ($prendas as $prenda)
                        <option value="{{ $prenda->id }}" @selected(old('prenda_id', $produccion->prenda_id) == $prenda->id)>
                            {{ $prenda->nombre }} — $ {{ number_format($prenda->precio, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Cantidad --}}
            <div>
                <label for="cantidad" class="mb-2 block text-sm font-semibold text-slate-700">Cantidad</label>
                <input id="cantidad" type="number" name="cantidad" min="1"
                    value="{{ old('cantidad', $produccion->cantidad) }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
            </div>

            {{-- Fecha --}}
            <div>
                <label for="fecha" class="mb-2 block text-sm font-semibold text-slate-700">Fecha</label>
                <input id="fecha" type="date" name="fecha"
                    value="{{ old('fecha', optional($produccion->fecha)->format('Y-m-d')) }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
            </div>

            {{-- Info total calculado --}}
            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <span class="font-semibold">Total actual:</span>
                $ {{ number_format($produccion->total, 0, ',', '.') }}
                <span class="ml-2 text-slate-400">(se recalcula al guardar según precio de la prenda)</span>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 rounded-2xl bg-sky-600 px-6 py-3 text-sm font-semibold text-white hover:bg-sky-700">
                    Guardar cambios
                </button>
                <a href="{{ route('admin.dashboard') }}"
                    class="rounded-2xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
