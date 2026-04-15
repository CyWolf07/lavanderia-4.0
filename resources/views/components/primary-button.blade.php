<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-sky-700 via-sky-600 to-emerald-500 px-5 py-3 text-xs font-semibold uppercase tracking-[0.25em] text-white shadow-lg shadow-sky-200/80 hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-2 active:scale-[0.99]']) }}>
    {{ $slot }}
</button>
