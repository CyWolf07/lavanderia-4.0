@extends('layouts.app')

@section('title', 'Informe de incongruencias')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 rounded-[1.75rem] bg-white p-6 shadow-xl ring-1 ring-slate-200 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.35em] text-slate-500">Control de calidad</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900">Informe de incongruencias</h1>
            <p class="mt-2 text-sm text-slate-500">Listado histórico de inconsistencias entre registros del recolector y datos del sistema.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Volver al panel
            </a>
            <button onclick="window.print()" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                Imprimir informe
            </button>
        </div>
    </div>

    <div class="overflow-hidden rounded-[1.75rem] bg-white shadow-xl ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Fecha</th>
                        <th class="px-6 py-4 font-semibold">Recolector</th>
                        <th class="px-6 py-4 font-semibold">Factura</th>
                        <th class="px-6 py-4 font-semibold">Título</th>
                        <th class="px-6 py-4 font-semibold">Detalle</th>
                        <th class="px-6 py-4 font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($incongruencias as $item)
                        <tr>
                            <td class="px-6 py-4 text-slate-700">{{ optional($item->detectada_en)->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $item->recolector->name ?? 'Recolector eliminado' }}</td>
                            <td class="px-6 py-4 text-slate-700">#{{ str_pad((string) $item->factura_recolector_id, 6, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4 font-semibold text-rose-700">{{ $item->titulo }}</td>
                            <td class="px-6 py-4 text-slate-700">{{ $item->detalle }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $item->estado === 'pendiente' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ ucfirst($item->estado) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">No hay incongruencias registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-6 py-4">
            {{ $incongruencias->links() }}
        </div>
    </div>
</div>
@endsection
