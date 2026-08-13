@extends('layouts.app')

@section('title', 'Recolector')

@section('content')
<div
    x-data="recolectorForm({
        clientes: @js($clientes->map(fn ($cliente) => [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'numero_cliente' => $cliente->numero_cliente,
            'celular' => $cliente->celular,
            'direccion' => $cliente->direccion,
            'barrio' => $cliente->barrio,
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
        facturas: @js($facturas->map(fn ($f) => [
            'id' => $f->id,
            'numero_orden' => $f->numero_orden ?? $f->id,
            'cliente_nombre' => $f->cliente->nombre ?? 'Cliente eliminado',
            'celular' => $f->celular ?? '',
            'total' => (float)$f->total,
            'total_prendas' => $f->total_prendas,
            'estado_factura' => $f->estado_factura ?? 'pendiente',
            'detalles' => $f->detalles->map(fn($d) => [
                'prenda_nombre' => $d->prenda_nombre,
                'cantidad' => $d->cantidad,
                'color_prenda' => $d->color_prenda ?? '',
                'valor_unitario' => (float)$d->valor_unitario,
                'subtotal' => (float)$d->subtotal,
            ])->values(),
        ])->values()),
    })"
    :class="isTouchDevice ? 'touch-ui' : ''"
    class="mx-auto max-w-screen-2xl space-y-8 px-4 py-8 sm:px-6 lg:px-8"
>
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-amber-700">Recolector</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Ingreso de orden de pedido para {{ $user->name }}</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500">Selecciona el cliente, agrega las prendas desde una lista y revisa el resumen completo antes de guardar la orden de pedido.</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-900">
                Orden #<span x-text="formatInvoiceNumber(numeroFactura)">{{ str_pad((string) $siguienteNumeroFactura, 6, '0', STR_PAD_LEFT) }}</span>
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
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-700">
            <p class="font-semibold">{{ session('success') }}</p>

            @if (session('nueva_factura_id'))
                @php
                    $facturaFlash = $facturas->firstWhere('id', session('nueva_factura_id'));
                @endphp

                @if ($facturaFlash && $facturaFlash->celular)
                    @php
                        $numLimpio     = preg_replace('/\D+/', '', (string) $facturaFlash->celular);
                        $numWa         = strlen($numLimpio) === 10 ? '57' . $numLimpio : $numLimpio;
                        $numeroOrden   = str_pad((string) ($facturaFlash->numero_orden ?? $facturaFlash->id), 6, '0', STR_PAD_LEFT);
                        $nombreCliente = $facturaFlash->cliente->nombre ?? 'Cliente';
                        $valorTotal    = number_format((float) $facturaFlash->total, 0, ',', '.');

                        $lineasPrendas = '';
                        foreach ($facturaFlash->detalles as $det) {
                            $colorStr = $det->color_prenda ? ' (' . $det->color_prenda . ')' : '';
                            $lineasPrendas .= '%0A-' . rawurlencode($det->prenda_nombre . $colorStr) . ' x ' . $det->cantidad;
                        }

                        $mensaje = 'Orden de pedido: ' . $numeroOrden
                            . '%0ANombre Cliente: ' . rawurlencode($nombreCliente)
                            . '%0ATipo de Prenda: ' . $lineasPrendas
                            . '%0ACantidad: ' . $facturaFlash->total_prendas
                            . '%0ATotal: $%20' . rawurlencode($valorTotal)
                            . '%0A%0A%C2%A1%C2%A1%C2%A1Muchas gracias por escoger nuestro servicio!!!'
                            . '%0ALavanderia Exclusiva'
                            . '%0Aa su servicio...';

                        $waUrl = 'https://wa.me/' . $numWa . '?text=' . $mensaje;
                    @endphp
                    <div class="mt-3">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.22em] text-emerald-600">Notificar al cliente por WhatsApp</p>
                        <a
                            href="{{ $waUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-full bg-[#25D366] px-5 py-2.5 text-sm font-bold shadow-md transition hover:-translate-y-0.5 hover:shadow-lg"
                            style="color: #ffffff;"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.524 3.66 1.438 5.168L2 22l4.979-1.418A9.953 9.953 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a7.946 7.946 0 0 1-4.274-1.244l-.306-.182-3.166.902.868-3.088-.2-.316A7.954 7.954 0 0 1 4 12c0-4.418 3.582-8 8-8s8 3.582 8 8-3.582 8-8 8z"/></svg>
                            Enviar WhatsApp al cliente
                        </a>
                    </div>
                @else
                    <p class="mt-2 text-xs text-emerald-600">El cliente no tiene celular registrado.</p>
                @endif
            @endif
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
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
                Crea al menos un cliente y espera a que administración cargue prendas activas del recolector para registrar órdenes de pedido.
            @elseif ($clientes->isEmpty())
                Todavía no tienes clientes asignados. Puedes crear uno desde esta misma pantalla.
            @else
                Todavía no hay prendas activas del recolector. Administración debe habilitar al menos una para poder registrar pedidos.
            @endif
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[250px_minmax(0,1fr)]">
        <aside class="xl:sticky xl:top-24 xl:self-start">
            <div class="rounded-[1.5rem] border border-amber-100 bg-white/90 p-3 shadow-xl shadow-amber-100">
                <nav class="grid gap-3">
                    <a href="#ingresar-orden" class="rounded-[1.35rem] border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-bold text-amber-800 shadow-sm hover:-translate-y-0.5 hover:bg-amber-100 transition-transform">📝 Ingresar orden</a>
                    <a href="#gastos-quincena" class="rounded-[1.35rem] border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-bold text-rose-700 shadow-sm hover:-translate-y-0.5 hover:bg-rose-100 transition-transform">💸 Gastos quincena</a>
                    <hr class="border-slate-200">
                    <button type="button" @click="modalEstatus = true" class="w-full text-left rounded-[1.35rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700 shadow-sm hover:-translate-y-0.5 hover:bg-emerald-100 transition-transform">
                        📋 Estatus de facturas
                        <span class="mt-1 block text-xs font-normal text-emerald-600">{{ $facturas->count() }} órdenes</span>
                    </button>
                    <button type="button" @click="modalOrdenes = true" class="w-full text-left rounded-[1.35rem] border border-slate-200 bg-white px-5 py-4 text-sm font-bold text-slate-700 shadow-sm hover:-translate-y-0.5 hover:bg-slate-50 transition-transform">
                        🗂 Órdenes recientes
                        <span class="mt-1 block text-xs font-normal text-slate-500">Historial completo</span>
                    </button>
                </nav>
            </div>
        </aside>

        <section class="min-w-0 space-y-8">
    <div id="ingresar-orden" class="grid min-w-0 gap-8 scroll-mt-24" :class="isTouchDevice ? '2xl:grid-cols-1' : '2xl:grid-cols-[minmax(360px,420px)_minmax(0,1fr)]'">
        <div class="min-w-0 space-y-6">

            {{-- ─── CREAR CLIENTE RÁPIDO ─────────────────────────────────── --}}
            <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Crear cliente rápido</h2>
                <p class="mt-1 text-sm text-slate-500">Si el cliente no existe, puedes registrarlo aquí mismo sin salir del módulo.</p>

                <form action="{{ route('recolector.clientes.store') }}" method="POST" class="mt-6 space-y-4">
                    @csrf

                    {{-- F4: Número de cliente (solo lectura, autoasignado) --}}
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500 uppercase tracking-[0.2em]">N° de cliente (automático)</label>
                        <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                            <span class="font-semibold text-amber-700">{{ \App\Models\Cliente::siguienteNumero() }}</span>
                            <span class="text-slate-400">(se asigna automáticamente)</span>
                        </div>
                    </div>

                    <input name="nombre" type="text" placeholder="Nombre del cliente *" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>

                    {{-- F5: Campo Barrio obligatorio --}}
                    <input name="barrio" type="text" placeholder="Barrio *" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>

                    <x-input-celular class="w-full" />
                    <input name="direccion" type="text" placeholder="Dirección" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">

                    <button type="submit" class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        Guardar cliente
                    </button>
                </form>
            </div>

            {{-- ─── DATOS DE LA ORDEN ────────────────────────────────────── --}}
            <div class="rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Datos de la orden de pedido</h2>

                <form action="{{ route('recolector.facturas.store') }}" method="POST" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="cliente_id" class="mb-2 block text-sm font-semibold text-slate-700">Cliente</label>
                        <select id="cliente_id" name="cliente_id" x-model="clienteId" @change="seleccionarCliente()" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required {{ $clientes->isEmpty() ? 'disabled' : '' }}>
                            <option value="">Selecciona un cliente</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}">{{ $cliente->nombre }} — Barrio: {{ $cliente->barrio ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2" :class="isTouchDevice ? 'md:grid-cols-1' : ''">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Fecha y hora</label>
                            <input type="text" x-model="fechaIngreso" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" readonly>
                        </div>
                        <div>
                            <label for="fecha_entrega" class="mb-2 block text-sm font-semibold text-slate-700">Día de entrega</label>
                            <input id="fecha_entrega" type="date" name="fecha_entrega" value="{{ old('fecha_entrega') }}" min="{{ now()->toDateString() }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                            <p class="mt-1 text-xs text-slate-500">Si no eliges fecha, se programa a 3 días contando hoy.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2" :class="isTouchDevice ? 'md:grid-cols-1' : ''">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Dirección</label>
                            <input type="text" :value="clienteActual.direccion || ''" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" readonly>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Barrio</label>
                            <input type="text" :value="clienteActual.barrio || ''" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" readonly>
                        </div>
                        {{-- F4: Número de cliente (visible, no editable) --}}
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">N° de cliente</label>
                            <div class="rounded-2xl border border-slate-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-700">
                                # <span x-text="clienteActual.numero_cliente || '—'"></span>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Celular</label>
                            <input type="text" :value="clienteActual.celular || ''" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600" readonly>
                        </div>
                    </div>

                    {{-- ─── PRENDAS DEL PEDIDO ──── --}}
                    <div class="rounded-3xl border border-slate-200 p-4 sm:p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-500">Prendas del pedido</h3>
                                <p class="mt-1 text-sm text-slate-500">Elige prenda, cantidad, color y valor.</p>
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

                                    {{-- F7: Grid con 4 columnas: Cantidad, Color, Valor, Subtotal --}}
                                    <div class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-4" :class="isTouchDevice ? 'md:grid-cols-1 lg:grid-cols-1' : ''">
                                        <div>
                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Cantidad</label>
                                            <input
                                                type="number"
                                                min="1"
                                                x-model.number="item.cantidad"
                                                @input="ajustarColores(item)"
                                                :name="'items[' + index + '][cantidad]'"
                                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                                required
                                            >
                                        </div>
                                        {{-- F7: Selector de colores --}}
                                        <div>
                                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Colores</label>
                                            <div class="space-y-2">
                                                <template x-for="colorIndex in indicesPorCantidad(item)" :key="colorIndex">
                                                    <div>
                                                        <label class="mb-1 block text-xs font-semibold text-slate-500" x-text="'Color prenda ' + (colorIndex + 1)"></label>
                                                        <select
                                                            x-model="item.colores[colorIndex]"
                                                            :name="'items[' + index + '][colores][]'"
                                                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                                            required
                                                        >
                                                            <option value="">Selecciona un color</option>
                                                            <template x-for="color in coloresDisponibles" :key="color">
                                                                <option :value="color" x-text="color"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                </template>
                                            </div>
                                            <p class="mt-2 text-xs text-slate-500">Se pide un color por cada prenda de la cantidad ingresada.</p>
                                            <p class="mt-1 text-xs font-semibold text-rose-600" x-show="!coloresCompletos(item)" x-cloak>
                                                Completa el color de cada prenda.
                                            </p>
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
                            Aún no has agregado prendas a la orden de pedido.
                        </div>
                    </div>

                    {{-- OBSERVACIONES --}}
                    <div class="rounded-3xl border border-slate-200 p-4 sm:p-5">
                        <h3 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-500">Observaciones adicionales</h3>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2" :class="isTouchDevice ? 'sm:grid-cols-1' : ''">
                            @foreach (['Faltan botones', 'Falta cinturón', 'Está manchado', 'Está descolorido', 'Está roto'] as $observacion)
                                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                    <input type="checkbox" name="observaciones[]" value="{{ $observacion }}" class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                    <span>{{ $observacion }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- RESUMEN ANTES DE GUARDAR --}}
                    <div class="rounded-[1.75rem] border border-amber-200 bg-amber-50 px-5 py-5">
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.26em] text-amber-700">Resumen antes de guardar</p>
                                <div class="mt-3 space-y-2 text-sm text-slate-700">
                                    <p><span class="font-semibold text-slate-900">Cliente:</span> <span x-text="clienteActual.nombre || 'Pendiente por seleccionar'"></span></p>
                                    <p><span class="font-semibold text-slate-900">N° cliente:</span> # <span x-text="clienteActual.numero_cliente || '—'"></span></p>
                                    <p><span class="font-semibold text-slate-900">Número de orden:</span> #<span x-text="formatInvoiceNumber(numeroFactura)">{{ str_pad((string) $siguienteNumeroFactura, 6, '0', STR_PAD_LEFT) }}</span></p>
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
                                                        <span x-show="item.color" class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600" x-text="item.color"></span>
                                                    </p>
                                                    <p class="font-semibold text-slate-900">$ <span x-text="formatMoney(item.subtotal)"></span></p>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!resumenPrendas.length">
                                        <p class="text-sm text-slate-500">Agrega prendas para ver aquí el resumen del pedido.</p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-slate-900 px-5 py-5 text-white">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Valor total de la orden</p>
                                <p class="mt-2 text-3xl font-black">$ <span x-text="formatMoney(totalFactura)">0</span></p>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Cantidad total</p>
                                <p class="mt-2 text-3xl font-black" x-text="totalPrendas">0</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <button
                            type="submit"
                            class="w-full rounded-2xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-700 disabled:cursor-not-allowed disabled:bg-amber-300"
                            :disabled="!puedeGuardarFactura"
                            {{ $clientes->isEmpty() || $prendas->isEmpty() ? 'disabled' : '' }}
                        >
                            Guardar orden de pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="min-w-0 space-y-8">
            {{-- TARJETAS DE ESTADÍSTICAS --}}
            <div class="grid min-w-0 gap-5" :class="isTouchDevice ? 'grid-cols-1' : '[grid-template-columns:repeat(auto-fit,minmax(12rem,1fr))]'">
                <div class="min-w-0 rounded-[1.75rem] bg-white p-5 shadow-xl ring-1 ring-slate-200 sm:p-6">
                    <p class="text-sm uppercase tracking-[0.25em] text-slate-400">Órdenes registradas</p>
                    <p class="mt-3 break-words text-4xl font-black text-slate-900">{{ $facturas->count() }}</p>
                </div>
                <div class="min-w-0 rounded-[1.75rem] bg-amber-500 p-5 text-white shadow-xl sm:p-6">
                    <p class="text-sm uppercase tracking-[0.25em] text-amber-50">Prendas registradas</p>
                    <p class="mt-3 break-words text-4xl font-black">{{ $facturas->sum('total_prendas') }}</p>
                </div>
                <div class="min-w-0 rounded-[1.75rem] bg-slate-900 p-5 text-white shadow-xl sm:p-6">
                    <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Valor acumulado</p>
                    <p class="mt-3 break-words text-3xl font-black sm:text-4xl">$ {{ number_format($facturas->sum('total'), 0, ',', '.') }}</p>
                </div>
                {{-- F2: Reporte pago = 30% --}}
                <div class="min-w-0 rounded-[1.75rem] bg-emerald-600 p-5 text-white shadow-xl sm:p-6">
                    <p class="text-sm uppercase tracking-[0.25em] text-emerald-100">Tu pago (30%)</p>
                    <p class="mt-3 break-words text-3xl font-black">$ {{ number_format($reportePagoQuincena, 0, ',', '.') }}</p>
                    <p class="mt-2 break-words text-xs text-emerald-100">{{ $periodoActual }}</p>
                </div>
            </div>

            {{-- GASTOS QUINCENA --}}
            <div id="gastos-quincena" class="scroll-mt-24 rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-slate-900">Gastos de quincena</h2>
                    <p class="mt-1 text-sm text-slate-500">Tu pago es el 30% del total de facturas de la quincena.</p>
                </div>
                <div class="grid min-w-0 gap-6 p-4 sm:p-6 2xl:grid-cols-[minmax(20rem,24rem)_minmax(0,1fr)]">
                    <div class="min-w-0">
                        <form action="{{ route('recolector.gastos.store') }}" method="POST" class="space-y-3">
                            @csrf
                            <input name="concepto" type="text" placeholder="Concepto del gasto" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                            <input name="monto" type="number" min="0.01" step="0.01" placeholder="Monto" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" required>
                            <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                                Registrar gasto
                            </button>
                        </form>
                    </div>
                    <div class="min-w-0 space-y-3">
                        <div class="grid min-w-0 gap-3 [grid-template-columns:repeat(auto-fit,minmax(10.5rem,1fr))]">
                            <div class="min-w-0 rounded-2xl bg-slate-50 px-4 py-4 ring-1 ring-slate-200">
                                <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Facturas quincena</p>
                                <p class="mt-2 break-words text-xl font-black text-slate-900">$ {{ number_format($totalFacturasQuincena, 0, ',', '.') }}</p>
                            </div>
                            <div class="min-w-0 rounded-2xl bg-rose-50 px-4 py-4 ring-1 ring-rose-200">
                                <p class="text-xs uppercase tracking-[0.22em] text-rose-600">Gastos quincena</p>
                                <p class="mt-2 break-words text-xl font-black text-rose-700">$ {{ number_format($gastosQuincena, 0, ',', '.') }}</p>
                            </div>
                            <div class="min-w-0 rounded-2xl bg-emerald-50 px-4 py-4 ring-1 ring-emerald-200">
                                <p class="text-xs uppercase tracking-[0.22em] text-emerald-600">Tu pago (30%)</p>
                                <p class="mt-2 break-words text-xl font-black text-emerald-700">$ {{ number_format($reportePagoQuincena, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-slate-200">
                            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">Últimos gastos registrados</div>
                            <div class="divide-y divide-slate-100">
                                @forelse ($gastosRecientes as $gasto)
                                    <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $gasto->concepto }}</p>
                                            <p class="text-slate-500">{{ optional($gasto->fecha)->format('d/m/Y') }}</p>
                                        </div>
                                        <p class="font-semibold text-rose-700">$ {{ number_format($gasto->monto, 0, ',', '.') }}</p>
                                    </div>
                                @empty
                                    <p class="px-4 py-4 text-sm text-slate-500">Aún no hay gastos en esta cuenta.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
        </section>
    </div>

    {{-- ─── MODAL PAGO ─────────────────────────────────────────────────────── --}}
    <div x-cloak x-show="paymentOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4">
        <div @click.outside="paymentOpen = false" class="w-full max-w-md rounded-[1.5rem] bg-white p-6 shadow-2xl">
            <h2 class="text-xl font-black text-slate-900">Método de Pago</h2>
            <p class="mt-1 text-sm text-slate-500">Orden <span x-text="selectedOrder"></span></p>
            <form :action="paymentAction" method="POST" class="mt-5 space-y-3">
                @csrf
                @method('PATCH')
                <input type="hidden" name="estado_factura" value="pagado">
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <input type="radio" name="metodo_pago" value="efectivo" class="h-4 w-4 text-emerald-600" required>
                    Efectivo
                </label>
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <input type="radio" name="metodo_pago" value="qr" class="h-4 w-4 text-emerald-600" required>
                    Qr
                </label>
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <input type="radio" name="metodo_pago" value="nequi" class="h-4 w-4 text-emerald-600" required>
                    Nequi
                </label>
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <input type="radio" name="metodo_pago" value="llave_breve" class="h-4 w-4 text-emerald-600" required>
                    LLave Breve
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="paymentOpen = false" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Aceptar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ─── F3: MODAL RESUMEN DE ORDEN ─────────────────────────────────────── --}}
    <div x-cloak x-show="orderSummaryOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4">
        <div @click.outside="orderSummaryOpen = false" class="w-full max-w-lg rounded-[1.75rem] bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-700">Resumen de Orden</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-900" x-text="selectedOrderSummary.numero_orden"></h2>
                </div>
                <button @click="orderSummaryOpen = false" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="mt-4 grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Cliente</p>
                    <p class="mt-1 font-bold text-slate-900" x-text="selectedOrderSummary.cliente_nombre"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Celular</p>
                    <p class="mt-1 font-bold text-slate-900" x-text="selectedOrderSummary.celular || 'Sin celular'"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Total prendas</p>
                    <p class="mt-1 font-bold text-slate-900" x-text="selectedOrderSummary.total_prendas"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Valor total</p>
                    <p class="mt-1 text-xl font-black text-emerald-700">$ <span x-text="selectedOrderSummary.total"></span></p>
                </div>
            </div>

            <div class="mt-4">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Detalle de prendas</p>
                <div class="mt-2 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                    <template x-for="(d, i) in selectedOrderSummary.detalles" :key="i">
                        <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                            <div>
                                <p class="font-semibold text-slate-900" x-text="d.prenda_nombre"></p>
                                <p class="text-slate-500">
                                    <span x-text="'x' + d.cantidad"></span>
                                    <span x-show="d.color_prenda" class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs" x-text="d.color_prenda"></span>
                                </p>
                            </div>
                            <p class="font-semibold text-slate-900">$ <span x-text="d.subtotal"></span></p>
                        </div>
                    </template>
                    <template x-if="!selectedOrderSummary.detalles || !selectedOrderSummary.detalles.length">
                        <p class="px-4 py-3 text-sm text-slate-400">Sin detalles disponibles.</p>
                    </template>
                </div>
            </div>

            <button @click="orderSummaryOpen = false" class="mt-5 w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-bold text-white hover:bg-slate-800">
                Cerrar
            </button>
        </div>
    </div>

    {{-- ════════ MODAL: ESTATUS DE FACTURAS ════════ --}}
    <div x-cloak x-show="modalEstatus" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/50 px-4 py-8">
        <div @click.outside="modalEstatus = false" class="relative w-full max-w-5xl rounded-[1.75rem] bg-white shadow-2xl my-auto">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-xl font-black text-slate-900">📋 Estatus de facturas</h2>
                    <p class="mt-1 text-sm text-slate-500">Haz clic en el número de orden para ver el resumen completo.</p>
                </div>
                <button @click="modalEstatus = false" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:bg-slate-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @php
                $estadoClasesModal = [
                    'pagado'    => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                    'pendiente' => 'bg-amber-100 text-amber-700 ring-amber-200',
                    'cancelado' => 'bg-rose-100 text-rose-700 ring-rose-200',
                ];
            @endphp
            <div class="grid gap-3 p-6 pb-4 md:grid-cols-3">
                @foreach (['pendiente' => 'Pendientes', 'pagado' => 'Pagadas', 'cancelado' => 'Canceladas'] as $estado => $label)
                    @php
                        $resumenEstado = $facturaStatusResumen->get($estado);
                    @endphp
                    <div class="rounded-2xl px-4 py-3 ring-1 {{ $estadoClasesModal[$estado] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                        <p class="text-xs font-bold uppercase tracking-[0.18em]">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-black">{{ $resumenEstado->cantidad ?? 0 }}</p>
                    </div>
                @endforeach
            </div>
            <div class="overflow-x-auto max-h-[75vh]">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Orden #</th>
                            <th class="px-6 py-4 font-semibold">Cliente</th>
                            <th class="px-6 py-4 font-semibold">Total prendas</th>
                            <th class="px-6 py-4 font-semibold">Valor total</th>
                            <th class="px-6 py-4 font-semibold">Estatus</th>
                            <th class="px-6 py-4 font-semibold">Cambiar estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($facturas as $factura)
                            @php
                                $estadoF = $factura->estado_factura ?? 'pendiente';
                                $ordenF  = '#'.str_pad((string) ($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT);
                                $actionF = route('recolector.facturas.estatus', $factura);
                                $facturaJsonM = json_encode([
                                    'numero_orden'   => $ordenF,
                                    'cliente_nombre' => $factura->cliente->nombre ?? 'Cliente eliminado',
                                    'celular'        => $factura->celular ?? '',
                                    'total'          => number_format((float)$factura->total, 0, ',', '.'),
                                    'total_prendas'  => $factura->total_prendas,
                                    'estado'         => $estadoF,
                                    'detalles'       => $factura->detalles->map(fn($d) => [
                                        'prenda_nombre' => $d->prenda_nombre,
                                        'cantidad'      => $d->cantidad,
                                        'color_prenda'  => $d->color_prenda ?? '',
                                        'subtotal'      => number_format((float)$d->subtotal, 0, ',', '.'),
                                    ])->values(),
                                ]);
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <button type="button" @click="openOrderSummary({{ $facturaJsonM }})" class="rounded-full bg-amber-100 px-3 py-1.5 text-sm font-bold text-amber-800 hover:bg-amber-200">
                                        {{ $ordenF }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $factura->cliente->nombre ?? 'Cliente eliminado' }}</td>
                                <td class="px-6 py-4 text-slate-700">{{ $factura->total_prendas }}</td>
                                <td class="px-6 py-4 font-semibold text-emerald-700">$ {{ number_format($factura->total, 0, ',', '.') }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] ring-1 {{ $estadoClasesModal[$estadoF] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                        {{ $estadoF }}
                                    </span>
                                    @if ($factura->metodo_pago)
                                        <p class="mt-1 text-xs text-slate-500">{{ str_replace('_', ' ', $factura->metodo_pago) }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="openPayment('{{ $actionF }}', '{{ $ordenF }}')" @disabled($estadoF !== 'pendiente') class="rounded-full bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500">
                                            Pagado
                                        </button>
                                        <form action="{{ $actionF }}" method="POST">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="estado_factura" value="pendiente">
                                            <button @disabled($estadoF !== 'pendiente') class="rounded-full border border-amber-200 px-3 py-2 text-xs font-bold text-amber-700 hover:bg-amber-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400">
                                                Pendiente
                                            </button>
                                        </form>
                                        <span class="rounded-full border border-rose-100 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-500">Cancelado admin</span>
                                        @if(auth()->user()->esAdmin())
                                            <a href="{{ route('admin.facturas-recolector.edit', $factura) }}" class="rounded-full border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-bold text-sky-700 hover:bg-sky-100">Editar</a>
                                            <form action="{{ route('admin.facturas-recolector.destroy', $factura) }}" method="POST" onsubmit="return confirm('¿Eliminar esta factura?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="rounded-full border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100">Eliminar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-slate-500">Todavia no has registrado ordenes de pedido.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ════════ MODAL: ÓRDENES RECIENTES ════════ --}}
    <div x-cloak x-show="modalOrdenes" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/50 px-4 py-8">
        <div @click.outside="modalOrdenes = false" class="relative w-full max-w-4xl rounded-[1.75rem] bg-white shadow-2xl my-auto">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-xl font-black text-slate-900">🗂 Órdenes recientes</h2>
                    <p class="mt-1 text-sm text-slate-500">Historial completo de órdenes de pedido registradas.</p>
                </div>
                <button @click="modalOrdenes = false" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:bg-slate-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="max-h-[75vh] overflow-y-auto p-6 space-y-4">
                @forelse ($facturas as $factura)
                    <div class="rounded-[1.5rem] border border-slate-200 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-700">
                                    Orden #{{ str_pad((string) ($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT) }}
                                </p>
                                <p class="text-lg font-bold text-slate-900">{{ $factura->cliente->nombre ?? 'Cliente eliminado' }}</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Ingreso {{ optional($factura->fecha_ingreso)->format('d/m/Y H:i') }} |
                                    Entrega {{ optional($factura->fecha_entrega)->format('d/m/Y') }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $factura->direccion ?: 'Sin dirección' }} |
                                    Cliente #{{ $factura->numero_cliente ?? 'N/A' }} |
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
                                        <th class="pb-3 font-semibold">Color</th>
                                        <th class="pb-3 font-semibold">Cantidad</th>
                                        <th class="pb-3 font-semibold">Valor unitario</th>
                                        <th class="pb-3 font-semibold">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach ($factura->detalles as $detalle)
                                        <tr>
                                            <td class="py-3 font-medium text-slate-900">{{ $detalle->prenda_nombre }}</td>
                                            <td class="py-3 text-slate-600">
                                                @if($detalle->color_prenda)
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold">{{ $detalle->color_prenda }}</span>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="py-3 text-slate-600">{{ $detalle->cantidad }}</td>
                                            <td class="py-3 text-slate-600">$ {{ number_format($detalle->valor_unitario, 0, ',', '.') }}</td>
                                            <td class="py-3 font-semibold text-slate-900">$ {{ number_format($detalle->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @php
                            $numLimpio     = preg_replace('/\D+/', '', (string) $factura->celular);
                            $numWa         = strlen($numLimpio) === 10 ? '57' . $numLimpio : $numLimpio;
                            $numeroOrdenWa = str_pad((string) ($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT);
                            $nombreClienteWa = $factura->cliente->nombre ?? 'Cliente';
                            $valorTotalWa  = number_format((float) $factura->total, 0, ',', '.');
                            $lineasPrendas = '';
                            foreach ($factura->detalles as $det) {
                                $colorStr = $det->color_prenda ? ' (' . $det->color_prenda . ')' : '';
                                $lineasPrendas .= '%0A-' . rawurlencode($det->prenda_nombre . $colorStr) . ' x ' . $det->cantidad;
                            }
                            $mensajeWa = 'Orden de pedido: ' . $numeroOrdenWa
                                . '%0ANombre Cliente: ' . rawurlencode($nombreClienteWa)
                                . '%0ATipo de Prenda: ' . $lineasPrendas
                                . '%0ACantidad: ' . $factura->total_prendas
                                . '%0ATotal: $%20' . rawurlencode($valorTotalWa)
                                . '%0A%0A%C2%A1%C2%A1%C2%A1Muchas gracias por escoger nuestro servicio!!!'
                                . '%0ALavanderia Exclusiva'
                                . '%0Aa su servicio...';
                            $waUrlModal = 'https://wa.me/' . $numWa . '?text=' . $mensajeWa;
                        @endphp

                        {{-- ─── BOTONES DE ACCIÓN RÁPIDA ──── --}}
                        <div class="mt-5 flex flex-wrap items-center gap-3">
                            @if ($factura->celular && $numLimpio)
                                <a href="{{ $waUrlModal }}" target="_blank" rel="noopener noreferrer"
                                   id="wa-btn-{{ $factura->id }}"
                                   class="inline-flex items-center gap-2 rounded-full bg-[#25D366] px-4 py-2.5 text-xs font-bold shadow transition hover:-translate-y-0.5 hover:shadow-md"
                                   style="color: #ffffff;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.524 3.66 1.438 5.168L2 22l4.979-1.418A9.953 9.953 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a7.946 7.946 0 0 1-4.274-1.244l-.306-.182-3.166.902.868-3.088-.2-.316A7.954 7.954 0 0 1 4 12c0-4.418 3.582-8 8-8s8 3.582 8 8-3.582 8-8 8z"/></svg>
                                    WhatsApp al cliente
                                </a>
                            @else
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-semibold text-slate-400">
                                    Sin celular registrado
                                </span>
                            @endif

                            {{-- Botón Imprimir Orden --}}
                            @php
                                $facturaParaImprimir = json_encode([
                                    'id'              => $factura->id,
                                    'numero_orden'    => str_pad((string)($factura->numero_orden ?? $factura->id), 6, '0', STR_PAD_LEFT),
                                    'cliente_nombre'  => $factura->cliente->nombre ?? 'Cliente eliminado',
                                    'celular'         => $factura->celular ?? '',
                                    'direccion'       => $factura->direccion ?? '',
                                    'numero_cliente'  => $factura->numero_cliente ?? '',
                                    'fecha_ingreso'   => optional($factura->fecha_ingreso)->format('d/m/Y H:i') ?? '',
                                    'fecha_entrega'   => optional($factura->fecha_entrega)->format('d/m/Y') ?? '',
                                    'total_prendas'   => $factura->total_prendas,
                                    'total'           => number_format((float)$factura->total, 0, ',', '.'),
                                    'observaciones'   => filled($factura->observaciones) ? implode(', ', $factura->observaciones) : '',
                                    'estado_factura'  => $factura->estado_factura ?? 'pendiente',
                                    'detalles'        => $factura->detalles->map(fn($d) => [
                                        'prenda_nombre'  => $d->prenda_nombre,
                                        'color_prenda'   => $d->color_prenda ?? '',
                                        'cantidad'       => $d->cantidad,
                                        'valor_unitario' => number_format((float)$d->valor_unitario, 0, ',', '.'),
                                        'subtotal'       => number_format((float)$d->subtotal, 0, ',', '.'),
                                    ])->values(),
                                ]);
                            @endphp
                            <button
                                type="button"
                                id="print-btn-{{ $factura->id }}"
                                @click="abrirImpresion({{ $facturaParaImprimir }})"
                                class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Imprimir orden
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Todavía no has registrado órdenes de pedido como recolector.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ════════ MODAL: IMPRESIÓN DE ORDEN ════════ --}}
    <div x-cloak x-show="printModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/60 px-4">
        <div @click.outside="printModalOpen = false" class="relative w-full max-w-2xl rounded-[1.75rem] bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 no-print">
                <p class="text-sm font-bold text-slate-700">Vista previa de impresión</p>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="ejecutarImpresion()"
                        class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Imprimir
                    </button>
                    <button @click="printModalOpen = false" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Contenido imprimible --}}
            <div id="print-content" class="max-h-[80vh] overflow-y-auto px-8 py-8 text-slate-900 no-print">
                {{-- Encabezado --}}
                <div class="flex items-center justify-between border-b-2 border-slate-900 pb-5">
                    <div class="flex items-center gap-4">
                        <svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg" class="h-14 w-14" fill="currentColor">
                            <path d="M305.8 81.125C305.77 80.995 305.69 80.885 305.65 80.755C305.56 80.525 305.49 80.285 305.37 80.075C305.29 79.935 305.17 79.815 305.07 79.685C304.94 79.515 304.83 79.325 304.68 79.175C304.55 79.045 304.39 78.955 304.25 78.845C304.09 78.715 303.95 78.575 303.77 78.475L251.32 48.275C249.97 47.495 248.31 47.495 246.96 48.275L194.51 78.475C194.33 78.575 194.19 78.725 194.03 78.845C193.89 78.955 193.73 79.045 193.6 79.175C193.45 79.325 193.34 79.515 193.21 79.685C193.11 79.815 192.99 79.935 192.91 80.075C192.79 80.285 192.71 80.525 192.63 80.755C192.58 80.875 192.51 80.995 192.48 81.125C192.38 81.495 192.33 81.875 192.33 82.265V139.625L148.62 164.795V52.575C148.62 52.185 148.57 51.805 148.47 51.435C148.44 51.305 148.36 51.195 148.32 51.065C148.23 50.835 148.16 50.595 148.04 50.385C147.96 50.245 147.84 50.125 147.74 49.995C147.61 49.825 147.5 49.635 147.35 49.485C147.22 49.355 147.06 49.265 146.92 49.155C146.76 49.025 146.62 48.885 146.44 48.785L93.99 18.585C92.64 17.805 90.98 17.805 89.63 18.585L37.18 48.785C37 48.885 36.86 49.035 36.7 49.155C36.56 49.265 36.4 49.355 36.27 49.485C36.12 49.635 36.01 49.825 35.88 49.995C35.78 50.125 35.66 50.245 35.58 50.385C35.46 50.595 35.38 50.835 35.3 51.065C35.25 51.185 35.18 51.305 35.15 51.435C35.05 51.805 35 52.185 35 52.575V232.235C35 233.795 35.84 235.245 37.19 236.025L142.1 296.425C142.33 296.555 142.58 296.635 142.82 296.725C142.93 296.765 143.04 296.835 143.16 296.865C143.53 296.965 143.9 297.015 144.28 297.015C144.66 297.015 145.03 296.965 145.4 296.865C145.5 296.835 145.59 296.775 145.69 296.745C145.95 296.655 146.21 296.565 146.45 296.435L251.36 236.035C252.72 235.255 253.55 233.815 253.55 232.245V174.885L303.81 145.945C305.17 145.165 306 143.725 306 142.155V82.265C305.95 81.875 305.89 81.495 305.8 81.125ZM144.2 227.205L100.57 202.515L146.39 176.135L196.66 147.195L240.33 172.335L208.29 190.625L144.2 227.205ZM244.75 114.995V164.795L226.39 154.225L201.03 139.625V89.825L219.39 100.395L244.75 114.995ZM249.12 57.105L292.81 82.265L249.12 107.425L205.43 82.265L249.12 57.105ZM114.49 184.425L96.13 194.995V85.305L121.49 70.705L139.85 60.135V169.815L114.49 184.425ZM91.76 27.425L135.45 52.585L91.76 77.745L48.07 52.585L91.76 27.425ZM43.67 60.135L62.03 70.705L87.39 85.305V202.545V202.555V202.565C87.39 202.735 87.44 202.895 87.46 203.055C87.49 203.265 87.49 203.485 87.55 203.695V203.705C87.6 203.875 87.69 204.035 87.76 204.195C87.84 204.375 87.89 204.575 87.99 204.745C87.99 204.745 87.99 204.755 88 204.755C88.09 204.905 88.22 205.035 88.33 205.175C88.45 205.335 88.55 205.495 88.69 205.635L88.7 205.645C88.82 205.765 88.98 205.855 89.12 205.965C89.28 206.085 89.42 206.225 89.59 206.325C89.6 206.325 89.6 206.325 89.61 206.335C89.62 206.335 89.62 206.345 89.63 206.345L139.87 234.775V285.065L43.67 229.705V60.135ZM244.75 229.705L148.58 285.075V234.775L219.8 194.115L244.75 179.875V229.705ZM297.2 139.625L253.49 164.795V114.995L278.85 100.395L297.21 89.825V139.625H297.2Z"/>
                        </svg>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-slate-500">Lavandería Exclusiva</p>
                            <p class="text-xl font-black text-slate-900">Orden de Pedido</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500">N° de Orden</p>
                        <p class="text-2xl font-black text-amber-700" x-text="'#' + (printData.numero_orden || '')"></p>
                    </div>
                </div>

                {{-- Datos generales --}}
                <div class="mt-5 grid grid-cols-2 gap-4 rounded-2xl bg-slate-50 p-5 text-sm">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Cliente</p>
                        <p class="mt-1 font-semibold text-slate-900" x-text="printData.cliente_nombre"></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Celular</p>
                        <p class="mt-1 font-semibold text-slate-900" x-text="printData.celular || 'Sin celular'"></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Fecha de recibido</p>
                        <p class="mt-1 font-semibold text-slate-900" x-text="printData.fecha_ingreso || '—'"></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Fecha de entrega</p>
                        <p class="mt-1 font-semibold text-slate-900" x-text="printData.fecha_entrega || '—'"></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Dirección</p>
                        <p class="mt-1 font-semibold text-slate-900" x-text="printData.direccion || 'Sin dirección'"></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">N° Cliente</p>
                        <p class="mt-1 font-semibold text-slate-900" x-text="'#' + (printData.numero_cliente || '—')"></p>
                    </div>
                </div>

                {{-- Tabla de prendas --}}
                <div class="mt-5">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Detalle de prendas</p>
                    <table class="mt-2 w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-slate-200 text-left">
                                <th class="pb-2 pr-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Prenda</th>
                                <th class="pb-2 pr-3 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Color</th>
                                <th class="pb-2 pr-3 text-center text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Cant.</th>
                                <th class="pb-2 pr-3 text-right text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Valor Unit.</th>
                                <th class="pb-2 text-right text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(d, i) in (printData.detalles || [])" :key="i">
                                <tr>
                                    <td class="py-2 pr-3 font-medium text-slate-900" x-text="d.prenda_nombre"></td>
                                    <td class="py-2 pr-3 text-slate-600" x-text="d.color_prenda || '—'"></td>
                                    <td class="py-2 pr-3 text-center text-slate-700" x-text="d.cantidad"></td>
                                    <td class="py-2 pr-3 text-right text-slate-700" x-text="'$\u00a0' + d.valor_unitario"></td>
                                    <td class="py-2 text-right font-semibold text-slate-900" x-text="'$\u00a0' + d.subtotal"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Observaciones --}}
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-amber-700">Observaciones del estado de la prenda</p>
                    <p class="mt-2 text-sm text-slate-700" x-text="printData.observaciones || 'Sin observaciones adicionales registradas.'"></p>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-600">
                        <template x-if="printData.observaciones">
                            <span></span>
                        </template>
                        <template x-if="!printData.observaciones">
                            <div class="col-span-2 space-y-1.5 text-slate-500">
                                <p>☐ Manchado &nbsp; ☐ Roto &nbsp; ☐ Dañado &nbsp; ☐ Le falta algo</p>
                                <p class="mt-2 text-slate-400">Observaciones adicionales: ___________________________________</p>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Total --}}
                <div class="mt-5 flex items-center justify-between rounded-2xl bg-slate-900 px-6 py-5 text-white">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Total general a pagar</p>
                        <p class="mt-1 text-3xl font-black">$ <span x-text="printData.total || '0'"></span></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Total prendas</p>
                        <p class="mt-1 text-3xl font-black" x-text="printData.total_prendas || '0'"></p>
                    </div>
                </div>

                {{-- Pie de página --}}
                <div class="mt-6 border-t border-slate-200 pt-4 text-center">
                    <p class="text-xs text-slate-400">¡¡¡Muchas gracias por escoger nuestro servicio!!!</p>
                    <p class="text-sm font-bold text-slate-700">Lavandería Exclusiva — a su servicio...</p>
                </div>
            </div>

            {{-- Versión para impresión real (sólo visible al imprimir) --}}
            <div id="print-real" class="print-only hidden px-8 py-8 text-slate-900">
                <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:2px solid #0f172a;padding-bottom:20px;margin-bottom:20px">
                    <div style="display:flex;align-items:center;gap:16px">
                        <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:0.3em;color:#64748b">Lavandería Exclusiva</div>
                        <div style="font-size:20px;font-weight:900;color:#0f172a">Orden de Pedido</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:11px;color:#94a3b8">N° de Orden</div>
                        <div id="pr-numero" style="font-size:22px;font-weight:900;color:#b45309"></div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;background:#f8fafc;border-radius:12px;padding:20px;margin-bottom:20px;font-size:13px">
                    <div><div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px">Cliente</div><div id="pr-cliente" style="font-weight:600"></div></div>
                    <div><div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px">Celular</div><div id="pr-celular" style="font-weight:600"></div></div>
                    <div><div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px">Fecha de recibido</div><div id="pr-fechaing" style="font-weight:600"></div></div>
                    <div><div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px">Fecha de entrega</div><div id="pr-fechaent" style="font-weight:600"></div></div>
                    <div><div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px">Dirección</div><div id="pr-direccion" style="font-weight:600"></div></div>
                    <div><div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:4px">N° Cliente</div><div id="pr-nocliente" style="font-weight:600"></div></div>
                </div>
                <div style="margin-bottom:20px">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:8px;letter-spacing:0.22em">Detalle de prendas</div>
                    <table id="pr-tabla" style="width:100%;border-collapse:collapse;font-size:13px">
                        <thead><tr style="border-bottom:2px solid #e2e8f0">
                            <th style="text-align:left;padding:6px 8px 6px 0;font-size:10px;text-transform:uppercase;color:#94a3b8">Prenda</th>
                            <th style="text-align:left;padding:6px 8px;font-size:10px;text-transform:uppercase;color:#94a3b8">Color</th>
                            <th style="text-align:center;padding:6px 8px;font-size:10px;text-transform:uppercase;color:#94a3b8">Cant.</th>
                            <th style="text-align:right;padding:6px 8px;font-size:10px;text-transform:uppercase;color:#94a3b8">Valor Unit.</th>
                            <th style="text-align:right;padding:6px 0 6px 8px;font-size:10px;text-transform:uppercase;color:#94a3b8">Subtotal</th>
                        </tr></thead>
                        <tbody id="pr-filas"></tbody>
                    </table>
                </div>
                <div id="pr-obs-section" style="background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;padding:16px;margin-bottom:20px">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#b45309;margin-bottom:8px;letter-spacing:0.22em">Observaciones del estado de la prenda</div>
                    <div id="pr-obs" style="font-size:13px;color:#334155"></div>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;background:#0f172a;color:#fff;border-radius:12px;padding:20px 24px">
                    <div>
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.25em;color:#94a3b8">Total general a pagar</div>
                        <div style="font-size:28px;font-weight:900;margin-top:4px">$ <span id="pr-total"></span></div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.25em;color:#94a3b8">Total prendas</div>
                        <div id="pr-totalprendas" style="font-size:28px;font-weight:900;margin-top:4px"></div>
                    </div>
                </div>
                <div style="text-align:center;margin-top:24px;padding-top:16px;border-top:1px solid #e2e8f0">
                    <div style="font-size:11px;color:#94a3b8">¡¡¡Muchas gracias por escoger nuestro servicio!!!</div>
                    <div style="font-size:14px;font-weight:700;color:#334155;margin-top:4px">Lavandería Exclusiva — a su servicio...</div>
                </div>
            </div>
        </div>
    </div>

