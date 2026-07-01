@extends('layouts.app')

@section('title', 'Editar orden #' . str_pad((string) $factura->numero_orden, 6, '0', STR_PAD_LEFT))

@section('content')
<div
    x-data="editFacturaForm({
        clientes: @js($clientes->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'numero_cliente' => $c->numero_cliente, 'celular' => $c->celular, 'direccion' => $c->direccion])->values()),
        prendas:  @js($prendas->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre, 'tipo' => $p->tipo, 'precio' => (float) $p->precio])->values()),
        itemsIniciales: @js($factura->detalles->map(fn ($d) => ['prenda_id' => $d->recolector_prenda_id, 'cantidad' => $d->cantidad, 'precio_unitario' => (float) $d->valor_unitario])->values()),
        clienteIdInicial: @js($factura->cliente_id),
    })"
    class="mx-auto max-w-4xl space-y-8 px-4 py-8 sm:px-6 lg:px-8"
>
    {{-- Cabecera --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            ← Volver
        </a>
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-amber-700">Editar orden</p>
            <h1 class="mt-1 text-2xl font-black text-slate-900">
                Orden #{{ str_pad((string) $factura->numero_orden, 6, '0', STR_PAD_LEFT) }}
                <span class="text-base font-normal text-slate-500">— {{ $factura->recolector->name ?? 'Recolector eliminado' }}</span>
            </h1>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('admin.facturas-recolector.update', $factura) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Cliente --}}
        <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
            <h2 class="text-base font-bold text-slate-900">Cliente</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Selecciona el cliente</label>
                    <select name="cliente_id" x-model="clienteId" @change="seleccionarCliente()"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                        <option value="">Selecciona un cliente</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected(old('cliente_id', $factura->cliente_id) == $cliente->id)>
                                {{ $cliente->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Dirección</label>
                    <input type="text" :value="clienteActual.direccion ?? ''" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm" readonly>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">N° Cliente</label>
                    <input type="text" :value="clienteActual.numero_cliente ?? ''" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm" readonly>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Celular</label>
                    <input type="text" :value="clienteActual.celular ?? ''" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm" readonly>
                </div>
            </div>
        </div>

        {{-- Fecha entrega --}}
        <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
            <h2 class="text-base font-bold text-slate-900">Fechas</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Fecha de ingreso (referencia)</label>
                    <input type="text" value="{{ optional($factura->fecha_ingreso)->format('d/m/Y H:i') }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm" readonly>
                </div>
                <div>
                    <label for="fecha_entrega" class="mb-2 block text-sm font-semibold text-slate-700">Fecha de entrega</label>
                    <input id="fecha_entrega" type="date" name="fecha_entrega"
                        value="{{ old('fecha_entrega', optional($factura->fecha_entrega)->format('Y-m-d')) }}"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                </div>
            </div>
        </div>

        {{-- Prendas --}}
        <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
            <h2 class="text-base font-bold text-slate-900">Prendas</h2>

            {{-- Selector de prendas --}}
            <div class="mt-4 flex gap-3">
                <select x-model="selectedPrendaId" class="flex-1 rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="">Selecciona una prenda para agregar</option>
                    <template x-for="prenda in prendasDisponibles" :key="prenda.id">
                        <option :value="String(prenda.id)" x-text="prenda.nombre + ' | $' + prenda.precio.toLocaleString('es-CO')"></option>
                    </template>
                </select>
                <button type="button" @click="agregarPrenda()"
                    :disabled="!selectedPrendaId"
                    class="rounded-2xl bg-amber-600 px-5 py-3 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-40">
                    Agregar
                </button>
            </div>

            {{-- Lista de prendas agregadas --}}
            <div class="mt-4 space-y-3">
                <template x-for="(item, index) in items" :key="item.key">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-semibold text-slate-900" x-text="nombrePrenda(item.prenda_id)"></p>
                            <button type="button" @click="eliminarPrenda(item.key)"
                                class="rounded-full border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                Quitar
                            </button>
                        </div>
                        <div class="mt-3 grid gap-3 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Cantidad</label>
                                <input type="number" min="1" x-model.number="item.cantidad"
                                    :name="'items[' + index + '][cantidad]'"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Precio unitario</label>
                                <input type="number" min="0" step="0.01" x-model.number="item.precio_unitario"
                                    :name="'items[' + index + '][precio_unitario]'"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Subtotal</label>
                                <div class="rounded-2xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 ring-1 ring-slate-200">
                                    $ <span x-text="(item.cantidad * item.precio_unitario).toLocaleString('es-CO')"></span>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" :name="'items[' + index + '][prenda_id]'" :value="item.prenda_id">
                    </div>
                </template>

                <div x-show="!items.length" class="rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">
                    Agrega al menos una prenda.
                </div>
            </div>

            {{-- Totales --}}
            <div class="mt-4 rounded-2xl bg-slate-900 px-5 py-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-widest text-slate-400">Total prendas</p>
                        <p class="mt-1 text-2xl font-black" x-text="totalPrendas"></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase tracking-widest text-slate-400">Valor total</p>
                        <p class="mt-1 text-2xl font-black">$ <span x-text="totalFactura.toLocaleString('es-CO')"></span></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Observaciones --}}
        <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
            <h2 class="text-base font-bold text-slate-900">Observaciones</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach (['Faltan botones', 'Falta cinturón', 'Está manchado', 'Está descolorido', 'Está roto'] as $obs)
                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                        <input type="checkbox" name="observaciones[]" value="{{ $obs }}"
                            class="h-4 w-4 rounded border-slate-300 text-amber-600"
                            @checked(in_array($obs, old('observaciones', $factura->observaciones ?? [])))>
                        <span>{{ $obs }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Botones --}}
        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-40"
                :disabled="!items.length || !clienteId">
                Guardar cambios
            </button>
            <a href="{{ route('admin.dashboard') }}" class="rounded-2xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
