@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex items-center justify-between bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-50 to-indigo-50 opacity-50 z-0"></div>
        <div class="relative z-10 w-full flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Atención al Cliente <span class="text-primary">(PQRS)</span></h1>
                <p class="text-sm text-gray-500 mt-1">Radica tus Peticiones, Quejas, Reclamos y Sugerencias, y lleva el control.</p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-3">
                <div class="bg-indigo-100 text-indigo-800 px-4 py-2 rounded-lg font-medium shadow-sm flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ count($pqrsList) }} Radicados</span>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg shadow-sm flex justify-between items-center">
        <div class="flex items-center">
            <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
        </div>
        <button @click="show = false" class="text-green-500 hover:text-green-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-6">
        
        <!-- Form Section -->
        <div class="lg:w-1/3 bg-white p-6 rounded-2xl shadow-xl border border-gray-100 hover:shadow-2xl transition duration-300">
            <div class="flex items-center mb-6">
                <div class="p-3 bg-gradient-to-br from-primary to-indigo-600 rounded-xl shadow-lg mr-4 text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"></path></svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800">Radicar PQRS</h2>
            </div>
            <form action="{{ route('pqrs.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo de Solicitud</label>
                    <select id="tipo" name="tipo" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary focus:ring-primary transition" required>
                        <option value="" disabled selected>Seleccione una opción...</option>
                        <option value="Petición">Petición</option>
                        <option value="Queja">Queja</option>
                        <option value="Reclamo">Reclamo</option>
                        <option value="Sugerencia">Sugerencia</option>
                    </select>
                </div>

                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                    <input type="text" name="nombre" id="nombre" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary focus:ring-primary transition" placeholder="Ej. Juan Pérez" required>
                </div>

                <div>
                    <label for="correo" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input type="email" name="correo" id="correo" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary focus:ring-primary transition" placeholder="ejemplo@correo.com" required>
                </div>

                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción detallada</label>
                    <textarea name="descripcion" id="descripcion" rows="4" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary focus:ring-primary transition resize-none" placeholder="Escribe aquí los detalles..." required></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gradient-to-r from-primary to-indigo-600 hover:from-indigo-600 hover:to-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all transform hover:-translate-y-1">
                        Enviar Solicitud
                    </button>
                </div>
            </form>
        </div>

        <!-- Table Section -->
        <div class="lg:w-2/3 bg-white p-6 rounded-2xl shadow-md border border-gray-100 overflow-hidden">
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                Registros Previos
            </h2>
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th scope="col" class="px-3 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider truncate max-w-xs">Descripción</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($pqrsList as $pqrs)
                        <tr class="hover:bg-blue-50/50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $pqrs->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                {{ $pqrs->nombre }}
                                <div class="text-xs text-gray-500 font-normal">{{ $pqrs->correo }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @php
                                    $colorClass = match($pqrs->tipo) {
                                        'Petición' => 'bg-blue-100 text-blue-800',
                                        'Queja' => 'bg-red-100 text-red-800',
                                        'Reclamo' => 'bg-orange-100 text-orange-800',
                                        'Sugerencia' => 'bg-green-100 text-green-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colorClass }}">
                                    {{ $pqrs->tipo }}
                                </span>
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-600 max-w-xs truncate" title="{{ $pqrs->descripcion }}">
                                {{ $pqrs->descripcion }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <p class="text-lg font-medium">No hay registros de PQRS.</p>
                                    <p class="text-sm">Envía el primer radicado desde el formulario.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
