<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-2xl border border-sky-200 bg-white px-5 py-3 text-xs font-semibold uppercase tracking-[0.25em] text-sky-800 shadow-sm shadow-sky-100/70 hover:-translate-y-0.5 hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
