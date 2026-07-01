@extends('layouts.app')

@section('title', 'Editar historial #' . $registro->id)

@section('content')
<div class="mx-auto max-w-xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">

    {{-- Cabecera --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.reportes.periodo', $registro->periodo) }}"
           class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            ← Volver al informe
        </a>
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-indigo-700">Editar registro histórico</p>
            <h1 class="mt-1 text-2xl font-black text-slate-900">Historial #{{ $registro->id }}</h1>
            <p class="mt-1 text-sm text-slate-500">Período: <span class="font-semibold text-slate-700">{{ $registro->periodo }}</span></p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-[1.75rem] bg-white p-8 shadow-xl ring-1 ring-slate-200">
        {{-- Info del registro actual --}}
        <div class="mb-6 rounded-2xl bg-indigo-50 px-4 py-4 text-sm ring-1 ring-indigo-200">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600">Datos actuales</p>
            <div class="mt-2 space-y-1 text-slate-700">
                <p><span class="font-semibold">Lavandero:</span> {{ $registro->user->name ?? 'Eliminado' }}</p>
                <p><span class="font-semibold">Prenda:</span> {{ $registro->prenda_nombre }}</p>
                <p><span class="font-semibold">Cantidad:</span> {{ $registro->cantidad }}</p>
                <p><span class="font-semibold">Total:</span> $ {{ number_format($registro->total, 0, ',', '.') }}</p>
                <p><span class="font-semibold">Fecha:</span> {{ optional($registro->fecha)->format('d/m/Y') }}</p>
                @if ($registro->cerradoPor)
                    <p><span class="font-semibold">Cerrado por:</span> {{ $registro->cerradoPor->name }}</p>
                @endif
            </div>
        </div>

        <form action="{{ route('admin.historial.update', $registro) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Usuario --}}
            <div>
                <label for="user_id" class="mb-2 block text-sm font-semibold text-slate-700">Lavandero</label>
                <select id="user_id" name="user_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario->id }}" @selected(old('user_id', $registro->user_id) == $usuario->id)>
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
                        <option value="{{ $prenda->id }}" @selected(old('prenda_id', $registro->prenda_id) == $prenda->id)>
                            {{ $prenda->nombre }} — $ {{ number_format($prenda->precio, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Cantidad --}}
            <div>
                <label for="cantidad" class="mb-2 block text-sm font-semibold text-slate-700">Cantidad</label>
                <input id="cantidad" type="number" name="cantidad" min="1"
                    value="{{ old('cantidad', $registro->cantidad) }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
            </div>

            {{-- Fecha --}}
            <div>
                <label for="fecha" class="mb-2 block text-sm font-semibold text-slate-700">Fecha</label>
                <input id="fecha" type="date" name="fecha"
                    value="{{ old('fecha', optional($registro->fecha)->format('Y-m-d')) }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
            </div>

            <div class="rounded-2xl bg-amber-50 px-4 py-3 text-xs text-amber-700 ring-1 ring-amber-200">
                ⚠️ El total se recalculará automáticamente usando el precio actual de la prenda seleccionada.
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                    Guardar cambios
                </button>
                <a href="{{ route('admin.reportes.periodo', $registro->periodo) }}"
                    class="rounded-2xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
