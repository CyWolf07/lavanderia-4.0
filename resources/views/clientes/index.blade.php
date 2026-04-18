@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Clientes</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Gestión de clientes</h1>
            <p class="mt-2 text-sm text-slate-500">Registra los clientes que luego podrá seleccionar el rol recolector al crear una factura.</p>
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
            <h2 class="text-lg font-bold text-slate-900">Nuevo cliente</h2>
            <form action="{{ route('clientes.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <input type="text" name="nombre" placeholder="Nombre del cliente" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                <input type="text" name="nit_cedula" placeholder="NIT o C.C." class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                <input type="text" name="celular" placeholder="Celular" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <input type="text" name="direccion" placeholder="Dirección" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                    Guardar cliente
                </button>
            </form>
        </div>

        <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-bold text-slate-900">Clientes registrados</h2>
            </div>
            <div class="space-y-4 p-6">
                @forelse ($clientes as $cliente)
                    <div class="rounded-[1.5rem] border border-slate-200 p-4">
                        <form action="{{ route('clientes.update', $cliente) }}" method="POST" class="grid gap-3 md:grid-cols-2">
                            @csrf
                            @method('PUT')
                            <input name="nombre" type="text" value="{{ $cliente->nombre }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                            <input name="nit_cedula" type="text" value="{{ $cliente->nit_cedula }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                            <input name="celular" type="text" value="{{ $cliente->celular }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                            <input name="direccion" type="text" value="{{ $cliente->direccion }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                            <div class="md:col-span-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-slate-500">{{ $cliente->nombre }} | {{ $cliente->nit_cedula }}</p>
                                <button class="rounded-full bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                                    Guardar cambios
                                </button>
                            </div>
                        </form>

                        <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('¿Eliminar este cliente?')" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                                Eliminar
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="px-6 pb-6 text-sm text-slate-500">No hay clientes registrados todav?a.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection


