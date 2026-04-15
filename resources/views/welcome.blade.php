@extends('layouts.app')

@section('title', 'Lavanderia Registro')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="brand-hero overflow-hidden rounded-[2rem] text-white shadow-2xl shadow-sky-200/70">
        <div class="grid gap-10 px-8 py-12 lg:grid-cols-[1.1fr_0.9fr] lg:px-14 lg:py-16">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.45em] text-sky-100">Sistema de lavanderia</p>
                <h1 class="mt-5 max-w-3xl text-4xl font-black leading-tight text-white sm:text-5xl">
                    Registra la produccion por empleado y cierra cada quincena con informe listo para imprimir.
                </h1>
                <p class="mt-6 max-w-2xl text-base text-sky-50/95">
                    El sistema separa accesos por rol: administrador, programador y usuario. Guarda usuarios, prendas, totales diarios y pagos por quincena en historial.
                </p>
                <div class="mt-10 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <a href="{{ route('login') }}" class="brand-button-secondary rounded-full border-white/30 bg-white/95 px-6 py-3 text-slate-900 shadow-lg shadow-slate-900/10 hover:border-white hover:bg-white">
                        Iniciar sesion
                    </a>
                    <a href="{{ route('register') }}" class="rounded-full border border-white/35 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur hover:bg-white/15">
                        Crear usuario
                    </a>
                </div>
                <div class="mt-10 flex flex-wrap gap-3 text-sm">
                    <span class="rounded-full border border-white/20 bg-white/10 px-4 py-2 font-semibold backdrop-blur">Interfaz clara</span>
                    <span class="rounded-full border border-white/20 bg-white/10 px-4 py-2 font-semibold backdrop-blur">Datos por quincena</span>
                    <span class="rounded-full border border-white/20 bg-white/10 px-4 py-2 font-semibold backdrop-blur">Reportes listos</span>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-3xl border border-white/15 bg-white/12 p-6 backdrop-blur-md">
                    <p class="text-sm font-semibold text-sky-100">Usuarios</p>
                    <h2 class="mt-3 text-2xl font-bold text-white">Registro completo</h2>
                    <p class="mt-2 text-sm text-sky-50/95">Nombre, cedula, contacto y rol en una sola administracion.</p>
                </div>
                <div class="rounded-3xl border border-white/15 bg-white/12 p-6 backdrop-blur-md">
                    <p class="text-sm font-semibold text-emerald-100">Produccion</p>
                    <h2 class="mt-3 text-2xl font-bold text-white">Totales por dia</h2>
                    <p class="mt-2 text-sm text-sky-50/95">Cada fila resume el ingreso diario y el total acumulado de la quincena.</p>
                </div>
                <div class="rounded-3xl border border-white/15 bg-white/12 p-6 backdrop-blur-md">
                    <p class="text-sm font-semibold text-sky-100">Cierres</p>
                    <h2 class="mt-3 text-2xl font-bold">Año/Mes/Quincena</h2>
                    <p class="mt-2 text-sm text-sky-50/95">El historial queda guardado por periodos para consulta posterior.</p>
                </div>
                <div class="rounded-3xl border border-white/15 bg-white/12 p-6 backdrop-blur-md">
                    <p class="text-sm font-semibold text-emerald-100">Informes</p>
                    <h2 class="mt-3 text-2xl font-bold text-white">Impresion inmediata</h2>
                    <p class="mt-2 text-sm text-sky-50/95">Cerrar quincena genera el reporte por empleados listo para imprimir.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
