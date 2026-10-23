@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-stone-300 text-stone-900 shadow-sm focus:border-stone-500 focus:ring-stone-500']) }}>
