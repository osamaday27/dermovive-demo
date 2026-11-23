@props(['label', 'name' => null])
<div {{ $attributes }}>
    <label class="lbl">{{ $label }}</label>
    {{ $slot }}
    @if ($name) @error($name)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror @endif
</div>
