{{-- ====================================================
     Vista: Editar PQRS
     Permite modificar los datos de un radicado existente.
     Solo accesible cuando se llega desde el botón "Editar"
     en el listado de PQRS.
     ====================================================  --}}
@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- Encabezado de la sección --}}
    <div class="flex items-center justify-between bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-yellow-50 to-amber-50 opacity-50 z-0"></div>
        <div class="relative z-10 w-full flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                    Editar PQRS <span class="text-primary">#{{ $pqrs->id }}</span>
                </h1>
                <p class="text-sm text-gray-500 mt-1">Modifica los datos del radicado seleccionado.</p>
            </div>
            {{-- Botón de regreso al listado --}}
            <div class="mt-4 md:mt-0">
                <a href="{{ route('pqrs.index') }}"
                   class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold shadow">
                    ← Volver al listado
                </a>
            </div>
        </div>
    </div>

    {{-- Formulario de edición del PQRS --}}
    <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 max-w-2xl mx-auto">
        <form action="{{ route('pqrs.update', $pqrs->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT') {{-- Laravel necesita PUT para actualizar un recurso --}}

            {{-- Selector del tipo de solicitud --}}
            <div>
                <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo de Solicitud</label>
                <select id="tipo" name="tipo" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary focus:ring-primary transition" required>
                    <option value="" disabled>Seleccione una opción...</option>
                    {{-- Usa @selected para marcar la opción actualmente guardada --}}
                    <option value="Petición"   @selected($pqrs->tipo === 'Petición')>Petición</option>
                    <option value="Queja"      @selected($pqrs->tipo === 'Queja')>Queja</option>
                    <option value="Reclamo"    @selected($pqrs->tipo === 'Reclamo')>Reclamo</option>
                    <option value="Sugerencia" @selected($pqrs->tipo === 'Sugerencia')>Sugerencia</option>
                </select>
            </div>

            {{-- Nombre completo del solicitante --}}
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                <input type="text" name="nombre" id="nombre"
                       value="{{ old('nombre', $pqrs->nombre) }}"
                       class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary focus:ring-primary transition"
                       placeholder="Ej. Juan Pérez" required>
            </div>

            {{-- Correo electrónico del solicitante --}}
            <div>
                <label for="correo" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                <input type="email" name="correo" id="correo"
                       value="{{ old('correo', $pqrs->correo) }}"
                       class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary focus:ring-primary transition"
                       placeholder="ejemplo@correo.com" required>
            </div>

            {{-- Descripción detallada del PQRS --}}
            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción detallada</label>
                <textarea name="descripcion" id="descripcion" rows="5"
                          class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary focus:ring-primary transition resize-none"
                          placeholder="Escribe aquí los detalles..." required>{{ old('descripcion', $pqrs->descripcion) }}</textarea>
            </div>

            {{-- Botón de guardar cambios --}}
            <div class="pt-2">
                <button type="submit"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-yellow-500 hover:to-amber-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-400 transition-all transform hover:-translate-y-1">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
