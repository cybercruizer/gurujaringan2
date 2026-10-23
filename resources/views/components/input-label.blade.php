@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-stone-800']) }}>
    {{ $value ?? $slot }}
</label>
