<div class="login-root h-dvh lg:overflow-hidden grid lg:grid-cols-[1.1fr_1fr] bg-white">
    <style>
        .login-root { --teal: #1A3F3F; --teal-dark: #123030; --coral: #EE7782; --blush: #FDF1F2; --mint: #EAF2F0; }
        .login-root .brand-serif { font-family: 'Playfair Display', Georgia, serif; }
        [dir='rtl'] .login-root .brand-serif { font-family: 'IBM Plex Sans Arabic', sans-serif; font-weight: 600; }
        .login-root .btn-brand { background: var(--teal); color: #fff; border-radius: 4px; }
        .login-root .btn-brand:hover { background: var(--teal-dark); }
        .login-root .input:focus { border-color: var(--teal); --tw-ring-color: var(--teal); }
        @keyframes floaty { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-5px) } }
        .login-root .floaty { animation: floaty 6s ease-in-out infinite; }
        .login-root .floaty-2 { animation: floaty 7.5s ease-in-out infinite .8s; }
        @media (prefers-reduced-motion: reduce) { .login-root .floaty, .login-root .floaty-2 { animation: none; } }
        /* short screens: drop the illustration so nothing ever scrolls */
        @media (max-height: 640px) { .login-root .hero-art { display: none; } }
    </style>

    {{-- ============ BRAND PANEL: logo is the focus ============ --}}
    <section class="relative hidden lg:flex flex-col items-center justify-center overflow-hidden px-10 text-center"
             style="background: linear-gradient(160deg, var(--blush) 0%, #fff 50%, var(--mint) 100%)">
        <div class="absolute -top-20 -end-20 w-72 h-72 rounded-full opacity-35" style="background: var(--coral); filter: blur(80px)"></div>
        <div class="absolute -bottom-24 -start-20 w-80 h-80 rounded-full opacity-20" style="background: var(--teal); filter: blur(90px)"></div>

        <div class="relative flex flex-col items-center gap-[2.2vh] w-full max-w-xl">
            <img src="{{ asset('images/logo.png') }}" alt="{{ t('app.name') }}"
                 class="w-full max-w-[420px] max-h-[34vh] object-contain">

            <div class="h-px w-16" style="background: var(--coral)"></div>

            <h1 class="brand-serif text-2xl xl:text-3xl leading-snug font-semibold" style="color: var(--teal)">{{ t('auth.headline') }}</h1>
            <p class="max-w-md text-sm text-slate-600 leading-relaxed">{{ t('auth.sub') }}</p>

            <div class="hero-art h-[18vh] max-h-40 mt-1 opacity-95">
                <svg viewBox="0 0 520 360" class="h-full w-auto" role="img" aria-label="Skincare products" xmlns="http://www.w3.org/2000/svg">
                    {{-- soft backdrop --}}
                    <circle cx="270" cy="190" r="150" fill="#EE7782" opacity=".10"/>
                    <circle cx="270" cy="190" r="105" fill="#1A3F3F" opacity=".06"/>
                    {{-- botanical leaves --}}
                    <g opacity=".9">
                        <path d="M470 330 C 440 270, 470 215, 520 200 C 520 255, 505 305, 470 330Z" fill="#1A3F3F" opacity=".14"/>
                        <path d="M470 330 C 490 285, 500 250, 520 200" stroke="#1A3F3F" stroke-width="2" fill="none" opacity=".25"/>
                        <path d="M30 335 C 10 285, 30 235, 75 222 C 80 270, 65 310, 30 335Z" fill="#EE7782" opacity=".25"/>
                        <path d="M30 335 C 45 295, 55 262, 75 222" stroke="#EE7782" stroke-width="2" fill="none" opacity=".45"/>
                    </g>
                    <ellipse cx="260" cy="338" rx="232" ry="13" fill="#1A3F3F" opacity=".10"/>

                    {{-- tube --}}
                    <g class="floaty-2" transform="rotate(-14 90 270)">
                        <rect x="62" y="226" width="58" height="104" rx="10" fill="#EE7782"/>
                        <rect x="64" y="316" width="54" height="14" rx="3" fill="#D95F6B"/>
                        <rect x="74" y="198" width="34" height="32" rx="6" fill="#1A3F3F"/>
                        <rect x="70" y="248" width="42" height="44" rx="6" fill="#fff" opacity=".92"/>
                        <rect x="76" y="258" width="30" height="4" rx="2" fill="#1A3F3F" opacity=".7"/>
                        <rect x="76" y="268" width="22" height="3" rx="1.5" fill="#1A3F3F" opacity=".35"/>
                        <rect x="68" y="232" width="6" height="80" rx="3" fill="#fff" opacity=".25"/>
                    </g>

                    {{-- dropper serum bottle (hero) --}}
                    <g class="floaty">
                        <rect x="198" y="58" width="44" height="64" rx="20" fill="#EE7782"/>
                        <rect x="190" y="118" width="60" height="24" rx="5" fill="#0F2A2A"/>
                        <rect x="164" y="138" width="112" height="188" rx="20" fill="#1A3F3F"/>
                        <rect x="180" y="188" width="80" height="102" rx="9" fill="#fff" opacity=".96"/>
                        <circle cx="220" cy="212" r="9" fill="#EE7782"/>
                        <path d="M214 212 q6 -10 12 0" stroke="#fff" stroke-width="2" fill="none"/>
                        <rect x="194" y="232" width="52" height="5" rx="2.5" fill="#1A3F3F"/>
                        <rect x="200" y="244" width="40" height="3.5" rx="1.75" fill="#1A3F3F" opacity=".45"/>
                        <rect x="204" y="254" width="32" height="3.5" rx="1.75" fill="#1A3F3F" opacity=".3"/>
                        <rect x="174" y="150" width="9" height="160" rx="4.5" fill="#fff" opacity=".14"/>
                    </g>

                    {{-- cream jar --}}
                    <g class="floaty-2">
                        <rect x="298" y="238" width="128" height="34" rx="8" fill="#EE7782"/>
                        <rect x="304" y="232" width="116" height="10" rx="4" fill="#D95F6B"/>
                        <rect x="302" y="270" width="120" height="58" rx="14" fill="#F8DDE0"/>
                        <rect x="302" y="288" width="120" height="22" fill="#1A3F3F"/>
                        <rect x="322" y="296" width="80" height="4" rx="2" fill="#fff" opacity=".85"/>
                        <rect x="310" y="276" width="7" height="46" rx="3.5" fill="#fff" opacity=".5"/>
                    </g>

                    {{-- pump bottle (haircare) --}}
                    <g class="floaty">
                        <rect x="446" y="168" width="12" height="30" rx="3" fill="#1A3F3F"/>
                        <rect x="436" y="160" width="40" height="11" rx="5.5" fill="#1A3F3F"/>
                        <rect x="428" y="196" width="60" height="132" rx="13" fill="#F8DDE0"/>
                        <rect x="436" y="236" width="44" height="62" rx="6" fill="#1A3F3F"/>
                        <circle cx="458" cy="258" r="6" fill="#EE7782"/>
                        <rect x="444" y="272" width="28" height="3" rx="1.5" fill="#fff" opacity=".85"/>
                        <rect x="436" y="204" width="6" height="108" rx="3" fill="#fff" opacity=".55"/>
                    </g>

                    {{-- droplets --}}
                    <path d="M222 28 c-9 13 -13 19 -13 26 a13 13 0 0 0 26 0 c0 -7 -4 -13 -13 -26z" fill="#EE7782" opacity=".85"/>
                    <path d="M330 70 c-5 7 -7 10 -7 14 a7 7 0 0 0 14 0 c0 -4 -2 -7 -7 -14z" fill="#1A3F3F" opacity=".35"/>
                </svg>
            </div>
        </div>

        <div class="absolute bottom-4 text-xs text-slate-500">© {{ date('Y') }} {{ t('app.name') }}</div>
    </section>

    {{-- ============ SIGN-IN PANEL ============ --}}
    <section class="flex flex-col px-6 py-4 sm:px-12 overflow-y-auto lg:overflow-visible" style="background: #FCFCFD">
        <div class="flex items-center justify-between shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="{{ t('app.name') }}" class="h-10 w-auto lg:invisible">
            <div class="flex text-xs rounded overflow-hidden border border-slate-200 bg-white">
                @foreach (['ar' => 'عربي', 'en' => 'EN', 'fr' => 'FR'] as $code => $label)
                    <a href="{{ route('lang', $code) }}" class="px-3 py-1.5 transition {{ app()->getLocale() === $code ? 'text-white' : 'text-slate-500 hover:bg-slate-50' }}"
                       @if (app()->getLocale() === $code) style="background: var(--teal)" @endif>{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="flex-1 flex items-center justify-center py-4">
            <div class="w-full max-w-sm">
                <div class="mb-5">
                    <h2 class="brand-serif text-2xl font-semibold" style="color: var(--teal)">{{ t('auth.welcome') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ t('auth.welcome_sub') }}</p>
                </div>

                <div class="bg-white border border-slate-200 rounded-md p-5 sm:p-6">
                    <form wire:submit="login" class="space-y-4" x-data="{ show: false }">
                        <x-field :label="t('common.email')" name="email">
                            <input type="email" wire:model="email" class="input" autocomplete="username" autofocus placeholder="name@company.com">
                        </x-field>

                        <div>
                            <div class="flex items-center justify-between">
                                <label class="lbl">{{ t('auth.password') }}</label>
                                <button type="button" class="text-xs font-medium" style="color: var(--coral)" @click="show = !show"
                                        x-text="show ? '{{ t('auth.hide') }}' : '{{ t('auth.show') }}'"></button>
                            </div>
                            <input :type="show ? 'text' : 'password'" wire:model="password" class="input" autocomplete="current-password" placeholder="••••••••">
                            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <label class="flex items-center gap-2 text-sm text-slate-600 select-none">
                            <input type="checkbox" wire:model="remember" class="rounded border-slate-300"> {{ t('auth.remember') }}
                        </label>

                        <button class="btn-brand w-full py-2.5 text-sm font-semibold inline-flex items-center justify-center gap-2 transition disabled:opacity-60" wire:loading.attr="disabled" wire:target="login">
                            <svg wire:loading wire:target="login" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".3"/><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                            {{ t('auth.submit') }}
                        </button>
                    </form>

                    @if (app()->isLocal())
                        <div class="mt-4 rounded border p-2.5 text-xs" style="background: var(--blush); border-color: rgba(238,119,130,.35); color: var(--teal)">
                            <div class="font-semibold mb-0.5">{{ t('auth.demo_creds') }}</div>
                            <div class="num">admin@dermovive.demo &nbsp;·&nbsp; password</div>
                        </div>
                    @endif
                </div>

                <p class="mt-3 flex items-center justify-center gap-2 text-xs text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/></svg>
                    {{ t('auth.secure') }}
                </p>
            </div>
        </div>
    </section>
</div>
