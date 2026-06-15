{{-- ====================================================
     Vista: Gestión de Clientes (Solo Admin/Programador)
     ====================================================  --}}
@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">

    {{-- Encabezado --}}
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Clientes</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Gestión de clientes</h1>
            <p class="mt-2 text-sm text-slate-500">Registra los clientes que luego podrá seleccionar el recolector al crear una factura.</p>
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

    <div class="grid gap-8 xl:grid-cols-[400px_1fr]">

        {{-- ── FORMULARIO CREAR NUEVO CLIENTE ────────────────── --}}
        <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
            <h2 class="text-lg font-bold text-slate-900">Nuevo cliente</h2>
            <p class="mt-1 text-sm text-slate-500">Completa los datos para registrar un nuevo cliente.</p>

            <form action="{{ route('clientes.store') }}" method="POST" class="mt-6 space-y-4" id="form-crear-cliente">
                @csrf

                {{-- Número autoasignado desde el controlador --}}
                <div class="flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                    <span class="text-slate-600">N° de cliente:</span>
                    <span class="text-xl font-black text-amber-700">#{{ $siguienteNumero }}</span>
                    <span class="text-xs text-slate-400">(asignado automáticamente)</span>
                </div>

                <input type="text" name="nombre" placeholder="Nombre del cliente *"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>

                <x-input-celular class="w-full" />

                <input type="text" name="direccion" placeholder="Dirección"
                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">

                {{-- Barrio con autodetección de CP --}}
                <div class="space-y-1">
                    <input type="text" name="barrio" id="barrio-crear" placeholder="Barrio *"
                           class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required
                           autocomplete="off">
                    <div id="cp-badge-crear" class="hidden flex items-center gap-2 rounded-xl bg-violet-50 px-3 py-2 text-xs text-violet-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <span id="cp-text-crear"></span>
                    </div>
                    <input type="hidden" name="codigo_postal" id="cp-crear">
                </div>

                <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                    Guardar cliente
                </button>
            </form>
        </div>

        {{-- ── LISTADO DE CLIENTES REGISTRADOS ───────────────── --}}
        <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-5 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Clientes registrados</h2>
                    <p class="text-sm text-slate-500 mt-0.5">{{ $clientes->count() }} cliente(s) activos</p>
                </div>
            </div>
            <div class="space-y-4 p-6">
                @forelse ($clientes as $cliente)
                    <div class="rounded-[1.5rem] border border-slate-200 p-4">

                        {{-- Encabezado: número y nombre --}}
                        <div class="mb-3 flex items-center gap-3">
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">
                                #{{ $cliente->numero_cliente ?? 'N/A' }}
                            </span>
                            <span class="font-semibold text-slate-800">{{ $cliente->nombre }}</span>
                            @if ($cliente->barrio)
                                <span class="rounded-full bg-violet-50 px-2 py-0.5 text-xs text-violet-700">
                                    {{ $cliente->barrio }}
                                    @if ($cliente->codigo_postal)
                                        · CP {{ $cliente->codigo_postal }}
                                    @endif
                                </span>
                            @endif
                        </div>

                        {{-- Formulario edición --}}
                        <form action="{{ route('clientes.update', $cliente) }}" method="POST"
                              class="grid gap-3 md:grid-cols-2"
                              data-edit-id="{{ $cliente->id }}">
                            @csrf
                            @method('PUT')

                            <input name="nombre" type="text" value="{{ $cliente->nombre }}"
                                   placeholder="Nombre completo del cliente"
                                   class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>

                            {{-- Barrio con CP autodetectado --}}
                            <div class="space-y-1">
                                <input name="barrio" type="text" value="{{ $cliente->barrio }}"
                                       placeholder="Barrio donde vive el cliente"
                                       class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm edit-barrio"
                                       data-edit-id="{{ $cliente->id }}" required>
                                <div id="cp-badge-edit-{{ $cliente->id }}"
                                     class="{{ $cliente->codigo_postal ? '' : 'hidden' }} flex items-center gap-1 rounded-xl bg-violet-50 px-3 py-1.5 text-xs text-violet-700">
                                    📍 <span id="cp-text-edit-{{ $cliente->id }}">
                                        @if ($cliente->codigo_postal)
                                            Código postal: {{ $cliente->codigo_postal }}
                                        @endif
                                    </span>
                                </div>
                                <input type="hidden" name="codigo_postal"
                                       id="cp-edit-{{ $cliente->id }}"
                                       value="{{ $cliente->codigo_postal }}">
                            </div>

                            <x-input-celular :value="$cliente->celular" class="md:col-span-1" />

                            <input name="direccion" type="text" value="{{ $cliente->direccion }}"
                                   placeholder="Dirección de domicilio"
                                   class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">

                            <div class="md:col-span-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-slate-500">
                                    {{ $cliente->nombre }} | {{ $cliente->barrio ?: 'Sin barrio' }}
                                </p>
                                <button class="rounded-full bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                                    Guardar cambios
                                </button>
                            </div>
                        </form>

                        <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('¿Eliminar este cliente?')"
                                    class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                                Eliminar
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="px-6 pb-6 text-sm text-slate-500">No hay clientes registrados todavía.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ── JS: Autodetección de código postal por barrio ───────── --}}
<script>
const BARRIOS_CP = @json($barriosCP);

function detectarCP(barrio) {
    const b = barrio.toLowerCase().trim()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, ''); // quitar tildes
    for (const [key, cp] of Object.entries(BARRIOS_CP)) {
        const k = key.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        if (b.includes(k) || k.includes(b)) return cp;
    }
    return null;
}

// ── Formulario CREAR ─────────────────────────────────
document.getElementById('barrio-crear')?.addEventListener('input', function () {
    const cp = detectarCP(this.value);
    const badge = document.getElementById('cp-badge-crear');
    const cpInput = document.getElementById('cp-crear');
    const cpText = document.getElementById('cp-text-crear');
    if (cp) {
        cpInput.value = cp;
        cpText.textContent = 'Código postal detectado: ' + cp;
        badge.classList.remove('hidden');
        badge.classList.add('flex');
    } else {
        cpInput.value = '';
        badge.classList.add('hidden');
        badge.classList.remove('flex');
    }
});

// ── Formularios EDITAR ───────────────────────────────
document.querySelectorAll('.edit-barrio').forEach(function (input) {
    const id = input.dataset.editId;
    input.addEventListener('input', function () {
        const cp = detectarCP(this.value);
        const badge = document.getElementById('cp-badge-edit-' + id);
        const cpInput = document.getElementById('cp-edit-' + id);
        const cpText = document.getElementById('cp-text-edit-' + id);
        if (cp) {
            cpInput.value = cp;
            cpText.textContent = 'Código postal: ' + cp;
            badge.classList.remove('hidden');
            badge.classList.add('flex');
        } else {
            cpInput.value = '';
            badge.classList.add('hidden');
            badge.classList.remove('flex');
        }
    });
});
</script>

@endsection
