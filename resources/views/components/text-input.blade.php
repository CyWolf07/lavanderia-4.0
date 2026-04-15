@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-2xl border-slate-200 bg-white/95 px-4 py-3 text-slate-800 shadow-sm shadow-sky-100/70 focus:border-sky-400 focus:ring-sky-200']) }}>
