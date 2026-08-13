@extends('layouts.print')

@section('title', 'Orden #' . str_pad((string) ($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT))

@php
    $numeroOrden = str_pad((string) ($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT);
    $barrio = $factura->cliente->barrio ?? '';
    $observaciones = filled($factura->observaciones) ? implode(', ', $factura->observaciones) : 'Sin observaciones';
    $formatos = [
        'carta'       => 'Carta',
        'media-carta' => 'Media carta',
        'ticket'      => 'Ticket (A6)',
    ];
    $volverUrl = auth()->user()->tieneRol('recolector')
        ? route('recolector.index')
        : route('admin.dashboard');
@endphp

@push('head')
<style>
    *, *::before, *::after { box-sizing: border-box; }

    :root {
        --orden-font: 11px;
        --orden-title: 18px;
        --orden-gap: 6px;
        --orden-padding: 10px;
        --page-height: 257mm;
    }

    body {
        margin: 0;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        font-size: var(--orden-font);
        line-height: 1.25;
        color: #0f172a;
        background: #f1f5f9;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    body.formato-media-carta {
        --orden-font: 10px;
        --orden-title: 16px;
        --orden-gap: 5px;
        --orden-padding: 8px;
        --page-height: 203mm;
    }

    body.formato-ticket {
        --orden-font: 8px;
        --orden-title: 11px;
        --orden-gap: 3px;
        --orden-padding: 5px;
        --page-height: 142mm;
    }

    .orden-toolbar {
        max-width: 48rem;
        margin: 0 auto;
        padding: 1.5rem 1rem;
    }

    .orden-toolbar__card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    }

    .orden-toolbar__eyebrow {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.25em;
        text-transform: uppercase;
        color: #64748b;
        margin: 0;
    }

    .orden-toolbar__title {
        margin: 0.25rem 0 0;
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f172a;
    }

    .orden-toolbar__hint {
        margin: 0.5rem 0 0;
        font-size: 0.875rem;
        color: #475569;
    }

    .orden-toolbar__formats {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .orden-toolbar__format {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #334155;
    }

    .orden-toolbar__format.is-active {
        background: #0f172a;
        border-color: #0f172a;
        color: #fff;
    }

    .orden-toolbar__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1.25rem;
    }

    .orden-btn {
        display: inline-block;
        padding: 0.625rem 1.25rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 700;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }

    .orden-btn--primary {
        background: #d97706;
        color: #fff;
    }

    .orden-btn--secondary {
        background: #fff;
        border: 1px solid #e2e8f0;
        color: #334155;
    }

    .orden-print-shell {
        max-width: 48rem;
        margin: 0 auto;
        padding: 0 1rem 1.5rem;
    }

    .orden-print-fit {
        transform-origin: top center;
    }

    .orden-print {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: var(--orden-padding);
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    }

    .orden-print__logo {
        display: block;
        width: auto;
        max-width: 110px;
        height: auto;
        max-height: 70px;
        margin: 0 auto 0.25rem;
        object-fit: contain;
    }

    body.formato-ticket .orden-print__logo {
        max-width: 72px;
        max-height: 48px;
    }

    body.formato-media-carta .orden-print__logo {
        max-width: 95px;
        max-height: 60px;
    }

    .orden-print__title {
        margin: 0;
        font-size: var(--orden-title);
        font-weight: 800;
        text-align: center;
        color: #0f172a;
    }

    .orden-print__orden {
        margin: 0.15rem 0 0;
        font-size: 1em;
        font-weight: 800;
        text-align: center;
        color: #b45309;
    }

    .orden-print__section {
        margin-top: var(--orden-gap);
        padding-top: var(--orden-gap);
        border-top: 1px dashed #cbd5e1;
    }

    .orden-print__grid {
        display: grid;
        gap: calc(var(--orden-gap) * 0.75);
    }

    .orden-print__grid--2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .orden-print__grid--3 {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .orden-print__label {
        margin: 0;
        font-size: 0.72em;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #64748b;
    }

    .orden-print__value {
        margin: 0.1rem 0 0;
        color: #0f172a;
        word-break: break-word;
    }

    .orden-print__value--bold {
        font-weight: 700;
    }

    .orden-print__table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.25rem;
        table-layout: fixed;
    }

    .orden-print__table th,
    .orden-print__table td {
        padding: 0.2em 0.15em;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: top;
        word-break: break-word;
    }

    .orden-print__table th {
        font-size: 0.72em;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #475569;
    }

    .orden-print__table .col-prenda { width: 34%; }
    .orden-print__table .col-color { width: 18%; }
    .orden-print__table .col-cant { width: 10%; }
    .orden-print__table .col-unitario { width: 18%; }
    .orden-print__table .col-subtotal { width: 20%; }

    .orden-print__footer-note {
        margin: calc(var(--orden-gap) * 0.75) 0 0;
        text-align: center;
        color: #475569;
        font-size: 0.92em;
    }

    .orden-print__footer-brand {
        margin: 0.1rem 0 0;
        text-align: center;
        font-weight: 600;
        color: #0f172a;
        font-size: 0.92em;
    }

    .orden-print__totals {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--orden-gap);
    }

    .orden-print__total-value {
        margin: 0.1rem 0 0;
        font-size: 1.15em;
        font-weight: 800;
    }

    .orden-print__total-value--money {
        color: #047857;
        text-align: right;
    }

    body.formato-ticket .col-unitario,
    body.formato-ticket .hide-ticket {
        display: none !important;
    }

    body.formato-ticket .orden-print__grid--2,
    body.formato-ticket .orden-print__grid--3,
    body.formato-ticket .orden-print__totals {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    @page carta {
        size: letter portrait;
        margin: 10mm;
    }

    @page media-carta {
        size: 5.5in 8.5in portrait;
        margin: 6mm;
    }

    @page ticket {
        size: A6 portrait;
        margin: 3mm;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        html, body {
            background: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100%;
            height: auto;
        }

        body.formato-carta { page: carta; }
        body.formato-media-carta { page: media-carta; }
        body.formato-ticket { page: ticket; }

        .orden-print-shell {
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .orden-print-fit {
            width: 100%;
            overflow: hidden;
        }

        .orden-print {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            page-break-inside: avoid;
            break-inside: avoid-page;
        }

        .orden-print__section,
        .orden-print__table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
    }
</style>
@endpush

@section('body-class')
formato-{{ $formato }}
@endsection

@section('content')
    <div class="no-print orden-toolbar">
        <div class="orden-toolbar__card">
            <p class="orden-toolbar__eyebrow">Vista previa de impresión</p>
            <h1 class="orden-toolbar__title">Orden #{{ $numeroOrden }}</h1>
            <p class="orden-toolbar__hint">
                Revisa los datos abajo antes de imprimir. Todo debe quedar en una sola hoja según el formato elegido.
            </p>

            <div class="orden-toolbar__formats">
                @foreach ($formatos as $key => $label)
                    <a
                        href="{{ request()->fullUrlWithQuery(['formato' => $key]) }}"
                        class="orden-toolbar__format {{ $formato === $key ? 'is-active' : '' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="orden-toolbar__actions">
                <button type="button" onclick="imprimirOrden()" class="orden-btn orden-btn--primary">
                    Imprimir orden
                </button>
                <a href="{{ $volverUrl }}" class="orden-btn orden-btn--secondary">
                    Volver
                </a>
            </div>
        </div>
    </div>

    <div class="orden-print-shell">
        <div class="orden-print-fit" id="orden-print-fit">
            <article class="orden-print" id="orden-print">
                <header style="text-align: center;">
                    <img
                        src="{{ asset('images/logo-lavanderia-exclusiva.png') }}"
                        alt="Lavandería Exclusiva"
                        class="orden-print__logo"
                    >
                    <h1 class="orden-print__title">Orden de pedido</h1>
                    <p class="orden-print__orden">#{{ $numeroOrden }}</p>
                </header>

                <section class="orden-print__section orden-print__grid orden-print__grid--2">
                    <div>
                        <p class="orden-print__label">N° cliente</p>
                        <p class="orden-print__value orden-print__value--bold">#{{ $factura->numero_cliente ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="orden-print__label">Celular</p>
                        <p class="orden-print__value">{{ $factura->celular ?: 'Sin celular' }}</p>
                    </div>
                    <div class="hide-ticket">
                        <p class="orden-print__label">Dirección</p>
                        <p class="orden-print__value">{{ $factura->direccion ?: 'Sin dirección' }}</p>
                    </div>
                    @if ($barrio)
                        <div>
                            <p class="orden-print__label">Barrio</p>
                            <p class="orden-print__value">{{ $barrio }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="orden-print__label">Fecha ingreso</p>
                        <p class="orden-print__value">{{ optional($factura->fecha_ingreso)->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="orden-print__label">Fecha entrega</p>
                        <p class="orden-print__value">{{ optional($factura->fecha_entrega)->format('d/m/Y') }}</p>
                    </div>
                </section>

                <section class="orden-print__section">
                    <p class="orden-print__label">Detalle de prendas</p>
                    <table class="orden-print__table">
                        <thead>
                            <tr>
                                <th class="col-prenda">Prenda</th>
                                <th class="col-color">Color</th>
                                <th class="col-cant">Cant.</th>
                                <th class="col-unitario">Unit.</th>
                                <th class="col-subtotal">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($factura->detalles as $detalle)
                                <tr>
                                    <td class="orden-print__value--bold">{{ $detalle->prenda_nombre }}</td>
                                    <td>{{ $detalle->color_prenda ?: '—' }}</td>
                                    <td>{{ $detalle->cantidad }}</td>
                                    <td class="col-unitario">$ {{ number_format((float) $detalle->valor_unitario, 0, ',', '.') }}</td>
                                    <td class="orden-print__value--bold">$ {{ number_format((float) $detalle->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>

                <section class="orden-print__section">
                    <p class="orden-print__label">Observaciones</p>
                    <p class="orden-print__value">{{ $observaciones }}</p>
                </section>

                <footer class="orden-print__section">
                    <div class="orden-print__totals">
                        <div>
                            <p class="orden-print__label">Total prendas</p>
                            <p class="orden-print__total-value">{{ $factura->total_prendas }}</p>
                        </div>
                        <div>
                            <p class="orden-print__label" style="text-align: right;">Valor total</p>
                            <p class="orden-print__total-value orden-print__total-value--money">$ {{ number_format((float) $factura->total, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <p class="orden-print__footer-note">¡Gracias por escoger nuestro servicio!</p>
                    <p class="orden-print__footer-brand">Lavandería Exclusiva — a su servicio</p>
                </footer>
            </article>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function obtenerAlturaPaginaMm() {
        const valor = getComputedStyle(document.body).getPropertyValue('--page-height').trim();
        return parseFloat(valor) || 142;
    }

    function mmAPx(mm) {
        return (mm / 25.4) * 96;
    }

    function ajustarASolaHoja() {
        const contenedor = document.getElementById('orden-print-fit');
        const orden = document.getElementById('orden-print');

        if (!contenedor || !orden) {
            return;
        }

        contenedor.style.height = '';
        contenedor.style.transform = 'none';
        orden.style.transform = 'none';

        const alturaMaxima = mmAPx(obtenerAlturaPaginaMm());
        const alturaContenido = orden.getBoundingClientRect().height;

        if (alturaContenido <= alturaMaxima) {
            return;
        }

        const escala = Math.max(0.55, alturaMaxima / alturaContenido);
        orden.style.transform = `scale(${escala})`;
        orden.style.transformOrigin = 'top center';
        contenedor.style.height = `${alturaContenido * escala}px`;
    }

    function restablecerEscala() {
        const contenedor = document.getElementById('orden-print-fit');
        const orden = document.getElementById('orden-print');

        if (!contenedor || !orden) {
            return;
        }

        contenedor.style.height = '';
        contenedor.style.transform = 'none';
        orden.style.transform = 'none';
    }

    function imprimirOrden() {
        ajustarASolaHoja();
        window.print();
    }

    window.addEventListener('beforeprint', ajustarASolaHoja);
    window.addEventListener('afterprint', restablecerEscala);
    window.addEventListener('load', ajustarASolaHoja);
    window.addEventListener('resize', ajustarASolaHoja);
</script>
@endpush
