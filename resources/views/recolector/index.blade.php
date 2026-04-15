@extends('layouts.app')

@section('title', 'Recolector')

@section('content')
<div
    x-data="recolectorForm({
        clientes: @js($clientes->map(fn ($cliente) => [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'nit_cedula' => $cliente->nit_cedula,
            'celular' => $cliente->celular,
            'direccion' => $cliente->direccion,
        ])->values()),
        prendas: @js($prendas->map(fn ($prenda) => [
            'id' => $prenda->id,
            'nombre' => $prenda->nombre,
            'tipo' => $prenda->tipo,
            'precio' => (float) $prenda->precio,
        ])->values()),
        fechaIngreso: '{{ $fechaIngreso->format('d/m/Y H:i') }}',
        clienteInicial: @js(old('cliente_id', $clientePreseleccionado)),
        oldItems: @js(old('items', [])),
        puedeEditarPrecios: @js($puedeEditarPrecios),
    })"
    class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8"
>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-amber-700">Recolector</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Ingreso de factura para {{ $user->name }}</h1>
            <p class="mt-2 text-sm text-slate-500">Registra pedidos por cliente, calcula cantidades y genera el valor total de cada factura.</p>
        </div>
        <div class="rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-900">
            Fecha y hora actual: {{ $fechaIngreso->format('d/m/Y H:i') }}
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($clientes->isEmpty() || $prendas->isEmpty())
        <div class="rounded-[1.75rem] border border-amber-200 bg-amber-50 px-6 py-5 text-sm text-amber-900">
            @if ($clientes->isEmpty() && $prendas->isEmpty())
                Crea al menos un cliente y espera a que administracion cargue prendas activas del recolector para registrar facturas.
            @elseif ($clientes->isEmpty())
                Todavia no hay clientes activos. Puedes crear uno desde esta misma pantalla y luego continuar con la factura.
            @else
                Todavia no hay prendas activas del recolector. Administracion debe habilitar al menos una para poder facturar.
            @endif
        </div>
    @endif

    <div class="grid gap-8 xl:grid-cols-[420px_1fr]">
        <div class="space-y-6">
            <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Crear cliente rapido</h2>
                <p class="mt-1 text-sm text-slate-500">Si el cliente no existe, puedes registrarlo aqui mismo sin salir del modulo.</p>

                <form action="{{ route('recolector.clientes.store') }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    <input name="nombre" type="text" placeholder="Nombre del cliente" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    <input name="nit_cedula" type="text" placeholder="NIT o C.C." class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                    <input name="celular" type="text" placeholder="Celular" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                    <input name="direccion" type="text" placeholder="Direccion" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                    <button type="submit" class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        Guardar cliente
                    </button>
                </form>
            </div>

            <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Datos de la factura</h2>

                <form action="{{ route('recolector.facturas.store') }}" method="POST" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="cliente_id" class="mb-2 block text-sm font-semibold text-slate-700">Cliente</label>
                        <select id="cliente_id" name="cliente_id" x-model="clienteId" @change="seleccionarCliente()" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required {{ $clientes->isEmpty() ? 'disabled' : '' }}>
                            <option value="">Selecciona un cliente</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Fecha y hora</label>
                            <input type="text" x-model="fechaIngreso" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" readonly>
                        </div>
                        <div>
                            <label for="fecha_entrega" class="mb-2 block text-sm font-semibold text-slate-700">Dia de entrega</label>
                            <input id="fecha_entrega" type="date" name="fecha_entrega" value="{{ old('fecha_entrega') }}" min="{{ now()->toDateString() }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                            <p class="mt-1 text-xs text-slate-500">Si no eliges fecha, se programa a 3 dias contando hoy.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Direccion</label>
                            <input type="text" :value="clienteActual.direccion || ''" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" readonly>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">NIT / C.C.</label>
                            <input type="text" :value="clienteActual.nit_cedula || ''" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" readonly>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Celular</label>
                            <input type="text" :value="clienteActual.celular || ''" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" readonly>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-500">Prendas del pedido</h3>
                                <p class="mt-1 text-sm text-slate-500">Marca cada prenda y registra la cantidad correspondiente.</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Total prendas</p>
                                <p class="text-2xl font-black text-slate-900" x-text="totalPrendas">0</p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            @foreach ($prendas as $indice => $prenda)
                                <div class="rounded-2xl border border-slate-200 px-4 py-4">
                                    <input type="hidden" name="items[{{ $indice }}][prenda_id]" value="{{ $prenda->id }}">
                                    <input type="hidden" name="items[{{ $indice }}][selected]" :value="selectedItems[{{ $prenda->id }}] ? 1 : 0">
                                    <input type="hidden" name="items[{{ $indice }}][precio_unitario]" :value="precioUnitario({{ $prenda->id }})">

                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                        <label class="flex items-center gap-3">
                                            <input type="checkbox" x-model="selectedItems[{{ $prenda->id }}]" class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                            <span>
                                                <span class="block font-semibold text-slate-900">{{ $prenda->nombre }}</span>
                                                <span class="text-sm text-slate-500">{{ $prenda->tipo ?: 'Sin tipo' }} | $ {{ number_format($prenda->precio, 0, ',', '.') }}</span>
                                            </span>
                                        </label>

                                        <div class="grid gap-3 sm:grid-cols-3">
                                            <div>
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Cantidad</p>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    name="items[{{ $indice }}][cantidad]"
                                                    x-model.number="cantidades[{{ $prenda->id }}]"
                                                    :disabled="!selectedItems[{{ $prenda->id }}]"
                                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                                >
                                            </div>
                                            <div>
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                                    {{ $puedeEditarPrecios ? 'Valor editable' : 'Valor unitario' }}
                                                </p>
                                                @if ($puedeEditarPrecios)
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        x-model.number="preciosActuales[{{ $prenda->id }}]"
                                                        :disabled="!selectedItems[{{ $prenda->id }}]"
                                                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                                    >
                                                @else
                                                    <div class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-800">
                                                        $ <span x-text="formatMoney(precioUnitario({{ $prenda->id }}))">0</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Subtotal</p>
                                                <div class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-800">
                                                    $ <span x-text="formatMoney(subtotalPrenda({{ $prenda->id }}))">0</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 p-4">
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-500">Observaciones adicionales</h3>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach (['Faltan botones', 'Falta cinturon', 'Esta manchado', 'Esta descolorido', 'Esta roto'] as $observacion)
                                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                    <input type="checkbox" name="observaciones[]" value="{{ $observacion }}" class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                    <span>{{ $observacion }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-3xl bg-slate-900 px-5 py-5 text-white">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Valor total de la factura</p>
                                <p class="mt-2 text-3xl font-black">$ <span x-text="formatMoney(totalFactura)">0</span></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Cantidad total</p>
                                <p class="mt-2 text-3xl font-black" x-text="totalPrendas">0</p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full rounded-2xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-700" {{ $clientes->isEmpty() || $prendas->isEmpty() ? 'disabled' : '' }}>
                        Guardar factura
                    </button>
                </form>
            </div>
        </div>

        <div class="space-y-8">
            <div class="grid gap-5 md:grid-cols-3">
                <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                    <p class="text-sm uppercase tracking-[0.25em] text-slate-400">Facturas registradas</p>
                    <p class="mt-3 text-4xl font-black text-slate-900">{{ $facturas->count() }}</p>
                </div>
                <div class="rounded-[1.75rem] bg-amber-500 p-6 text-white shadow-xl">
                    <p class="text-sm uppercase tracking-[0.25em] text-amber-50">Prendas registradas</p>
                    <p class="mt-3 text-4xl font-black">{{ $facturas->sum('total_prendas') }}</p>
                </div>
                <div class="rounded-[1.75rem] bg-slate-900 p-6 text-white shadow-xl">
                    <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Valor acumulado</p>
                    <p class="mt-3 text-4xl font-black">$ {{ number_format($facturas->sum('total'), 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">Facturas recientes</h2>
                    <p class="mt-1 text-sm text-slate-500">Cada factura conserva el cliente, fecha de entrega, observaciones y detalle de prendas.</p>
                </div>

                <div class="space-y-4 p-6">
                    @forelse ($facturas as $factura)
                        <div class="rounded-[1.5rem] border border-slate-200 p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-lg font-bold text-slate-900">{{ $factura->cliente->nombre ?? 'Cliente eliminado' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Ingreso {{ optional($factura->fecha_ingreso)->format('d/m/Y H:i') }} |
                                        Entrega {{ optional($factura->fecha_entrega)->format('d/m/Y') }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $factura->direccion ?: 'Sin direccion' }} |
                                        {{ $factura->nit_cedula ?: 'Sin documento' }} |
                                        {{ $factura->celular ?: 'Sin celular' }}
                                    </p>
                                </div>
                                <div class="text-left lg:text-right">
                                    <p class="text-sm font-semibold text-slate-500">{{ $factura->total_prendas }} prendas</p>
                                    <p class="mt-1 text-2xl font-black text-emerald-700">$ {{ number_format($factura->total, 0, ',', '.') }}</p>
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl bg-slate-50 px-4 py-4">
                                <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Observaciones</p>
                                <p class="mt-2 text-sm text-slate-700">
                                    {{ filled($factura->observaciones) ? implode(', ', $factura->observaciones) : 'Sin observaciones adicionales.' }}
                                </p>
                            </div>

                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="text-left text-slate-500">
                                        <tr>
                                            <th class="pb-3 font-semibold">Prenda</th>
                                            <th class="pb-3 font-semibold">Cantidad</th>
                                            <th class="pb-3 font-semibold">Valor unitario</th>
                                            <th class="pb-3 font-semibold">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        @foreach ($factura->detalles as $detalle)
                                            <tr>
                                                <td class="py-3 font-medium text-slate-900">{{ $detalle->prenda_nombre }}</td>
                                                <td class="py-3 text-slate-600">{{ $detalle->cantidad }}</td>
                                                <td class="py-3 text-slate-600">$ {{ number_format($detalle->valor_unitario, 0, ',', '.') }}</td>
                                                <td class="py-3 font-semibold text-slate-900">$ {{ number_format($detalle->subtotal, 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Todavia no has registrado facturas como recolector.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function recolectorForm({ clientes, prendas, fechaIngreso, clienteInicial, oldItems, puedeEditarPrecios }) {
    return {
        clientes,
        prendas,
        fechaIngreso,
        puedeEditarPrecios,
        clienteId: clienteInicial ? String(clienteInicial) : '',
        clienteActual: {},
        selectedItems: {},
        cantidades: {},
        preciosActuales: {},
        init() {
            this.prendas.forEach((prenda) => {
                this.selectedItems[prenda.id] = false;
                this.cantidades[prenda.id] = 1;
                this.preciosActuales[prenda.id] = Number(prenda.precio || 0);
            });

            oldItems.forEach((item) => {
                const prendaId = Number(item.prenda_id || 0);

                if (!prendaId || !(prendaId in this.selectedItems)) {
                    return;
                }

                const marcada = ['1', 1, true, 'true', 'on'].includes(item.selected) || Number(item.cantidad || 0) > 0;

                this.selectedItems[prendaId] = marcada;
                this.cantidades[prendaId] = Number(item.cantidad || 1);

                if (item.precio_unitario !== undefined && item.precio_unitario !== null && item.precio_unitario !== '') {
                    this.preciosActuales[prendaId] = Number(item.precio_unitario || 0);
                }
            });

            this.seleccionarCliente();
        },
        seleccionarCliente() {
            this.clienteActual = this.clientes.find((cliente) => String(cliente.id) === String(this.clienteId)) || {};
        },
        precioUnitario(id) {
            return Number(this.preciosActuales[id] || 0);
        },
        subtotalPrenda(id) {
            if (!this.selectedItems[id]) {
                return 0;
            }

            return Number(this.cantidades[id] || 0) * this.precioUnitario(id);
        },
        get totalPrendas() {
            return Object.keys(this.selectedItems).reduce((total, id) => {
                if (!this.selectedItems[id]) {
                    return total;
                }

                return total + Number(this.cantidades[id] || 0);
            }, 0);
        },
        get totalFactura() {
            return Object.keys(this.selectedItems).reduce((total, id) => total + this.subtotalPrenda(id), 0);
        },
        formatMoney(value) {
            return Number(value || 0).toLocaleString('es-CO');
        },
    };
}
</script>
@endsection
