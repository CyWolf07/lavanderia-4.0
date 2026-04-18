@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="grid overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-sky-100/80 ring-1 ring-sky-100 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="brand-hero p-8 text-white sm:p-12">
            <p class="text-sm font-semibold uppercase tracking-[0.4em] text-sky-100">Lavandería</p>
            <h1 class="mt-4 text-4xl font-black leading-tight text-white">Controla usuarios, prendas y quincenas desde un solo panel.</h1>
            <p class="mt-6 max-w-xl text-sm text-sky-50/95 sm:text-base">
                Inicia sesión con tu correo o tu cédula para registrar producción, consultar pagos por quincena y administrar el sistema según tu rol.
            </p>
            <div class="mt-8 grid gap-3 text-sm text-sky-50/95">
                <p class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">Administrador: usuarios, prendas, cierres e informes.</p>
                <p class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">Programador: acceso total y borrado de históricos.</p>
                <p class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">Usuario: registro de producción y consulta de pagos.</p>
            </div>
        </div>

        <div class="p-8 sm:p-12">
            <h2 class="text-3xl font-bold text-slate-900">Iniciar sesión</h2>
            <p class="mt-2 text-sm text-slate-500">Usa tu correo o cédula para entrar.</p>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('login') ?: $errors->first('email') ?: $errors->first('password') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="login" class="mb-2 block text-sm font-semibold text-slate-700">Correo o cédula</label>
                    <input id="login" name="login" type="text" value="{{ old('login', old('email')) }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm shadow-sm shadow-sky-100/60 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100" required>
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Contraseña</label>
                    <input id="password" name="password" type="password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm shadow-sm shadow-sky-100/60 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100" required>
                </div>

                <button class="w-full rounded-2xl bg-gradient-to-r from-sky-700 via-sky-600 to-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-200/80 transition hover:-translate-y-0.5 hover:shadow-xl">
                    Entrar al sistema
                </button>
            </form>

            <p class="mt-6 text-sm text-slate-500">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}" class="font-semibold text-sky-700 hover:text-sky-800">Registrarse</a>
            </p>
        </div>
    </div>
</div>
@endsection
