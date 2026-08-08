<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orden #{{ str_pad((string) ($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e8eef4; color: #162235; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { display: flex; justify-content: center; gap: 12px; padding: 18px; }
        .button { border: 0; border-radius: 999px; padding: 11px 20px; background: #123f74; color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; }
        .button.secondary { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .sheet { width: min(210mm, calc(100% - 24px)); min-height: 270mm; margin: 0 auto 28px; padding: 14mm; background: #fff; box-shadow: 0 12px 35px rgba(15, 23, 42, .15); }
        header { display: flex; align-items: center; justify-content: space-between; gap: 24px; border-bottom: 3px solid #50bd0b; padding-bottom: 15px; }
        .logo { width: 190px; max-height: 105px; object-fit: contain; }
        .order-heading { text-align: right; }
        .eyebrow { margin: 0 0 5px; color: #497082; font-size: 11px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 0; color: #123f74; font-size: 27px; }
        .status { display: inline-block; margin-top: 8px; padding: 5px 10px; border: 1px solid #cbd5e1; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .info { display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 12px; margin: 18px 0; }
        .info-card { padding: 12px; border: 1px solid #dce4ea; border-radius: 10px; background: #f8fafc; }
        .label { display: block; margin-bottom: 5px; color: #64748b; font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .value { font-size: 14px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { padding: 10px 8px; background: #123f74; color: #fff; text-align: left; }
        td { padding: 10px 8px; border-bottom: 1px solid #dce4ea; vertical-align: top; }
        th.number, td.number { text-align: right; white-space: nowrap; }
        .detail { margin-top: 3px; color: #64748b; font-size: 11px; }
        .notes { margin-top: 18px; padding: 13px; border: 1px solid #f3c98b; border-radius: 10px; background: #fff8eb; }
        .notes p { margin: 5px 0 0; font-size: 12px; line-height: 1.5; }
        .summary { display: flex; justify-content: flex-end; margin-top: 18px; }
        .summary-box { width: 280px; border-top: 2px solid #123f74; }
        .summary-row { display: flex; justify-content: space-between; gap: 20px; padding: 9px 0; font-size: 13px; }
        .summary-row.total { border-top: 1px solid #cbd5e1; color: #123f74; font-size: 19px; font-weight: 800; }
        footer { margin-top: 28px; border-top: 1px solid #dce4ea; padding-top: 12px; color: #64748b; font-size: 10px; text-align: center; }
        @page { size: A4; margin: 10mm; }
        @media print {
            body { background: #fff; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .toolbar { display: none; }
            .sheet { width: 100%; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
        }
        @media (max-width: 650px) {
            .sheet { padding: 20px; }
            header { align-items: flex-start; }
            .logo { width: 145px; }
            .info { grid-template-columns: 1fr; }
            table { font-size: 11px; }
        }
    </style>
</head>
<body>
    @php
        $numeroOrden = str_pad((string) ($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT);
        $observaciones = collect($factura->observaciones ?? [])->filter()->values();
    @endphp

    <div class="toolbar">
        <button type="button" class="button" onclick="window.print()">Imprimir orden</button>
        <button type="button" class="button secondary" onclick="window.close()">Cerrar</button>
    </div>

    <main class="sheet">
        <header>
            <img class="logo" src="{{ asset('images/lavanderia-exclusiva.png') }}" alt="Lavandería Exclusiva">
            <div class="order-heading">
                <p class="eyebrow">Orden de pedido</p>
                <h1>#{{ $numeroOrden }}</h1>
                <span class="status">{{ $factura->estado_factura ?? 'pendiente' }}</span>
            </div>
        </header>

        <section class="info">
            <div class="info-card">
                <span class="label">Nombre del cliente</span>
                <span class="value">{{ $factura->cliente->nombre ?? 'Cliente no disponible' }}</span>
            </div>
            <div class="info-card">
                <span class="label">Fecha de recolección</span>
                <span class="value">{{ optional($factura->fecha_ingreso)->format('d/m/Y') ?? 'No registrada' }}</span>
            </div>
            <div class="info-card">
                <span class="label">Fecha de entrega</span>
                <span class="value">{{ optional($factura->fecha_entrega)->format('d/m/Y') ?? 'No registrada' }}</span>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Tipo de prenda / detalle</th>
                    <th class="number">Cantidad</th>
                    <th class="number">Valor por prenda</th>
                    <th class="number">Valor total prenda</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($factura->detalles as $detalle)
                    <tr>
                        <td>
                            <strong>{{ $detalle->prenda_nombre }}</strong>
                            <div class="detail">Color: {{ $detalle->color_prenda ?: 'No especificado' }}</div>
                            <div class="detail">Lavado especial/estado: {{ $observaciones->isNotEmpty() ? $observaciones->implode(', ') : 'Sin novedad registrada' }}</div>
                        </td>
                        <td class="number">{{ $detalle->cantidad }}</td>
                        <td class="number">$ {{ number_format((float) $detalle->valor_unitario, 0, ',', '.') }}</td>
                        <td class="number"><strong>$ {{ number_format((float) $detalle->subtotal, 0, ',', '.') }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="4">Esta orden no tiene prendas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>

        <section class="notes">
            <span class="label">Detalle general de la orden</span>
            <p>{{ $observaciones->isNotEmpty() ? $observaciones->implode(', ') : 'Sin observaciones, daños o lavados especiales registrados.' }}</p>
        </section>

        <section class="summary">
            <div class="summary-box">
                <div class="summary-row"><span>Cantidad total de prendas</span><strong>{{ $factura->total_prendas }}</strong></div>
                <div class="summary-row total"><span>Valor total</span><span>$ {{ number_format((float) $factura->total, 0, ',', '.') }}</span></div>
            </div>
        </section>

        <footer>
            Atendido por {{ $factura->recolector->name ?? 'Lavandería Exclusiva' }} · Comprobante generado el {{ now()->format('d/m/Y H:i') }}
        </footer>
    </main>

    @if ($imprimirAutomaticamente)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
