@extends('layouts.app')

@section('title', 'Registro')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-sky-100/80 ring-1 ring-sky-100">
        <div class="brand-hero border-b border-white/10 px-8 py-8 text-white">
            <p class="text-sm font-semibold uppercase tracking-[0.35em] text-sky-100">Registro</p>
            <h1 class="mt-3 text-3xl font-black text-white">Crear una cuenta para el sistema de lavanderia</h1>
            <p class="mt-3 max-w-2xl text-sm text-sky-50/95">
                El registro publico crea usuarios de produccion. Solo si este es el primer acceso del sistema se habilita elegir un rol administrativo.
            </p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="grid gap-5 px-8 py-8 sm:grid-cols-2">
            @csrf

            <div class="sm:col-span-2">
                @if ($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif
            </div>

            <div class="sm:col-span-2">
                <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Nombre completo</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm shadow-sm shadow-sky-100/60 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100" required>
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Correo</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm shadow-sm shadow-sky-100/60 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100" required>
            </div>

            <div>
                <label for="cedula" class="mb-2 block text-sm font-semibold text-slate-700">Cedula</label>
                <input id="cedula" name="cedula" type="text" value="{{ old('cedula') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm shadow-sm shadow-sky-100/60 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100">
            </div>

            <div class="sm:col-span-2">
                <label for="contacto" class="mb-2 block text-sm font-semibold text-slate-700">Contacto</label>
                <input id="contacto" name="contacto" type="text" value="{{ old('contacto') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm shadow-sm shadow-sky-100/60 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100">
            </div>

            <div class="sm:col-span-2">
                <label for="rol" class="mb-2 block text-sm font-semibold text-slate-700">Rol</label>
                @if ($puedeElegirRol ?? false)
                    <select id="rol" name="rol" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm shadow-sm shadow-sky-100/60 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100">
                        <option value="admin">Administrador</option>
                        <option value="programador">Programador</option>
                        <option value="usuario">Usuario</option>
                        <option value="recolector">Recolector</option>
                    </select>
                @else
                    <input value="usuario" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500" readonly>
                    <input type="hidden" name="rol" value="usuario">
                @endif
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Contrasena</label>
                <input id="password" name="password" type="password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm shadow-sm shadow-sky-100/60 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100" required>
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-700">Confirmar contrasena</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm shadow-sm shadow-sky-100/60 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100" required>
            </div>

            <div class="sm:col-span-2 flex flex-col gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">Ya tengo una cuenta</a>
                <button class="rounded-2xl bg-gradient-to-r from-sky-700 via-sky-600 to-emerald-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-200/80 transition hover:-translate-y-0.5 hover:shadow-xl">
                    Crear cuenta
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
