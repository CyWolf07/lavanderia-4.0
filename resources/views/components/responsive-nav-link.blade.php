@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-xl bg-sky-100 px-3 py-2 text-start text-base font-semibold text-sky-900 ring-1 ring-sky-200 focus:outline-none'
            : 'block w-full rounded-xl px-3 py-2 text-start text-base font-medium text-slate-600 hover:bg-sky-50 hover:text-sky-800 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
