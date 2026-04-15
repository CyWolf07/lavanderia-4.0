@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full bg-sky-100 px-4 py-2 text-sm font-semibold leading-5 text-sky-900 ring-1 ring-sky-200 shadow-sm shadow-sky-100/60 focus:outline-none'
            : 'inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold leading-5 text-slate-600 hover:bg-sky-50 hover:text-sky-800 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
