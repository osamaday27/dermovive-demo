@props(['name'])
@php
$paths = [
 'home' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10',
 'users' => 'M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M10 10a4 4 0 100-8 4 4 0 000 8M21 20v-2a4 4 0 00-3-3.9M16 2.1a4 4 0 010 7.8',
 'box' => 'M21 8l-9-5-9 5v8l9 5 9-5V8zM3 8l9 5 9-5M12 13v8',
 'cart' => 'M3 3h2l.4 2M7 13h10l3-8H5.4M7 13L5.4 5M7 13l-2 4h12M9 21a1 1 0 100-2 1 1 0 000 2M17 21a1 1 0 100-2 1 1 0 000 2',
 'doc' => 'M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-5-5zM14 3v5h5M9 13h6M9 17h6',
 'book' => 'M4 19V5a2 2 0 012-2h13v16H6a2 2 0 00-2 2zm0 0a2 2 0 002 2h13M9 7h6',
 'cash' => 'M3 7h18v10H3zM12 14a2 2 0 100-4 2 2 0 000 4M6 10v.01M18 14v.01',
 'bank' => 'M3 10l9-6 9 6M5 10v8M9 10v8M15 10v8M19 10v8M3 21h18',
 'chart' => 'M4 20V10M10 20V4M16 20v-7M22 20H2',
 'menu' => 'M4 6h16M4 12h16M4 18h16',
 'x' => 'M6 6l12 12M18 6L6 18',
 'print' => 'M6 9V3h12v6M6 18H4a1 1 0 01-1-1v-6a1 1 0 011-1h16a1 1 0 011 1v6a1 1 0 01-1 1h-2M6 14h12v7H6z',
];
@endphp
<svg {{ $attributes->merge(['class' => 'w-5 h-5']) }} fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $paths[$name] ?? '' }}"/></svg>
