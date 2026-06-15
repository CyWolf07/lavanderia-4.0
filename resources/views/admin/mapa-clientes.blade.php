@extends('layouts.app')

@section('title', 'Mapa de Clientes — Lavandería Exclusiva')

@section('content')

{{-- Leaflet CSS — usar cdnjs para máxima compatibilidad y evitar bloqueos --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" integrity="sha512-h9O8S1VKhH21XjBpewW4bHwXn3B32G5eZWeN1A1iVjsG7t9v1FzT8z8t1m+eO5OaL8Y0XkUjZ8y3mHq6z9A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
    #mapa-leaflet { z-index: 0; }
    .leaflet-container { font-family: inherit; border-radius: 1.5rem; }
    .mapa-wrapper { background: linear-gradient(135deg, #0f172a 0%, #1e1035 50%, #0f172a 100%); min-height: 100vh; }
</style>

<div class="mapa-wrapper">
<div class="mx-auto max-w-screen-2xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

    {{-- ENCABEZADO --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-violet-400">Panel Admin</p>
            <h1 class="mt-2 text-3xl font-black text-white">Mapa de Clientes</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-300">
                Visualiza la ubicación de cada cliente en Pasto por barrio y zona postal. Usa los filtros para segmentar.
            </p>
        </div>
        <a href="{{ route('admin.dashboard') }}"
           class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-white/20">
            ← Volver al dashboard
        </a>
    </div>

    {{-- ESTADÍSTICAS RÁPIDAS --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-[1.5rem] bg-white/10 p-5 ring-1 ring-white/20 backdrop-blur">
            <p class="text-xs uppercase tracking-[0.25em] text-slate-300">Total clientes</p>
            <p class="mt-2 text-3xl font-black text-white">{{ count($clientes) }}</p>
        </div>
        <div class="rounded-[1.5rem] bg-violet-500/30 p-5 ring-1 ring-violet-400/40 backdrop-blur">
            <p class="text-xs uppercase tracking-[0.25em] text-violet-200">Con ubicación</p>
            <p class="mt-2 text-3xl font-black text-white">{{ $clientes->filter(fn($c) => $c['latitud'] !== null)->count() }}</p>
        </div>
        <div class="rounded-[1.5rem] bg-amber-500/20 p-5 ring-1 ring-amber-400/30 backdrop-blur">
            <p class="text-xs uppercase tracking-[0.25em] text-amber-200">Sin ubicación</p>
            <p class="mt-2 text-3xl font-black text-white">{{ $clientes->filter(fn($c) => $c['latitud'] === null)->count() }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[300px_minmax(0,1fr)]" x-data="mapaClientes()">

        {{-- PANEL LATERAL --}}
        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">

            {{-- FILTROS --}}
            <div class="rounded-[1.5rem] bg-white/10 p-5 ring-1 ring-white/20 backdrop-blur">
                <h2 class="text-sm font-bold uppercase tracking-[0.22em] text-white">Filtrar clientes</h2>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-300">Nombre</label>
                        <input type="text" x-model="filtroNombre" @input="aplicarFiltros()"
                               placeholder="Nombre del cliente..."
                               class="w-full rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-400">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-300">Zona / Código postal</label>
                        <select x-model="filtroZona" @change="aplicarFiltros()"
                                class="w-full rounded-2xl border border-white/20 bg-slate-800 px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-violet-400">
                            <option value="">Todas las zonas</option>
                            @foreach ($zonas as $codigo => $nombre)
                                <option value="{{ $codigo }}">{{ $codigo }} — {{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-300">Barrio</label>
                        <input type="text" x-model="filtroBarrio" @input="aplicarFiltros()"
                               placeholder="Nombre del barrio..."
                               class="w-full rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-400">
                    </div>
                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" x-model="soloConUbicacion" @change="aplicarFiltros()"
                               class="h-4 w-4 rounded text-violet-500">
                        <span class="text-sm text-slate-200">Solo con coordenadas</span>
                    </label>
                    <button @click="resetFiltros()"
                            class="w-full rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/20">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            {{-- LISTA DE CLIENTES --}}
            <div class="max-h-[45vh] overflow-y-auto rounded-[1.5rem] bg-white/10 ring-1 ring-white/20 backdrop-blur">
                <div class="border-b border-white/10 px-5 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-300">
                        Visibles: <span class="text-violet-300" x-text="clientesFiltrados.length"></span>
                    </p>
                </div>
                <div class="divide-y divide-white/5">
                    <template x-for="c in clientesFiltrados" :key="c.id">
                        <button @click="centrarEnCliente(c)"
                                class="w-full px-5 py-3 text-left transition hover:bg-white/10"
                                :class="clienteSeleccionado?.id === c.id ? 'bg-violet-500/20' : ''">
                            <p class="text-sm font-semibold text-white" x-text="c.nombre"></p>
                            <p class="text-xs text-slate-400" x-text="(c.barrio || 'Sin barrio') + ' · ' + (c.codigo_postal || 'Sin CP')"></p>
                            <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-bold"
                                  :class="c.latitud ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300'"
                                  x-text="c.latitud ? 'Con ubicación' : 'Sin ubicación'"></span>
                        </button>
                    </template>
                    <template x-if="clientesFiltrados.length === 0">
                        <p class="px-5 py-4 text-sm text-slate-400">Sin resultados.</p>
                    </template>
                </div>
            </div>
        </aside>

        {{-- MAPA + INFO --}}
        <div class="space-y-4">

            {{-- MAPA LEAFLET --}}
            <div id="mapa-leaflet" style="height: 65vh; min-height: 450px; width: 100%; border-radius: 1.5rem; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.4);"></div>

            {{-- INFO CLIENTE SELECCIONADO --}}
            <div x-show="clienteSeleccionado" x-cloak
                 class="rounded-[1.5rem] bg-white/10 p-5 ring-1 ring-violet-400/40 backdrop-blur">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-violet-300">Cliente seleccionado</p>
                        <p class="mt-1 text-xl font-black text-white" x-text="clienteSeleccionado?.nombre"></p>
                        <div class="mt-2 grid gap-1 text-sm text-slate-200 sm:grid-cols-2">
                            <p><span class="font-semibold text-white">Cliente #:</span> <span x-text="clienteSeleccionado?.numero_cliente"></span></p>
                            <p><span class="font-semibold text-white">Celular:</span> <span x-text="clienteSeleccionado?.celular || 'N/A'"></span></p>
                            <p><span class="font-semibold text-white">Barrio:</span> <span x-text="clienteSeleccionado?.barrio || 'Sin barrio'"></span></p>
                            <p><span class="font-semibold text-white">Dirección:</span> <span x-text="clienteSeleccionado?.direccion || 'Sin dirección'"></span></p>
                            <p><span class="font-semibold text-white">C.P.:</span> <span x-text="clienteSeleccionado?.codigo_postal || '—'"></span></p>
                            <p><span class="font-semibold text-white">Recolector:</span> <span x-text="clienteSeleccionado?.recolector"></span></p>
                        </div>
                    </div>
                    <button @click="clienteSeleccionado = null"
                            class="rounded-full border border-white/20 p-2 text-white/60 hover:bg-white/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div x-show="!clienteSeleccionado?.latitud"
                     class="mt-3 rounded-2xl border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-sm text-amber-200">
                    ⚠️ Sin coordenadas. Haz clic en el mapa para asignarlas o usa geocodificación automática.
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button @click="geocodificarCliente(clienteSeleccionado)"
                            class="rounded-full bg-violet-600 px-4 py-2 text-xs font-bold text-white hover:bg-violet-500">
                        🔍 Auto-geocodificar
                    </button>
                    <span x-show="geocodificandoId === clienteSeleccionado?.id"
                          class="rounded-full bg-white/10 px-4 py-2 text-xs text-slate-300">Buscando...</span>
                </div>
            </div>

            {{-- LEYENDA --}}
            <div class="rounded-[1.5rem] bg-white/10 p-4 ring-1 ring-white/20 backdrop-blur">
                <div class="flex flex-wrap gap-5 text-xs text-slate-200">
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-violet-500"></span>
                        <span>Con coordenadas exactas</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                        <span>Sin coordenadas (barrio)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-slate-400"></span>
                        <span>Sin información</span>
                    </div>
                    <p class="text-slate-400">💡 Clic en el mapa para asignar ubicación al cliente seleccionado</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

{{-- Leaflet JS — cdnjs --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" integrity="sha512-BwHqxNdZV0q+8oIoxZ48m0yVbE3oGjH12S2b1U1bXz4c2+kE3R8T9w7f8h5q1g5i6z1Q4p2c3g5p6r7l5e5g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
const CLIENTES_DATA  = @json($clientes);
const ZONAS_PASTO    = @json($zonas);
const CSRF_TOKEN     = '{{ csrf_token() }}';
const URL_MAP_BASE   = '{{ url('/admin/mapa-clientes') }}';
const PASTO_CENTER   = [1.2136, -77.2811];

function mapaClientes() {
    return {
        todosClientes:      CLIENTES_DATA,
        clientesFiltrados:  CLIENTES_DATA,
        clienteSeleccionado: null,
        filtroNombre:   '',
        filtroZona:     '',
        filtroBarrio:   '',
        soloConUbicacion: false,
        geocodificandoId: null,
        mapa:           null,
        marcadores:     {},
        capaMarcadores: null,

        init() {
            this.$nextTick(() => this.inicializarMapa());
        },

        inicializarMapa() {
            this.mapa = L.map('mapa-leaflet', { zoomControl: true }).setView(PASTO_CENTER, 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.mapa);

            this.capaMarcadores = L.layerGroup().addTo(this.mapa);
            this.renderizarMarcadores(this.todosClientes);

            // Clic en mapa → asignar coordenadas al cliente seleccionado
            this.mapa.on('click', (e) => {
                if (this.clienteSeleccionado) {
                    this.asignarCoordenadas(this.clienteSeleccionado, e.latlng.lat, e.latlng.lng);
                }
            });
        },

        renderizarMarcadores(clientes) {
            this.capaMarcadores.clearLayers();
            this.marcadores = {};

            clientes.forEach(c => {
                if (!c.latitud || !c.longitud) return;

                const marker = L.circleMarker([c.latitud, c.longitud], {
                    radius:      9,
                    fillColor:   '#8b5cf6',
                    color:       '#ffffff',
                    weight:      2,
                    opacity:     1,
                    fillOpacity: 0.9,
                });

                marker.bindPopup(`
                    <div style="min-width:200px;font-family:inherit">
                        <p style="font-weight:700;font-size:14px;margin:0 0 6px;color:#1e293b">${c.nombre}</p>
                        <p style="font-size:12px;color:#6366f1;margin:0 0 4px">Cliente #${c.numero_cliente}</p>
                        <hr style="border:none;border-top:1px solid #e2e8f0;margin:4px 0">
                        <p style="font-size:12px;margin:2px 0;color:#334155"><b>Barrio:</b> ${c.barrio || 'N/A'}</p>
                        <p style="font-size:12px;margin:2px 0;color:#334155"><b>Dirección:</b> ${c.direccion || 'N/A'}</p>
                        <p style="font-size:12px;margin:2px 0;color:#334155"><b>Celular:</b> ${c.celular || 'N/A'}</p>
                        <p style="font-size:12px;margin:2px 0;color:#334155"><b>C.P.:</b> ${c.codigo_postal || 'N/A'}</p>
                        <p style="font-size:12px;margin:4px 0 0;color:#7c3aed"><b>Recolector:</b> ${c.recolector}</p>
                    </div>
                `);

                marker.on('click', () => { this.clienteSeleccionado = c; });
                marker.addTo(this.capaMarcadores);
                this.marcadores[c.id] = marker;
            });
        },

        aplicarFiltros() {
            this.clientesFiltrados = this.todosClientes.filter(c => {
                const n  = !this.filtroNombre  || c.nombre.toLowerCase().includes(this.filtroNombre.toLowerCase());
                const z  = !this.filtroZona    || c.codigo_postal === this.filtroZona;
                const b  = !this.filtroBarrio  || (c.barrio || '').toLowerCase().includes(this.filtroBarrio.toLowerCase());
                const u  = !this.soloConUbicacion || c.latitud !== null;
                return n && z && b && u;
            });
            this.renderizarMarcadores(this.clientesFiltrados);
        },

        resetFiltros() {
            this.filtroNombre = '';
            this.filtroZona   = '';
            this.filtroBarrio = '';
            this.soloConUbicacion = false;
            this.clientesFiltrados = this.todosClientes;
            this.renderizarMarcadores(this.todosClientes);
        },

        centrarEnCliente(cliente) {
            this.clienteSeleccionado = cliente;
            if (cliente.latitud && cliente.longitud) {
                this.mapa.flyTo([cliente.latitud, cliente.longitud], 17, { duration: 1 });
                if (this.marcadores[cliente.id]) this.marcadores[cliente.id].openPopup();
            } else {
                this.mapa.flyTo(PASTO_CENTER, 14, { duration: 0.8 });
            }
        },

        async geocodificarCliente(cliente) {
            if (!cliente) return;
            this.geocodificandoId = cliente.id;

            const query = [cliente.direccion, cliente.barrio, 'Pasto', 'Nariño', 'Colombia']
                .filter(Boolean).join(', ');

            try {
                // Llamar al proxy del servidor (evita bloqueos CORS/User-Agent de Nominatim)
                const resp = await fetch(
                    `/admin/mapa-clientes/geocodificar?q=${encodeURIComponent(query)}`,
                    { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
                );

                if (!resp.ok) {
                    alert('Error del servidor al geocodificar. Intenta de nuevo.');
                    return;
                }

                const data = await resp.json();

                if (data.error) {
                    alert('Error: ' + data.error);
                    return;
                }

                if (!data.found) {
                    alert('No se encontró "' + query + '" en el mapa.\nIntenta hacer clic directamente sobre el mapa para asignar la ubicación.');
                    return;
                }

                await this.asignarCoordenadas(cliente, data.lat, data.lon);

            } catch (e) {
                alert('Error de red al geocodificar. Verifica tu conexión.');
            } finally {
                this.geocodificandoId = null;
            }
        },

        async asignarCoordenadas(cliente, lat, lon) {
            const codigoPostal = this.buscarCodigoPostal(cliente.barrio);
            const resp = await fetch(`${URL_MAP_BASE}/${cliente.id}/coordenadas`, {
                method: 'PATCH',
                headers: {
                    'Content-Type':  'application/json',
                    'X-CSRF-TOKEN':  CSRF_TOKEN,
                    'Accept':        'application/json',
                },
                body: JSON.stringify({ latitud: lat, longitud: lon, codigo_postal: codigoPostal }),
            });
            if (resp.ok) {
                const idx = this.todosClientes.findIndex(c => c.id === cliente.id);
                if (idx !== -1) {
                    this.todosClientes[idx].latitud        = lat;
                    this.todosClientes[idx].longitud       = lon;
                    this.todosClientes[idx].codigo_postal  = codigoPostal;
                    this.clienteSeleccionado = this.todosClientes[idx];
                }
                this.aplicarFiltros();
                this.mapa.flyTo([lat, lon], 17, { duration: 1 });
            }
        },

        buscarCodigoPostal(barrio) {
            if (!barrio) return '';
            const bl = barrio.toLowerCase().trim();
            for (const [codigo, nombre] of Object.entries(ZONAS_PASTO)) {
                if (nombre.toLowerCase().includes(bl) || bl.includes(nombre.toLowerCase())) return codigo;
            }
            return '520001';
        },
    };
}
</script>

@endsection