</div>{{-- FIN del div raíz x-data --}}

<script>
function recolectorForm({ clientes, prendas, fechaIngreso, clienteInicial, oldItems, puedeEditarPrecios, numeroFactura, facturas }) {
    // modalEstatus y modalOrdenes se agregan vía x-data en la plantilla blade
    return {
        clientes,
        prendas,
        facturas,
        fechaIngreso,
        puedeEditarPrecios,
        numeroFactura,
        clienteId: clienteInicial ? String(clienteInicial) : '',
        clienteActual: {},
        selectedPrendaId: '',
        items: [],
        nextItemKey: 0,
        coloresDisponibles: ['Blanco', 'Negro', 'Azul', 'Rojo', 'Verde', 'Amarillo', 'Gris', 'Rosa', 'Cafe', 'Morado', 'Naranja', 'Beige', 'Violeta', 'Multicolor', 'Otro'],
        paymentOpen: false,
        selectedOrder: '',
        paymentAction: '',
        orderSummaryOpen: false,
        selectedOrderSummary: {},
        modalEstatus: false,
        modalOrdenes: false,
        printModalOpen: false,
        printData: {},
        isTouchDevice: false,
        isMobileViewport: false,

        init() {
            this.actualizarDispositivo();
            window.addEventListener('resize', () => this.actualizarDispositivo());

            oldItems.forEach((item) => {
                const prendaId = Number(item.prenda_id || 0);
                const seleccionada = ['1', 1, true, 'true', 'on'].includes(item.selected) || Number(item.cantidad || 0) > 0;
                if (!prendaId || !seleccionada) return;
                this.agregarPrenda(prendaId, {
                    cantidad: Number(item.cantidad || 1),
                    precio_unitario: item.precio_unitario !== undefined && item.precio_unitario !== null && item.precio_unitario !== ''
                        ? Number(item.precio_unitario || 0)
                        : undefined,
                    colores: this.normalizarColores(item.colores || item.color_prenda || []),
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

        openPayment(action, order) {
            this.paymentAction = action;
            this.selectedOrder = order;
            this.paymentOpen = true;
        },

        // F3: Abrir modal resumen de orden
        openOrderSummary(facturaData) {
            this.selectedOrderSummary = facturaData;
            this.orderSummaryOpen = true;
        },

        // Abrir modal de impresión con datos de la factura
        abrirImpresion(facturaData) {
            this.printData = facturaData;
            this.printModalOpen = true;
        },

        // Ejecutar impresión real usando el div oculto
        ejecutarImpresion() {
            const d = this.printData;
            // Rellenar campos del template de impresión real
            const s = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
            s('pr-numero',      '#' + (d.numero_orden || ''));
            s('pr-cliente',     d.cliente_nombre || '');
            s('pr-celular',     d.celular || 'Sin celular');
            s('pr-fechaing',    d.fecha_ingreso || '—');
            s('pr-fechaent',    d.fecha_entrega || '—');
            s('pr-direccion',   d.direccion || 'Sin dirección');
            s('pr-nocliente',   '#' + (d.numero_cliente || '—'));
            s('pr-total',       d.total || '0');
            s('pr-totalprendas',String(d.total_prendas || '0'));
            s('pr-obs',         d.observaciones || 'Sin observaciones adicionales registradas.');

            // Construir filas de tabla
            const tbody = document.getElementById('pr-filas');
            if (tbody) {
                tbody.innerHTML = '';
                (d.detalles || []).forEach(det => {
                    tbody.insertAdjacentHTML('beforeend',
                        `<tr style="border-bottom:1px solid #f1f5f9">
                            <td style="padding:7px 8px 7px 0;font-weight:500">${det.prenda_nombre}</td>
                            <td style="padding:7px 8px;color:#64748b">${det.color_prenda || '—'}</td>
                            <td style="padding:7px 8px;text-align:center;color:#475569">${det.cantidad}</td>
                            <td style="padding:7px 8px;text-align:right;color:#475569">$ ${det.valor_unitario}</td>
                            <td style="padding:7px 0 7px 8px;text-align:right;font-weight:600">$ ${det.subtotal}</td>
                        </tr>`
                    );
                });
            }

            // Mostrar el template real, imprimir y ocultar
            const printEl = document.getElementById('print-real');
            const printContent = document.getElementById('print-content');
            if (printEl && printContent) {
                printEl.style.display = 'block';
                printContent.style.display = 'none';
                setTimeout(() => {
                    window.print();
                    printEl.style.display = 'none';
                    printContent.style.display = 'block';
                }, 150);
            } else {
                window.print();
            }
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
            if (!id || this.items.some((item) => Number(item.prenda_id) === id)) return;

            const prenda = this.datosPrenda(id);
            if (!prenda) return;
            const cantidad = Math.max(1, Number(valores.cantidad || 1));
            const colores = this.normalizarColores(valores.colores || valores.color_prenda || []);
            while (colores.length < cantidad) colores.push('');

            this.items.push({
                key: this.nextItemKey++,
                prenda_id: id,
                cantidad,
                precio_unitario: valores.precio_unitario !== undefined
                    ? Math.max(0, Number(valores.precio_unitario || 0))
                    : Number(prenda.precio || 0),
                colores: colores.slice(0, cantidad),
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
        ajustarColores(item) {
            const cantidad = Math.max(1, Number(item.cantidad || 1));
            item.cantidad = cantidad;
            item.colores = Array.isArray(item.colores) ? item.colores : [];
            while (item.colores.length < cantidad) item.colores.push('');
            if (item.colores.length > cantidad) item.colores = item.colores.slice(0, cantidad);
        },
        indicesPorCantidad(item) {
            this.ajustarColores(item);
            return Array.from({ length: Math.max(1, Number(item.cantidad || 1)) }, (_, index) => index);
        },
        coloresCompletos(item) {
            const cantidad = Math.max(1, Number(item.cantidad || 1));
            return Array.isArray(item.colores)
                && item.colores.length >= cantidad
                && item.colores.slice(0, cantidad).every((color) => this.coloresDisponibles.includes(String(color || '').trim()));
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
                color: item.colores.filter(Boolean).join(', '),
            }));
        },
        get puedeGuardarFactura() {
            return Boolean(this.clienteId) && this.items.length > 0 && this.items.every((item) => this.coloresCompletos(item));
        },
        get deviceLabel() {
            if (this.isTouchDevice && this.isMobileViewport) return 'Celular o pantalla táctil';
            if (this.isTouchDevice) return 'Pantalla táctil';
            if (this.isMobileViewport) return 'Pantalla pequeña';
            return 'Escritorio';
        },
        get deviceMessage() {
            if (this.isTouchDevice && this.isMobileViewport) return 'La interfaz se organiza en una sola columna y con botones más amplios para trabajar mejor desde celular.';
            if (this.isTouchDevice) return 'Se ampliaron controles y espacios para facilitar el uso en pantallas táctiles.';
            if (this.isMobileViewport) return 'La vista se compacta para pantallas pequeñas manteniendo todos los datos visibles.';
            return 'Controles optimizados para mouse y teclado, sin perder respuesta en ventanas reducidas.';
        },
        formatMoney(value) {
            return Number(value || 0).toLocaleString('es-CO');
        },
        formatInvoiceNumber(value) {
            return String(value || 1).padStart(6, '0');
        },
        normalizarColores(value) {
            const valores = Array.isArray(value) ? value : String(value || '').split(',');
            return valores.map((color) => String(color).trim()).filter((color) => this.coloresDisponibles.includes(color));
        },
    };
}
</script>

<style>
@media print {
    /* Ocultar todo excepto el template de impresión */
    body > * { display: none !important; }
    #print-real { display: block !important; }
    .no-print { display: none !important; }
    nav, header, aside, footer { display: none !important; }
    body { background: #fff !important; margin: 0; padding: 0; }
    #print-real {
        position: fixed;
        top: 0; left: 0;
        width: 100%;
        padding: 24px 32px;
        font-family: system-ui, sans-serif;
        color: #0f172a;
    }
}
</style>
@endsection
