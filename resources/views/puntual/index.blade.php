@extends('layouts.app')
@section('title', 'Puntual | Lavandería Exclusiva')
@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8" id="puntual" data-push-key="{{ $pushKey }}">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h1 class="text-3xl font-bold">Puntual</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $admin ? 'Todas las entregas' : 'Mis entregas' }} · {{ $hoy->format('d/m/Y') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" id="puntual-push" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Activar avisos</button>
            <button type="button" id="puntual-install" hidden class="rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white">Instalar</button>
            @if (config('puntual.apk_url'))
                <a id="puntual-apk" href="{{ config('puntual.apk_url') }}" class="rounded-lg border border-sky-700 px-4 py-2 text-sm font-semibold text-sky-800">Descargar APK</a>
            @endif
        </div>
    </div>
    <p id="puntual-feedback" role="status" class="mt-2 text-sm text-slate-600"></p>
    @if (session('status'))
        <p role="status" class="my-3 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</p>
    @endif
    <div class="grid grid-cols-3 gap-3 border-b border-slate-200 py-5">
        @foreach (['hoy' => ['Hoy', 'text-sky-800'], 'manana' => ['Mañana', 'text-emerald-800'], 'vencidas' => ['Vencidas', 'text-rose-700']] as $key => [$label, $color])
            <a href="{{ route('puntual.index', ['vista' => $key]) }}" class="min-w-0 {{ $color }}">
                <span class="block text-sm font-medium">{{ $label }}</span><strong class="text-3xl">{{ $conteos[$key] }}</strong>
            </a>
        @endforeach
    </div>
    @foreach ($avisos as $aviso)
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
            <a class="text-sm font-semibold text-amber-950" href="{{ route('puntual.index', ['orden' => $aviso->factura_recolector_id]) }}">Orden #{{ $aviso->factura->numero_orden }}: {{ $aviso->tipo === 'hoy' ? 'debes entregar las prendas hoy.' : 'debes entregar las prendas mañana.' }}</a>
            <form method="POST" action="{{ route('puntual.leer', $aviso) }}">@csrf @method('PATCH')<button class="text-sm font-semibold underline">Marcar leído</button></form>
        </div>
    @endforeach
    <nav aria-label="Estado de entregas" class="my-5 flex flex-wrap gap-2">
        @foreach (['pendientes' => 'Pendientes', 'hoy' => 'Hoy', 'manana' => 'Mañana', 'vencidas' => 'Vencidas', 'entregadas' => 'Entregadas', 'todas' => 'Todas'] as $key => $label)
            <a href="{{ route('puntual.index', ['vista' => $key]) }}" @if ($vista === $key) aria-current="page" @endif class="border-b-2 px-3 py-2 text-sm font-semibold {{ $vista === $key ? 'border-sky-700 text-sky-800' : 'border-transparent text-slate-600' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="divide-y divide-slate-200">
        @forelse ($ordenes as $orden)
            <article class="py-5" id="orden-{{ $orden->id }}">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 break-words">
                        <h2 class="text-lg font-bold">Orden #{{ $orden->numero_orden }}</h2>
                        <p class="font-medium">{{ $orden->cliente?->nombre ?? 'Cliente no disponible' }}</p>
                        @if ($admin)<p class="text-sm text-slate-600">Recolector: {{ $orden->recolector?->name ?? 'Sin asignar' }}</p>@endif
                        <p class="mt-1 text-sm text-slate-600">{{ $orden->direccion }}</p>
                    </div>
                    <div class="text-sm">
                        <p>Recolección: {{ $orden->fecha_ingreso?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                        <p class="font-semibold">Entrega: {{ $orden->fecha_entrega?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                        @if ($orden->entregado_en)
                            <p class="mt-1 font-semibold text-emerald-700">Entregada · {{ $orden->entregado_en->timezone(config('puntual.timezone'))->format('d/m/Y H:i') }}</p>
                        @elseif ($orden->fecha_entrega && $orden->fecha_entrega->toDateString() < $hoy->toDateString())
                            <p class="mt-1 font-semibold text-rose-700">Entrega vencida</p>
                        @endif
                    </div>
                </div>
                <details class="mt-3">
                    <summary class="cursor-pointer py-2 text-sm font-semibold text-sky-800">{{ $orden->total_prendas }} prendas · ${{ number_format($orden->total, 0, ',', '.') }}</summary>
                    <ul class="mt-2 space-y-2 text-sm">
                        @foreach ($orden->detalles as $detalle)
                            <li class="flex flex-wrap justify-between gap-2 border-b border-slate-100 py-2"><span>{{ $detalle->cantidad }} × {{ $detalle->prenda_nombre }} @if ($detalle->color_prenda) · {{ $detalle->color_prenda }} @endif</span><span>{{ $detalle->lavado_en ? 'Lavada' : 'Lavado pendiente' }}</span></li>
                        @endforeach
                    </ul>
                </details>
                @unless ($orden->entregado_en)
                    <form method="POST" action="{{ route('puntual.entregar', $orden) }}" class="mt-3" x-data @submit="if (!confirm('¿Confirmas que entregaste la orden #{{ $orden->numero_orden }} al cliente?')) $event.preventDefault()">
                        @csrf @method('PATCH')<button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Confirmar entrega</button>
                    </form>
                @endunless
            </article>
        @empty
            <p class="py-12 text-center text-slate-500">No hay entregas en esta vista.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $ordenes->links('components.pagination-arrows') }}</div>
</div>
@endsection
