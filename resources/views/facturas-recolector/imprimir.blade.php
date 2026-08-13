@extends('layouts.print')

@section('title', 'Orden #' . str_pad((string) ($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT))

@php
    $numeroOrden = str_pad((string) ($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT);
    $nombreCliente = $factura->cliente->nombre ?? 'Cliente';
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
        --orden-font: 12px;
        --orden-title: 20px;
        --orden-gap: 10px;
        --orden-padding: 14px;
    }

    body {
        margin: 0;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        font-size: var(--orden-font);
        line-height: 1.35;
        color: #0f172a;
        background: #f1f5f9;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    body.formato-media-carta {
        --orden-font: 11px;
        --orden-title: 17px;
        --orden-gap: 8px;
        --orden-padding: 12px;
    }

    body.formato-ticket {
        --orden-font: 9px;
        --orden-title: 13px;
        --orden-gap: 5px;
        --orden-padding: 8px;
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
        max-width: 140px;
        height: auto;
        max-height: 90px;
        margin: 0 auto 0.5rem;
        object-fit: contain;
    }

    body.formato-ticket .orden-print__logo {
        max-width: 100px;
        max-height: 65px;
    }

    .orden-print__brand {
        margin: 0;
        font-size: 0.78em;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
        text-align: center;
    }

    .orden-print__title {
        margin: 0.25rem 0 0;
        font-size: var(--orden-title);
        font-weight: 800;
        text-align: center;
        color: #0f172a;
    }

    .orden-print__orden {
        margin: 0.25rem 0 0;
        font-size: 1.1em;
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
        gap: var(--orden-gap);
    }

    .orden-print__grid--2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .orden-print__label {
        margin: 0;
        font-size: 0.78em;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .orden-print__value {
        margin: 0.15rem 0 0;
        color: #0f172a;
    }

    .orden-print__value--bold {
        font-weight: 700;
    }

    .orden-print__table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.5rem;
    }

    .orden-print__table th,
    .orden-print__table td {
        padding: 0.35em 0.25em;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: top;
    }

    .orden-print__table th {
        font-size: 0.82em;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #475569;
    }

    .orden-print__footer-note {
        margin: 1rem 0 0;
        text-align: center;
        color: #475569;
    }

    .orden-print__footer-brand {
        margin: 0.25rem 0 0;
        text-align: center;
        font-weight: 600;
        color: #0f172a;
    }

    .orden-print__totals {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--orden-gap);
    }

    .orden-print__total-value {
        margin: 0.15rem 0 0;
        font-size: 1.4em;
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
    body.formato-ticket .orden-print__totals {
        grid-template-columns: 1fr;
    }

    @page carta {
        size: letter portrait;
        margin: 12mm;
    }

    @page media-carta {
        size: 5.5in 8.5in portrait;
        margin: 8mm;
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
        }

        body.formato-carta { page: carta; }
        body.formato-media-carta { page: media-carta; }
        body.formato-ticket { page: ticket; }

        .orden-print-shell {
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .orden-print {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
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
                Revisa los datos abajo antes de imprimir. Cuando todo esté correcto, pulsa <strong>Imprimir orden</strong>.
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
                <button type="button" onclick="window.print()" class="orden-btn orden-btn--primary">
                    Imprimir orden
                </button>
                <a href="{{ $volverUrl }}" class="orden-btn orden-btn--secondary">
                    Volver
                </a>
            </div>
        </div>
    </div>

    <div class="orden-print-shell">
        <article class="orden-print">
            <header style="text-align: center;">
                <img
                    src="{{ asset('images/logo-lavanderia-exclusiva.png') }}"
                    alt="Lavandería Exclusiva"
                    class="orden-print__logo"
                >
                <p class="orden-print__brand">Lavandería Exclusiva</p>
                <h1 class="orden-print__title">Orden de pedido</h1>
                <p class="orden-print__orden">#{{ $numeroOrden }}</p>
            </header>

            <section class="orden-print__section orden-print__grid orden-print__grid--2">
                <div>
                    <p class="orden-print__label">Cliente</p>
                    <p class="orden-print__value orden-print__value--bold">{{ $nombreCliente }}</p>
                </div>
                <div>
                    <p class="orden-print__label">N° cliente</p>
                    <p class="orden-print__value orden-print__value--bold">#{{ $factura->numero_cliente ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="orden-print__label">Celular</p>
                    <p class="orden-print__value">{{ $factura->celular ?: 'Sin celular' }}</p>
                </div>
                <div>
                    <p class="orden-print__label">Recolector</p>
                    <p class="orden-print__value">{{ $factura->recolector->name ?? 'Sin recolector' }}</p>
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
                            <th>Prenda</th>
                            <th>Color</th>
                            <th>Cant.</th>
                            <th class="col-unitario">Unit.</th>
                            <th>Subtotal</th>
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
@endsection
