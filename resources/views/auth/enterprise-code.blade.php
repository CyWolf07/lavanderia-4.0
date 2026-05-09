@extends('layouts.app')

@section('title', 'Codigo empresarial')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="grid overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-sky-100/80 ring-1 ring-sky-100 lg:grid-cols-[1fr_0.9fr]">
        <div class="brand-hero p-8 text-white sm:p-12">
            <p class="text-sm font-semibold uppercase tracking-[0.4em] text-sky-100">Acceso empresarial</p>
            <h1 class="mt-4 text-4xl font-black leading-tight text-white">Valida el dispositivo antes de iniciar sesion.</h1>
            <p class="mt-6 max-w-xl text-sm text-sky-50/95 sm:text-base">
                Este paso protege la entrada al sistema. El codigo vigente lo administra el rol programador.
            </p>
        </div>

        <div class="p-8 sm:p-12">
            <h2 class="text-3xl font-bold text-slate-900">Codigo empresarial</h2>
            <p class="mt-2 text-sm text-slate-500">Ingresa el codigo autorizado para habilitar el inicio de sesion.</p>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('codigo_empresarial') }}
                </div>
            @endif

            @if ($bloqueado)
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    Este dispositivo esta bloqueado. Contacta al programador para desbloquearlo.
                </div>
            @else
                <form method="POST" action="{{ route('enterprise-code.store') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="codigo_empresarial" class="mb-2 block text-sm font-semibold text-slate-700">Codigo empresarial</label>
                        <input id="codigo_empresarial" name="codigo_empresarial" type="password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm shadow-sm shadow-sky-100/60 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100" required autofocus>
                    </div>

                    <button class="w-full rounded-2xl bg-gradient-to-r from-sky-700 via-sky-600 to-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-200/80 transition hover:-translate-y-0.5 hover:shadow-xl">
                        Validar codigo
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
