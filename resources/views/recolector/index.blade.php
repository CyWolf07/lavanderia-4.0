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
        numeroFactura: @js($siguienteNumeroFactura),
    })"
    :class="isTouchDevice ? 'touch-ui' : ''"
    class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8"
>
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-amber-700">Recolector</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Ingreso de factura para {{ $user->name }}</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500">Selecciona el cliente, agrega las prendas desde una lista y revisa el resumen completo antes de guardar la factura.</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-900">
                Factura #<span x-text="formatInvoiceNumber(numeroFactura)">{{ str_pad((string) $siguienteNumeroFactura, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                Fecha y hora actual: {{ $fechaIngreso->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <div
        class="rounded-[1.5rem] border px-5 py-4"
        :class="isTouchDevice ? 'border-sky-200 bg-sky-50' : 'border-slate-200 bg-white/80'"
    >
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">Modo detectado</p>
                <p class="mt-1 text-lg font-bold text-slate-900" x-text="deviceLabel">Escritorio</p>
            </div>
            <p class="max-w-2xl text-sm text-slate-600" x-text="deviceMessage">
                Controles optimizados para mouse y teclado.
            </p>
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

    <div class="grid gap-8" :class="isTouchDevice ? 'xl:grid-cols-1' : 'xl:grid-cols-[420px_1fr]'">
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

                    <div class="grid gap-4 md:grid-cols-2" :class="isTouchDevice ? 'md:grid-cols-1' : ''">
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

                    <div class="grid gap-4 md:grid-cols-2" :class="isTouchDevice ? 'md:grid-cols-1' : ''">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Direccion</label>
                            <input type="text" :value="clienteActual.direccion || ''" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" readonly>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">NIT / C.C.</label>
                            <input type="text" :value="clienteActual.nit_cedula || ''" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" readonly>
                        </div>
                        <div class="md:col-span-2" :class="isTouchDevice ? 'md:col-span-1' : ''">
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Celular</label>
                            <input type="text" :value="clienteActual.celular || ''" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" readonly>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 p-4 sm:p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-500">Prendas del pedido</h3>
                                <p class="mt-1 text-sm text-slate-500">Elige una prenda de la lista y agregala con su cantidad, valor unitario y subtotal.</p>
                            </div>
                            <div class="text-left lg:text-right">
                                <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Total prendas</p>
                                <p class="text-2xl font-black text-slate-900" x-text="totalPrendas">0</p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-4">
                            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px]" :class="isTouchDevice ? 'lg:grid-cols-1' : ''">
                                <div>
                                    <label for="prenda_selector" class="mb-2 block text-sm font-semibold text-slate-700">Lista de prendas</label>
                                    <select id="prenda_selector" x-model="selectedPrendaId" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" {{ $prendas->isEmpty() ? 'disabled' : '' }}>
                                        <option value="">Selecciona una prenda</option>
                                        <template x-for="prenda in prendasDisponibles" :key="prenda.id">
                                            <option :value="String(prenda.id)" x-text="prenda.nombre + ' | ' + (prenda.tipo || 'Sin tipo') + ' | $ ' + formatMoney(prenda.precio)"></option>
                                        </template>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button
                                        type="button"
                                        @click="agregarPrenda()"
                                        class="w-full rounded-2xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-700 disabled:cursor-not-allowed disabled:bg-amber-300"
                                        :disabled="!selectedPrendaId"
                                    >
                                        Agregar prenda
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3" x-show="items.length" x-cloak>
                            <template x-for="(item, index) in items" :key="item.key">
                                <div class="rounded-[1.5rem] border border-slate-200 bg-white px-4 py-4 shadow-sm shadow-slate-100/70">
                                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                        <div class="space-y-1">
                                            <p class="text-lg font-bold text-slate-900" x-text="nombrePrenda(item.prenda_id)"></p>
                                            <p class="text-sm text-slate-500" x-text="tipoPrenda(item.prenda_id)"></p>
                                        </div>

                                        <button
                                            type="button"
                                            @click="eliminarPrenda(item.key)"
                                            class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50"
                                        >
                                            Quitar
                                        </button>
                                    </div>

                                    <div class="mt-4 grid gap-3 md:grid-cols-3" :class="isTouchDevice ? 'md:grid-cols-1' : ''">
                                        <div>
                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Cantidad</label>
                                            <input
                                                type="number"
                                                min="1"
                                                x-model.number="item.cantidad"
                                                :name="'items[' + index + '][cantidad]'"
                                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                                required
                                            >
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                                {{ $puedeEditarPrecios ? 'Valor editable' : 'Valor unitario' }}
                                            </label>
                                            <input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                x-model.number="item.precio_unitario"
                                                :name="'items[' + index + '][precio_unitario]'"
                                                :readonly="!puedeEditarPrecios"
                                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                                :class="!puedeEditarPrecios ? 'bg-slate-100 text-slate-700' : ''"
                                                required
                                            >
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Subtotal</label>
                                            <div class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-800">
                                                $ <span x-text="formatMoney(subtotalItem(item))">0</span>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" :name="'items[' + index + '][prenda_id]'" :value="item.prenda_id">
                                    <input type="hidden" :name="'items[' + index + '][selected]'" value="1">
                                </div>
                            </template>
                        </div>

                        <div x-show="!items.length" x-cloak class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">
                            Aun no has agregado prendas a la factura.
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 p-4 sm:p-5">
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-500">Observaciones adicionales</h3>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2" :class="isTouchDevice ? 'sm:grid-cols-1' : ''">
                            @foreach (['Faltan botones', 'Falta cinturon', 'Esta manchado', 'Esta descolorido', 'Esta roto'] as $observacion)
                                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                    <input type="checkbox" name="observaciones[]" value="{{ $observacion }}" class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                    <span>{{ $observacion }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-[1.75rem] border border-amber-200 bg-amber-50 px-5 py-5">
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.26em] text-amber-700">Resumen antes de guardar</p>
                                <div class="mt-3 space-y-2 text-sm text-slate-700">
                                    <p><span class="font-semibold text-slate-900">Cliente:</span> <span x-text="clienteActual.nombre || 'Pendiente por seleccionar'"></span></p>
                                    <p><span class="font-semibold text-slate-900">Numero de factura:</span> #<span x-text="formatInvoiceNumber(numeroFactura)">{{ str_pad((string) $siguienteNumeroFactura, 6, '0', STR_PAD_LEFT) }}</span></p>
                                    <p><span class="font-semibold text-slate-900">Valor total:</span> $ <span x-text="formatMoney(totalFactura)">0</span></p>
                                </div>
                            </div>

                            <div class="min-w-0 flex-1 xl:max-w-xl">
                                <p class="text-sm font-semibold text-slate-900">Prendas seleccionadas</p>
                                <div class="mt-3 rounded-[1.25rem] bg-white/80 px-4 py-4 ring-1 ring-amber-100">
                                    <template x-if="resumenPrendas.length">
                                        <div class="space-y-2">
                                            <template x-for="item in resumenPrendas" :key="item.key">
                                                <div class="flex items-start justify-between gap-4 text-sm text-slate-700">
                                                    <p>
                                                        <span class="font-semibold text-slate-900" x-text="item.nombre"></span>
                                                        <span x-text="' x ' + item.cantidad"></span>
                                                    </p>
                                                    <p class="font-semibold text-slate-900">$ <span x-text="formatMoney(item.subtotal)"></span></p>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!resumenPrendas.length">
                                        <p class="text-sm text-slate-500">Agrega prendas para ver aqui el resumen del pedido.</p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-slate-900 px-5 py-5 text-white">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Valor total de la factura</p>
                                <p class="mt-2 text-3xl font-black">$ <span x-text="formatMoney(totalFactura)">0</span></p>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Cantidad total</p>
                                <p class="mt-2 text-3xl font-black" x-text="totalPrendas">0</p>
                            </div>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-2xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-700 disabled:cursor-not-allowed disabled:bg-amber-300"
                        :disabled="!puedeGuardarFactura"
                        {{ $clientes->isEmpty() || $prendas->isEmpty() ? 'disabled' : '' }}
                    >
                        Guardar factura
                    </button>
                </form>
            </div>
        </div>

        <div class="space-y-8">
            <div class="grid gap-5 md:grid-cols-3" :class="isTouchDevice ? 'md:grid-cols-1' : ''">
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
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-700">
                                        Factura #{{ str_pad((string) $factura->id, 6, '0', STR_PAD_LEFT) }}
                                    </p>
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
function recolectorForm({ clientes, prendas, fechaIngreso, clienteInicial, oldItems, puedeEditarPrecios, numeroFactura }) {
    return {
        clientes,
        prendas,
        fechaIngreso,
        puedeEditarPrecios,
        numeroFactura,
        clienteId: clienteInicial ? String(clienteInicial) : '',
        clienteActual: {},
        selectedPrendaId: '',
        items: [],
        nextItemKey: 0,
        isTouchDevice: false,
        isMobileViewport: false,
        init() {
            this.actualizarDispositivo();
            window.addEventListener('resize', () => this.actualizarDispositivo());

            oldItems.forEach((item) => {
                const prendaId = Number(item.prenda_id || 0);
                const seleccionada = ['1', 1, true, 'true', 'on'].includes(item.selected) || Number(item.cantidad || 0) > 0;

                if (!prendaId || !seleccionada) {
                    return;
                }

                this.agregarPrenda(prendaId, {
                    cantidad: Number(item.cantidad || 1),
                    precio_unitario: item.precio_unitario !== undefined && item.precio_unitario !== null && item.precio_unitario !== ''
                        ? Number(item.precio_unitario || 0)
                        : undefined,
                });
            });

            this.seleccionarCliente();
        },
        actualizarDispositivo() {
            const pointerCoarse = window.matchMedia('(pointer: coarse)').matches;

            this.isTouchDevice = pointerCoarse || (navigator.maxTouchPoints || 0) > 0;
            this.isMobileViewport = window.innerWidth < 1024;
        },
        seleccionarCliente() {
            this.clienteActual = this.clientes.find((cliente) => String(cliente.id) === String(this.clienteId)) || {};
        },
        datosPrenda(prendaId) {
            return this.prendas.find((prenda) => Number(prenda.id) === Number(prendaId)) || null;
        },
        nombrePrenda(prendaId) {
            return this.datosPrenda(prendaId)?.nombre || 'Prenda no disponible';
        },
        tipoPrenda(prendaId) {
            return this.datosPrenda(prendaId)?.tipo || 'Sin tipo';
        },
        agregarPrenda(prendaId = this.selectedPrendaId, valores = {}) {
            const id = Number(prendaId || 0);

            if (!id || this.items.some((item) => Number(item.prenda_id) === id)) {
                return;
            }

            const prenda = this.datosPrenda(id);

            if (!prenda) {
                return;
            }

            this.items.push({
                key: this.nextItemKey++,
                prenda_id: id,
                cantidad: Math.max(1, Number(valores.cantidad || 1)),
                precio_unitario: valores.precio_unitario !== undefined
                    ? Math.max(0, Number(valores.precio_unitario || 0))
                    : Number(prenda.precio || 0),
            });

            this.selectedPrendaId = '';
        },
        eliminarPrenda(itemKey) {
            this.items = this.items.filter((item) => item.key !== itemKey);
        },
        precioUnitario(item) {
            if (!this.puedeEditarPrecios) {
                return Number(this.datosPrenda(item.prenda_id)?.precio || 0);
            }

            return Math.max(0, Number(item.precio_unitario || 0));
        },
        subtotalItem(item) {
            return Math.max(0, Number(item.cantidad || 0)) * this.precioUnitario(item);
        },
        get prendasDisponibles() {
            const idsSeleccionados = this.items.map((item) => Number(item.prenda_id));

            return this.prendas.filter((prenda) => !idsSeleccionados.includes(Number(prenda.id)));
        },
        get totalPrendas() {
            return this.items.reduce((total, item) => total + Math.max(0, Number(item.cantidad || 0)), 0);
        },
        get totalFactura() {
            return this.items.reduce((total, item) => total + this.subtotalItem(item), 0);
        },
        get resumenPrendas() {
            return this.items.map((item) => ({
                key: item.key,
                nombre: this.nombrePrenda(item.prenda_id),
                cantidad: Math.max(0, Number(item.cantidad || 0)),
                subtotal: this.subtotalItem(item),
            }));
        },
        get puedeGuardarFactura() {
            return Boolean(this.clienteId) && this.items.length > 0;
        },
        get deviceLabel() {
            if (this.isTouchDevice && this.isMobileViewport) {
                return 'Celular o pantalla tactil';
            }

            if (this.isTouchDevice) {
                return 'Pantalla tactil';
            }

            if (this.isMobileViewport) {
                return 'Pantalla pequena';
            }

            return 'Escritorio';
        },
        get deviceMessage() {
            if (this.isTouchDevice && this.isMobileViewport) {
                return 'La interfaz se organiza en una sola columna y con botones mas amplios para trabajar mejor desde celular.';
            }

            if (this.isTouchDevice) {
                return 'Se ampliaron controles y espacios para facilitar el uso en pantallas tactiles.';
            }

            if (this.isMobileViewport) {
                return 'La vista se compacta para pantallas pequenas manteniendo todos los datos visibles.';
            }

            return 'Controles optimizados para mouse y teclado, sin perder respuesta en ventanas reducidas.';
        },
        formatMoney(value) {
            return Number(value || 0).toLocaleString('es-CO');
        },
        formatInvoiceNumber(value) {
            return String(value || 1).padStart(6, '0');
        },
    };
}
</script>
@endsection
