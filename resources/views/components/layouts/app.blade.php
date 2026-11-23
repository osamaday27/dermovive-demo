@php
    $nav = [
        ['dashboard', 'dashboard', 'home'], ['customers', 'customers', 'users'], ['inventory', 'inventory', 'box'],
        ['pos', 'pos', 'cart'], ['invoices', 'invoices', 'doc'], ['statements', 'statements', 'book'],
        ['collections', 'collections', 'cash'], ['treasury', 'treasury', 'bank'], ['reports', 'reports', 'chart'],
    ];
    $loc = app()->getLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ $loc }}" dir="{{ $loc === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? '') ? $title.' · ' : '' }}{{ t('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://googleapis.com" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased" x-data="{ side: false, toast: null }"
      @notify.window="toast = $event.detail; setTimeout(() => toast = null, 4000)">

<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <div x-show="side" x-cloak class="fixed inset-0 z-30 bg-ink/50 lg:hidden no-print" @click="side=false"></div>
    <aside class="no-print fixed inset-y-0 start-0 z-40 w-64 bg-ink text-white flex flex-col transition-transform
                  -translate-x-full rtl:translate-x-full lg:translate-x-0 lg:rtl:translate-x-0 lg:static lg:shrink-0"
           :class="side ? 'translate-x-0 rtl:translate-x-0' : ''">
        <div class="h-14 flex items-center gap-2 px-4 border-b border-white/10">
            <div class="w-8 h-8 rounded bg-accent flex items-center justify-center font-bold">D</div>
            <div class="font-semibold leading-tight">{{ t('app.name') }}<div class="text-[10px] font-normal text-slate-400">{{ t('app.tagline') }}</div></div>
        </div>
        <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
            @foreach ($nav as [$route, $key, $icon])
                <a href="{{ route($route) }}" class="navlink {{ request()->routeIs($route) ? 'active' : '' }}">
                    <x-icon :name="$icon" class="w-5 h-5"/> <span>{{ t('nav.'.$key) }}</span>
                </a>
            @endforeach
        </nav>
        <div class="p-3 border-t border-white/10 text-xs text-slate-400">{{ auth()->user()->email }}</div>
    </aside>

    <div class="flex-1 min-w-0 flex flex-col">
        {{-- Header --}}
        <header class="no-print h-14 bg-ink text-white flex items-center justify-between px-4 sticky top-0 z-20">
            <button class="lg:hidden p-1" @click="side=true" aria-label="Menu"><x-icon name="menu" class="w-6 h-6"/></button>
            <div class="hidden lg:block text-sm text-slate-300">{{ now()->translatedFormat('D, d M Y') }}</div>
            <div class="flex items-center gap-3">
                <div class="flex items-center text-xs rounded border border-white/20 overflow-hidden">
                    @foreach (['ar' => 'عربي', 'en' => 'EN', 'fr' => 'FR'] as $code => $label)
                        <a href="{{ route('lang', $code) }}" class="px-2.5 py-1 {{ $loc === $code ? 'bg-accent text-white' : 'text-slate-300 hover:bg-white/10' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="text-xs text-slate-300 hover:text-white px-2 py-1">{{ t('nav.logout') }}</button>
                </form>
            </div>
        </header>

        <main class="p-4 lg:p-6">{{ $slot }}</main>
    </div>
</div>

{{-- Toast --}}
<div x-show="toast" x-cloak x-transition class="fixed bottom-4 end-4 z-[60] max-w-sm no-print">
    <div class="rounded-md border px-4 py-3 text-sm bg-white shadow-sm"
         :class="toast && toast.type === 'error' ? 'border-red-300 text-red-700' : 'border-green-300 text-green-700'" x-text="toast && toast.message"></div>
</div>
<style>[x-cloak]{display:none!important}</style>
@livewireScripts
</body>
</html>
