<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-600 px-5 py-3 text-xs font-semibold uppercase tracking-[0.25em] text-white shadow-lg shadow-rose-100/80 hover:-translate-y-0.5 hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-300 focus:ring-offset-2 active:scale-[0.99]']) }}>
    {{ $slot }}
</button>
