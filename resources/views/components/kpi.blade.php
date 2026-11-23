@props(['label', 'value', 'hint' => null])
<div {{ $attributes->merge(['class' => 'card p-4']) }}>
    <div class="text-xs font-medium text-muted">{{ $label }}</div>
    <div class="mt-1 text-xl font-semibold num">{{ $value }}</div>
    @if ($hint)<div class="mt-1 text-xs text-muted">{{ $hint }}</div>@endif
</div>
