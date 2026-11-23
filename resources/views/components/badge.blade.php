@props(['color' => 'gray'])
<span {{ $attributes->merge(['class' => 'badge b-'.$color]) }}>{{ $slot }}</span>
