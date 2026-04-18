@extends('layouts.app')

@section('title', 'Panel administrativo')

@section('content')
<div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Panel administrativo</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Gesti?n general del sistema</h1>
            <p class="mt-2 text-sm text-slate-500">
                Administra usuarios, permisos de precios para recolectores, estados habilitado o inhabilitado y reportes imprimibles.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('prendas.index') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Gesti?nar prendas
            </a>
            <a href="{{ route('clientes.index') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Gesti?nar clientes
            </a>
            <a href="{{ route('recolector-prendas.index') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Prendas recolector
            </a>
            <a href="{{ route('admin.reportes.impresion') }}" class="rounded-full border border-sky-200 bg-sky-50 px-5 py-3 text-sm font-semibold text-sky-700 hover:bg-sky-100">
                Informes imprimibles
            </a>
            <form action="{{ route('produccion.cerrar') }}" method="POST">
                @csrf
                <button type="submit" onclick="return confirm('Ã‚Â¿Cerrar la quincena actual y generar informe imprimible?')" class="rounded-full bg-rose-600 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-700">
                    Cerrar quincena
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-3">
        <div class="rounded-[1.75rem] bg-slate-900 p-6 text-white shadow-xl">
            <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Usuarios registrados</p>
            <p class="mt-3 text-4xl font-black">{{ $totalUsuarios }}</p>
        </div>
        <div class="rounded-[1.75rem] bg-sky-600 p-6 text-white shadow-xl">
            <p class="text-sm uppercase tracking-[0.25em] text-sky-100">Registros activos</p>
            <p class="mt-3 text-4xl font-black">{{ $totalProducciones }}</p>
        </div>
        <div class="rounded-[1.75rem] bg-emerald-600 p-6 text-white shadow-xl">
            <p class="text-sm uppercase tracking-[0.25em] text-emerald-100">Ingreso activo</p>
            <p class="mt-3 text-4xl font-black">$ {{ number_format($ingresosTotales, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid gap-8 xl:grid-cols-[420px_1fr]">
        <div class="space-y-8">
            <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Crear usuario</h2>
                <p class="mt-1 text-sm text-slate-500">Define rol, estado y si un recolector puede editar precios.</p>

                <form action="{{ route('admin.usuarios.store') }}" method="POST" class="mt-6 space-y-4">
                    @csrf

                    <input name="name" type="text" placeholder="Nombre completo" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    <input name="email" type="email" placeholder="Correo" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    <input name="cedula" type="text" placeholder="C?dula" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                    <input name="contacto" type="text" placeholder="Contacto" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">

                    <select name="rol" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                        <option value="usuario">Usuario</option>
                        <option value="recolector">Recolector</option>
                        <option value="admin">Administrador</option>
                        <option value="programador">Programador</option>
                    </select>

                    <div class="rounded-3xl border border-slate-200 p-4">
                        <label class="flex items-center gap-3 text-sm text-slate-700">
                            <input type="hidden" name="activo" value="0">
                            <input type="checkbox" name="activo" value="1" checked class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <span>Crear usuario habilitado</span>
                        </label>
                        <label class="mt-3 flex items-center gap-3 text-sm text-slate-700">
                            <input type="hidden" name="puede_editar_precios" value="0">
                            <input type="checkbox" name="puede_editar_precios" value="1" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <span>Permitir edici?n de precios si el rol es recolector</span>
                        </label>
                    </div>

                    <input name="password" type="password" placeholder="Contrase?a" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    <input name="password_confirmation" type="password" placeholder="Confirmar contrase?a" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>

                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        Guardar usuario
                    </button>
                </form>
            </div>

            <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Prendas por mes</h2>
                <p class="mt-1 text-sm text-slate-500">Suma prendas del mes actual incluyendo periodos ya cerrados.</p>

                <div class="mt-4 space-y-3">
                    @forelse ($resumenMensualPrendas as $item)
                        <div class="rounded-2xl border border-slate-200 px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $item['prenda'] }}</p>
                                    <p class="text-sm text-slate-500">{{ $item['cantidad'] }} prendas</p>
                                </div>
                                <p class="text-sm font-semibold text-emerald-700">$ {{ number_format($item['total'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">A?n no hay producci?n registrada este mes.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-8">
            <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">Usuarios registrados</h2>
                    <p class="mt-1 text-sm text-slate-500">Edita rol, estado y permiso de precios, o usa los botones de habilitar e inhabilitar.</p>
                </div>
                <div class="space-y-4 p-6">
                    @forelse ($usuarios as $usuario)
                        <div class="rounded-[1.5rem] border border-slate-200 p-4">
                            <form action="{{ route('admin.usuarios.update', $usuario) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="grid gap-3 md:grid-cols-2">
                                    <input name="name" type="text" value="{{ $usuario->name }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                                    <input name="email" type="email" value="{{ $usuario->email }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                                    <input name="cedula" type="text" value="{{ $usuario->cedula }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                                    <input name="contacto" type="text" value="{{ $usuario->contacto }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                                    <select name="rol" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                                        <option value="usuario" @selected($usuario->obtenerRol() === 'usuario')>Usuario</option>
                                        <option value="recolector" @selected($usuario->obtenerRol() === 'recolector')>Recolector</option>
                                        <option value="admin" @selected($usuario->obtenerRol() === 'admin')>Administrador</option>
                                        <option value="programador" @selected($usuario->obtenerRol() === 'programador')>Programador</option>
                                    </select>
                                    <input name="password" type="password" placeholder="Nueva contrase?a opcional" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                                    <div class="rounded-2xl border border-slate-200 p-4 text-sm text-slate-700">
                                        <input type="hidden" name="activo" value="0">
                                        <label class="flex items-center gap-3">
                                            <input type="checkbox" name="activo" value="1" @checked($usuario->activo) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                            <span>Usuario habilitado</span>
                                        </label>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 p-4 text-sm text-slate-700 md:col-span-2">
                                        <input type="hidden" name="puede_editar_precios" value="0">
                                        <label class="flex items-center gap-3">
                                            <input type="checkbox" name="puede_editar_precios" value="1" @checked($usuario->puede_editar_precios) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                            <span>Permitir edici?n de precios cuando el rol sea recolector</span>
                                        </label>
                                    </div>
                                    <input name="password_confirmation" type="password" placeholder="Confirmar nueva contrase?a" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm md:col-span-2">
                                </div>
                                <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                                        <span>{{ $usuario->name }} | {{ $usuario->c?dula ?: 'Sin c?dula' }} | {{ $usuario->contacto ?: 'Sin contacto' }}</span>
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $usuario->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ $usuario->activo ? 'Habilitado' : 'Inhabilitado' }}
                                        </span>
                                        @if ($usuario->esRecolector())
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $usuario->puede_editar_precios ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $usuario->puede_editar_precios ? 'Puede editar precios' : 'Precio fijo' }}
                                            </span>
                                        @endif
                                    </div>
                                    <button class="rounded-full bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                                        Guardar cambios
                                    </button>
                                </div>
                            </form>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <form action="{{ route('admin.usuarios.toggle-status', $usuario) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button onclick="return confirm('Ã‚Â¿Cambiar el estado de este usuario?')" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $usuario->activo ? 'border-amber-200 text-amber-700 hover:bg-amber-50' : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' }}">
                                        {{ $usuario->activo ? 'Inhabilitar' : 'Habilitar' }}
                                    </button>
                                </form>

                                @if ($usuario->esRecolector())
                                    <form action="{{ route('admin.usuarios.toggle-precios', $usuario) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-full border border-sky-200 px-4 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-50">
                                            {{ $usuario->puede_editar_precios ? 'Bloquear precios' : 'Habilitar precios' }}
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('admin.usuarios.destroy', $usuario) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button onclick="return confirm('Ã‚Â¿Eliminar este usuario?')" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No hay usuarios registrados.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">?ltimos registros activos</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-slate-500">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Usuario</th>
                                <th class="px-6 py-4 font-semibold">Prenda</th>
                                <th class="px-6 py-4 font-semibold">Cantidad</th>
                                <th class="px-6 py-4 font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($ultimasProducciones as $item)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $item->user->name ? 'Sin usuario' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $item->prenda->nombre ? 'Sin prenda' }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $item->cantidad }}</td>
                                    <td class="px-6 py-4 font-semibold text-emerald-700">$ {{ number_format($item->total, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">No hay producci?n activa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">Quincenas cerradas</h2>
                    <p class="mt-1 text-sm text-slate-500">Consulta periodos guardados como AÃƒâ€˜O/MES/QUINCENA.</p>
                </div>
                <div class="space-y-3 p-6">
                    @forelse ($periodosCerrados as $periodo)
                        <a href="{{ route('admin.reportes.periodo', $periodo->periodo) }}" class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-4 transition hover:bg-slate-50">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $periodo->periodo }}</p>
                                <p class="text-sm text-slate-500">{{ $periodo->total_prendas }} prendas registradas</p>
                            </div>
                            <p class="text-sm font-semibold text-emerald-700">$ {{ number_format($periodo->total_general, 0, ',', '.') }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">Todav?a no se ha cerrado ninguna quincena.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection





