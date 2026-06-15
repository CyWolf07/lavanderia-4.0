@extends('layouts.app')

@section('title', 'Mapa de Clientes — Lavandería Exclusiva')

@section('content')
<div class="mx-auto max-w-screen-2xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

    {{-- ENCABEZADO --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-violet-700">Panel Admin</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Mapa de Clientes</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">
                Visualiza la ubicación de cada cliente en Pasto por barrio y zona postal. Usa los filtros para segmentar por zona o recolector.
            </p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
            ← Volver al dashboard
        </a>
    </div>

    {{-- ESTADÍSTICAS RÁPIDAS --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-[1.5rem] bg-white p-5 shadow-md ring-1 ring-slate-200">
            <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Total clientes</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ count($clientes) }}</p>
        </div>
        <div class="rounded-[1.5rem] bg-violet-600 p-5 text-white shadow-md">
            <p class="text-xs uppercase tracking-[0.25em] text-violet-200">Con ubicación</p>
            <p class="mt-2 text-3xl font-black">{{ $clientes->filter(fn($c) => $c['latitud'] !== null)->count() }}</p>
        </div>
        <div class="rounded-[1.5rem] bg-slate-900 p-5 text-white shadow-md">
            <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Sin ubicación</p>
            <p class="mt-2 text-3xl font-black">{{ $clientes->filter(fn($c) => $c['latitud'] === null)->count() }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]" x-data="mapaClientes()">

        {{-- PANEL DE FILTROS --}}
        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <div class="rounded-[1.5rem] bg-white p-5 shadow-md ring-1 ring-slate-200">
                <h2 class="text-sm font-bold uppercase tracking-[0.22em] text-slate-700">Filtrar clientes</h2>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Buscar por nombre</label>
                        <input
                            type="text"
                            x-model="filtroNombre"
                            @input="aplicarFiltros()"
                            placeholder="Nombre del cliente..."
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Zona / Código postal</label>
                        <select x-model="filtroZona" @change="aplicarFiltros()" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">Todas las zonas</option>
                            @foreach ($zonas as $codigo => $nombre)
                                <option value="{{ $codigo }}">{{ $codigo }} — {{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Barrio</label>
                        <input
                            type="text"
                            x-model="filtroBarrio"
                            @input="aplicarFiltros()"
                            placeholder="Nombre del barrio..."
                            class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-500">Solo con ubicación</label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" x-model="soloConUbicacion" @change="aplicarFiltros()" class="h-4 w-4 rounded text-violet-600">
                            <span class="text-sm text-slate-700">Mostrar solo clientes con coordenadas</span>
                        </label>
                    </div>

                    <button @click="resetFiltros()" class="w-full rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            {{-- LISTA DE CLIENTES FILTRADOS --}}
            <div class="max-h-[50vh] overflow-y-auto rounded-[1.5rem] bg-white shadow-md ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                        Clientes visibles: <span class="text-violet-700" x-text="clientesFiltrados.length"></span>
                    </p>
                </div>
                <div class="divide-y divide-slate-50">
                    <template x-for="c in clientesFiltrados" :key="c.id">
                        <button
                            @click="centrarEnCliente(c)"
                            class="w-full px-5 py-3 text-left transition hover:bg-violet-50"
                            :class="clienteSeleccionado?.id === c.id ? 'bg-violet-50' : ''"
                        >
                            <p class="text-sm font-semibold text-slate-900" x-text="c.nombre"></p>
                            <p class="text-xs text-slate-500" x-text="(c.barrio || 'Sin barrio') + ' · ' + (c.codigo_postal || 'Sin CP')"></p>
                            <span
                                class="mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-bold"
                                :class="c.latitud ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                x-text="c.latitud ? 'Con ubicación' : 'Sin ubicación'"
                            ></span>
                        </button>
                    </template>
                    <template x-if="clientesFiltrados.length === 0">
                        <p class="px-5 py-4 text-sm text-slate-400">Sin resultados para los filtros aplicados.</p>
                    </template>
                </div>
            </div>
        </aside>

        {{-- MAPA PRINCIPAL --}}
        <div class="space-y-4">
            <div id="mapa-leaflet" class="h-[70vh] min-h-[450px] w-full rounded-[1.5rem] shadow-xl ring-1 ring-slate-200 overflow-hidden"></div>

            {{-- PANEL INFO CLIENTE SELECCIONADO --}}
            <div x-show="clienteSeleccionado" x-cloak class="rounded-[1.5rem] bg-white p-5 shadow-md ring-1 ring-violet-200">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-violet-700">Cliente seleccionado</p>
                        <p class="mt-1 text-xl font-black text-slate-900" x-text="clienteSeleccionado?.nombre"></p>
                        <div class="mt-2 grid gap-1 text-sm text-slate-600 sm:grid-cols-2">
                            <p><span class="font-semibold">Cliente #:</span> <span x-text="clienteSeleccionado?.numero_cliente"></span></p>
                            <p><span class="font-semibold">Celular:</span> <span x-text="clienteSeleccionado?.celular || 'N/A'"></span></p>
                            <p><span class="font-semibold">Barrio:</span> <span x-text="clienteSeleccionado?.barrio || 'Sin barrio'"></span></p>
                            <p><span class="font-semibold">Dirección:</span> <span x-text="clienteSeleccionado?.direccion || 'Sin dirección'"></span></p>
                            <p><span class="font-semibold">C.P.:</span> <span x-text="clienteSeleccionado?.codigo_postal || 'Sin código postal'"></span></p>
                            <p><span class="font-semibold">Recolector:</span> <span x-text="clienteSeleccionado?.recolector"></span></p>
                        </div>
                    </div>
                    <button @click="clienteSeleccionado = null" class="rounded-full border border-slate-200 p-2 text-slate-500 hover:bg-slate-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div x-show="!clienteSeleccionado?.latitud" class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    ⚠️ Este cliente no tiene coordenadas asignadas. Haz clic en el mapa para asignarlas o usa la búsqueda de Nominatim.
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button
                        @click="geocodificarCliente(clienteSeleccionado)"
                        class="rounded-full bg-violet-600 px-4 py-2 text-xs font-bold text-white hover:bg-violet-700"
                    >
                        🔍 Auto-geocodificar dirección
                    </button>
                    <span x-show="geocodificandoId === clienteSeleccionado?.id" class="rounded-full bg-slate-100 px-4 py-2 text-xs text-slate-500">Buscando...</span>
                </div>
            </div>

            {{-- LEYENDA --}}
            <div class="rounded-[1.5rem] bg-white p-5 shadow-md ring-1 ring-slate-200">
                <h3 class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Leyenda</h3>
                <div class="mt-3 flex flex-wrap gap-4 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-violet-600"></span>
                        <span>Con coordenadas</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                        <span>Sin coordenadas (barrio aproximado)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="h-3 w-3 rounded-full bg-slate-400"></span>
                        <span>Sin información de ubicación</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Leaflet CSS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
{{-- Leaflet JS --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN2GGnk=" crossorigin=""></script>

<style>
.leaflet-container { font-family: inherit; }
.marker-cluster-small { background-color: rgba(124, 58, 237, 0.2); }
.marker-cluster-small div { background-color: rgba(124, 58, 237, 0.5); }
.custom-pin { border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
</style>

<script>
const CLIENTES_DATA = @json($clientes);
const ZONAS_PASTO = @json($zonas);
const CSRF_TOKEN = '{{ csrf_token() }}';
const URL_COORDENADAS = '{{ url('/admin/mapa-clientes') }}';

// Centro de Pasto, Nariño
const PASTO_CENTER = [1.2136, -77.2811];

function mapaClientes() {
    return {
        todosClientes: CLIENTES_DATA,
        clientesFiltrados: CLIENTES_DATA,
        clienteSeleccionado: null,
        filtroNombre: '',
        filtroZona: '',
        filtroBarrio: '',
        soloConUbicacion: false,
        geocodificandoId: null,
        mapa: null,
        marcadores: {},
        capaMarcadores: null,

        init() {
            this.$nextTick(() => {
                this.inicializarMapa();
            });
        },

        inicializarMapa() {
            this.mapa = L.map('mapa-leaflet').setView(PASTO_CENTER, 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.mapa);

            this.capaMarcadores = L.layerGroup().addTo(this.mapa);

            this.renderizarMarcadores(this.todosClientes);

            // Clic en mapa para asignar coordenadas al cliente seleccionado
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
                if (c.latitud && c.longitud) {
                    const color = '#7c3aed';
                    const marker = L.circleMarker([c.latitud, c.longitud], {
                        radius: 9,
                        fillColor: color,
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.9,
                    });

                    marker.bindPopup(`
                        <div style="min-width:180px; font-family:inherit">
                            <p style="font-weight:700; font-size:14px; margin:0 0 4px">${c.nombre}</p>
                            <p style="font-size:12px; color:#64748b; margin:0">Cliente #${c.numero_cliente}</p>
                            <p style="font-size:12px; margin:2px 0"><b>Barrio:</b> ${c.barrio || 'N/A'}</p>
                            <p style="font-size:12px; margin:2px 0"><b>Dirección:</b> ${c.direccion || 'N/A'}</p>
                            <p style="font-size:12px; margin:2px 0"><b>Celular:</b> ${c.celular || 'N/A'}</p>
                            <p style="font-size:12px; margin:2px 0"><b>C.P.:</b> ${c.codigo_postal || 'N/A'}</p>
                            <p style="font-size:12px; color:#7c3aed; margin:4px 0 0"><b>Recolector:</b> ${c.recolector}</p>
                        </div>
                    `);

                    marker.on('click', () => {
                        this.clienteSeleccionado = c;
                    });

                    marker.addTo(this.capaMarcadores);
                    this.marcadores[c.id] = marker;
                }
            });
        },

        aplicarFiltros() {
            this.clientesFiltrados = this.todosClientes.filter(c => {
                const matchNombre = !this.filtroNombre || c.nombre.toLowerCase().includes(this.filtroNombre.toLowerCase());
                const matchZona = !this.filtroZona || c.codigo_postal === this.filtroZona;
                const matchBarrio = !this.filtroBarrio || (c.barrio || '').toLowerCase().includes(this.filtroBarrio.toLowerCase());
                const matchUbicacion = !this.soloConUbicacion || c.latitud !== null;
                return matchNombre && matchZona && matchBarrio && matchUbicacion;
            });

            this.renderizarMarcadores(this.clientesFiltrados);
        },

        resetFiltros() {
            this.filtroNombre = '';
            this.filtroZona = '';
            this.filtroBarrio = '';
            this.soloConUbicacion = false;
            this.clientesFiltrados = this.todosClientes;
            this.renderizarMarcadores(this.todosClientes);
        },

        centrarEnCliente(cliente) {
            this.clienteSeleccionado = cliente;
            if (cliente.latitud && cliente.longitud) {
                this.mapa.flyTo([cliente.latitud, cliente.longitud], 17, { duration: 1 });
                if (this.marcadores[cliente.id]) {
                    this.marcadores[cliente.id].openPopup();
                }
            } else {
                // Sin coordenadas: centrar en Pasto y mostrar aviso
                this.mapa.flyTo(PASTO_CENTER, 14, { duration: 1 });
            }
        },

        async geocodificarCliente(cliente) {
            if (!cliente) return;
            this.geocodificandoId = cliente.id;

            const query = [cliente.direccion, cliente.barrio, 'Pasto', 'Nariño', 'Colombia']
                .filter(Boolean).join(', ');

            try {
                const resp = await fetch(
                    `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`,
                    { headers: { 'Accept-Language': 'es' } }
                );
                const data = await resp.json();

                if (data && data[0]) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    await this.asignarCoordenadas(cliente, lat, lon);
                } else {
                    alert('No se encontró la dirección en OpenStreetMap. Intenta hacer clic directamente en el mapa.');
                }
            } catch (e) {
                alert('Error al geocodificar. Verifica tu conexión.');
            } finally {
                this.geocodificandoId = null;
            }
        },

        async asignarCoordenadas(cliente, lat, lon) {
            // Determinar código postal por barrio
            const codigoPostal = this.buscarCodigoPostal(cliente.barrio);

            const resp = await fetch(`${URL_COORDENADAS}/${cliente.id}/coordenadas`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ latitud: lat, longitud: lon, codigo_postal: codigoPostal }),
            });

            if (resp.ok) {
                // Actualizar datos locales
                const idx = this.todosClientes.findIndex(c => c.id === cliente.id);
                if (idx !== -1) {
                    this.todosClientes[idx].latitud = lat;
                    this.todosClientes[idx].longitud = lon;
                    this.todosClientes[idx].codigo_postal = codigoPostal;
                    this.clienteSeleccionado = this.todosClientes[idx];
                }
                this.aplicarFiltros();
                this.mapa.flyTo([lat, lon], 17, { duration: 1 });
            }
        },

        buscarCodigoPostal(barrio) {
            if (!barrio) return '';
            const barrioLower = barrio.toLowerCase().trim();
            for (const [codigo, nombre] of Object.entries(ZONAS_PASTO)) {
                if (nombre.toLowerCase().includes(barrioLower) || barrioLower.includes(nombre.toLowerCase())) {
                    return codigo;
                }
            }
            // Si no coincide exacto, devolver el más cercano
            return '520001'; // Centro como fallback
        },
    };
}
</script>
@endsection
