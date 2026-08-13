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
    :root {
        --orden-font: 12px;
        --orden-title: 20px;
        --orden-gap: 10px;
        --orden-padding: 14px;
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

    .orden-print {
        font-size: var(--orden-font);
        line-height: 1.35;
    }

    .orden-print__title {
        font-size: var(--orden-title);
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
        font-size: 0.78em;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .orden-print__table {
        width: 100%;
        border-collapse: collapse;
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

    body.formato-ticket .col-unitario,
    body.formato-ticket .hide-ticket {
        display: none !important;
    }

    body.formato-ticket .orden-print__grid--2 {
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
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body.formato-carta {
            page: carta;
        }

        body.formato-media-carta {
            page: media-carta;
        }

        body.formato-ticket {
            page: ticket;
        }

        .orden-print-shell {
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
        }

        .orden-print {
            break-inside: avoid;
        }
    }
</style>
@endpush

@section('body-class')
formato-{{ $formato }} bg-slate-100 text-slate-900 antialiased
@endsection

@section('content')
    <div class="no-print mx-auto max-w-3xl space-y-4 px-4 py-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Vista previa de impresión</p>
            <h1 class="mt-1 text-xl font-black text-slate-900">Orden #{{ $numeroOrden }}</h1>
            <p class="mt-2 text-sm text-slate-600">Elige el tamaño de papel. El contenido se adaptará automáticamente.</p>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($formatos as $key => $label)
                    <a
                        href="{{ request()->fullUrlWithQuery(['formato' => $key, 'imprimir' => null]) }}"
                        class="rounded-full px-4 py-2 text-sm font-bold transition {{ $formato === $key ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="mt-5 flex flex-wrap gap-3">
                <button
                    type="button"
                    onclick="window.print()"
                    class="rounded-full bg-amber-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-amber-700"
                >
                    Imprimir orden
                </button>
                <a href="{{ $volverUrl }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                    Volver
                </a>
            </div>
        </div>
    </div>

    <div class="orden-print-shell mx-auto max-w-3xl px-4 py-6 print:mx-0 print:max-w-none print:p-0 sm:px-6">
        <article class="orden-print rounded-2xl border border-slate-200 bg-white p-5 shadow-sm print:border-0 print:p-0 print:shadow-none" style="padding: var(--orden-padding);">
            <header class="text-center">
                <p class="orden-print__label">Lavandería Exclusiva</p>
                <h1 class="orden-print__title mt-1 font-black text-slate-900">Orden de pedido</h1>
                <p class="mt-1 text-lg font-black text-amber-700">#{{ $numeroOrden }}</p>
            </header>

            <section class="orden-print__section orden-print__grid orden-print__grid--2">
                <div>
                    <p class="orden-print__label">Cliente</p>
                    <p class="font-bold text-slate-900">{{ $nombreCliente }}</p>
                </div>
                <div>
                    <p class="orden-print__label">N° cliente</p>
                    <p class="font-bold text-slate-900">#{{ $factura->numero_cliente ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="orden-print__label">Celular</p>
                    <p class="font-semibold text-slate-800">{{ $factura->celular ?: 'Sin celular' }}</p>
                </div>
                <div>
                    <p class="orden-print__label">Recolector</p>
                    <p class="font-semibold text-slate-800">{{ $factura->recolector->name ?? 'Sin recolector' }}</p>
                </div>
                <div class="hide-ticket">
                    <p class="orden-print__label">Dirección</p>
                    <p class="font-semibold text-slate-800">{{ $factura->direccion ?: 'Sin dirección' }}</p>
                </div>
                @if ($barrio)
                    <div>
                        <p class="orden-print__label">Barrio</p>
                        <p class="font-semibold text-slate-800">{{ $barrio }}</p>
                    </div>
                @endif
                <div>
                    <p class="orden-print__label">Fecha ingreso</p>
                    <p class="font-semibold text-slate-800">{{ optional($factura->fecha_ingreso)->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <p class="orden-print__label">Fecha entrega</p>
                    <p class="font-semibold text-slate-800">{{ optional($factura->fecha_entrega)->format('d/m/Y') }}</p>
                </div>
            </section>

            <section class="orden-print__section">
                <p class="orden-print__label">Detalle de prendas</p>
                <table class="orden-print__table mt-2">
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
                                <td class="font-semibold text-slate-900">{{ $detalle->prenda_nombre }}</td>
                                <td>{{ $detalle->color_prenda ?: '—' }}</td>
                                <td>{{ $detalle->cantidad }}</td>
                                <td class="col-unitario">$ {{ number_format((float) $detalle->valor_unitario, 0, ',', '.') }}</td>
                                <td class="font-semibold text-slate-900">$ {{ number_format((float) $detalle->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="orden-print__section">
                <p class="orden-print__label">Observaciones</p>
                <p class="mt-1 text-slate-800">{{ $observaciones }}</p>
            </section>

            <footer class="orden-print__section">
                <div class="orden-print__grid orden-print__grid--2">
                    <div>
                        <p class="orden-print__label">Total prendas</p>
                        <p class="text-xl font-black text-slate-900">{{ $factura->total_prendas }}</p>
                    </div>
                    <div class="text-right">
                        <p class="orden-print__label">Valor total</p>
                        <p class="text-2xl font-black text-emerald-700">$ {{ number_format((float) $factura->total, 0, ',', '.') }}</p>
                    </div>
                </div>
                <p class="mt-4 text-center text-slate-600">¡Gracias por escoger nuestro servicio!</p>
                <p class="text-center font-semibold text-slate-800">Lavandería Exclusiva — a su servicio</p>
            </footer>
        </article>
    </div>

@push('scripts')
    @if ($autoPrint)
        <script>
            window.addEventListener('load', () => {
                setTimeout(() => window.print(), 350);
            });
        </script>
    @endif
@endpush
@endsection