function editFacturaForm({ clientes, prendas, itemsIniciales, clienteIdInicial }) {
    return {
        clientes,
        prendas,
        clienteId: clienteIdInicial ? String(clienteIdInicial) : '',
        clienteActual: {},
        selectedPrendaId: '',
        items: [],
        nextKey: 0,

        init() {
            this.seleccionarCliente();

            itemsIniciales.forEach((item) => {
                const prenda = this.prendas.find(p => Number(p.id) === Number(item.prenda_id));
                if (!prenda) return;
                this.items.push({
                    key: this.nextKey++,
                    prenda_id: Number(item.prenda_id),
                    cantidad: Number(item.cantidad),
                    precio_unitario: Number(item.precio_unitario),
                });
            });
        },

        seleccionarCliente() {
            this.clienteActual = this.clientes.find(c => String(c.id) === String(this.clienteId)) || {};
        },

        nombrePrenda(id) {
            return this.prendas.find(p => Number(p.id) === Number(id))?.nombre ?? 'Prenda no disponible';
        },

        agregarPrenda() {
            const id = Number(this.selectedPrendaId);
            if (!id || this.items.some(i => Number(i.prenda_id) === id)) return;
            const prenda = this.prendas.find(p => Number(p.id) === id);
            if (!prenda) return;
            this.items.push({ key: this.nextKey++, prenda_id: id, cantidad: 1, precio_unitario: prenda.precio });
            this.selectedPrendaId = '';
        },

        eliminarPrenda(key) {
            this.items = this.items.filter(i => i.key !== key);
        },

        get prendasDisponibles() {
            const ids = this.items.map(i => Number(i.prenda_id));
            return this.prendas.filter(p => !ids.includes(Number(p.id)));
        },

        get totalPrendas() {
            return this.items.reduce((t, i) => t + Math.max(0, Number(i.cantidad)), 0);
        },

        get totalFactura() {
            return this.items.reduce((t, i) => t + Math.max(0, Number(i.cantidad)) * Math.max(0, Number(i.precio_unitario)), 0);
        },
    };
}
</script>
@endsection
