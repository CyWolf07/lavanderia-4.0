{{--
    Componente: input-celular
    Uso: <x-input-celular name="celular" :value="old('celular', $cliente->celular ?? '')" />

    Props:
      - name    : nombre del campo que llega al controlador (default: "celular")
      - value   : valor actual del campo (para edición)
      - required: si el campo es obligatorio (boolean)
      - class   : clases CSS extras para el wrapper
--}}

@props([
    'name'     => 'celular',
    'value'    => '',
    'required' => false,
    'class'    => '',
])

@php
    /*
     * Si el valor guardado ya empieza con el indicativo (ej: "573001234567")
     * lo mostramos sólo con el número local (300 1234567) para que el usuario
     * no vea el prefijo duplicado al editar.
     */
    $indicativosMap = [
        '+57' => '57', '+1'  => '1',  '+34' => '34', '+52' => '52',
        '+54' => '54', '+55' => '55', '+51' => '51', '+56' => '56',
        '+58' => '58', '+593'=> '593','+57' => '57',
    ];

    $indicativoSeleccionado = '+57'; // Colombia por defecto
    $numeroLocal = $value ?? '';

    // Detectar si el valor guardado ya lleva indicativo numérico y separarlo
    foreach ($indicativosMap as $ind => $digits) {
        $sinPlus = ltrim($ind, '+');
        if ($sinPlus !== '' && str_starts_with(preg_replace('/\D/', '', $numeroLocal), $sinPlus) && strlen(preg_replace('/\D/', '', $numeroLocal)) > strlen($sinPlus)) {
            $indicativoSeleccionado = $ind;
            $numeroLocal = substr(preg_replace('/\D/', '', $numeroLocal), strlen($sinPlus));
            break;
        }
    }
@endphp

<div
    class="flex overflow-hidden rounded-2xl border border-slate-300 focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100 {{ $class }}"
    x-data="{
        indicativo: '{{ $indicativoSeleccionado }}',
        numero: '{{ $numeroLocal }}',
        get combinado() {
            const digitos = this.numero.replace(/\D/g, '');
            if (!digitos) return '';
            return this.indicativo.replace('+', '') + digitos;
        }
    }"
>
    {{-- Selector de indicativo --}}
    <select
        x-model="indicativo"
        class="shrink-0 border-r border-slate-300 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700 focus:outline-none"
        aria-label="Indicativo de país"
    >
        <option value="+57">🇨🇴 +57</option>
        <option value="+1">🇺🇸 +1</option>
        <option value="+34">🇪🇸 +34</option>
        <option value="+52">🇲🇽 +52</option>
        <option value="+54">🇦🇷 +54</option>
        <option value="+55">🇧🇷 +55</option>
        <option value="+51">🇵🇪 +51</option>
        <option value="+56">🇨🇱 +56</option>
        <option value="+58">🇻🇪 +58</option>
        <option value="+593">🇪🇨 +593</option>
    </select>

    {{-- Campo de número visible al usuario (solo dígitos locales) --}}
    <input
        type="tel"
        inputmode="numeric"
        x-model="numero"
        placeholder="300 1234567"
        class="min-w-0 flex-1 bg-white px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none"
        {{ $required ? 'required' : '' }}
        aria-label="Número de celular"
    >

    {{-- Campo oculto que el servidor recibe con indicativo + número juntos --}}
    <input
        type="hidden"
        name="{{ $name }}"
        :value="combinado"
    >
</div>
