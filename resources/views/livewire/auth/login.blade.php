<div class="auth-shell" dir="ltr">

    <style>
        /* ============================================================
           AUTH SHELL — DESIGN TOKENS
        ============================================================ */
        .auth-shell {
            --teal: #1A3F3F;
            --teal-dark: #0F2828;
            --teal-soft: #2A5A5A;
            --coral: #EE7782;
            --coral-dark: #D95F6B;
            --blush: #FDF1F2;
            --mint: #EAF2F0;
            --ink: #0F172A;
            --muted: #64748B;
            --line: #E5E9EF;
            --bg: #FFFFFF;

            font-family: 'Inter', 'IBM Plex Sans Arabic', system-ui, sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
        }

        .auth-shell *,
        .auth-shell *::before,
        .auth-shell *::after {
            box-sizing: border-box;
        }

        .auth-shell .serif {
            font-family: 'Playfair Display', Georgia, serif;
            letter-spacing: -0.5px;
        }

        [dir="rtl"] .auth-shell .serif {
            font-family: 'IBM Plex Sans Arabic', sans-serif;
        }

        /* ============================================================
           LEFT — FORM PANEL
        ============================================================ */
        .auth-shell .form-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            padding: 40px 56px;
            overflow-y: auto;
            background: #fff;
        }

        /* Top bar */
        .auth-shell .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 48px;
        }

        .auth-shell .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 2.5px;
            color: var(--teal);
        }

        .auth-shell .brand-mark::before {
            content: "";
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background:
                radial-gradient(circle at 30% 30%, var(--coral), var(--coral-dark));
            box-shadow: 0 0 0 4px rgba(238,119,130,.12);
        }

        /* Language switcher */
        .auth-shell .lang {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            padding: 4px;
            background: #F8FAFC;
            border: 1px solid var(--line);
            border-radius: 999px;
        }

        .auth-shell .lang a {
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            text-decoration: none;
            border-radius: 999px;
            transition: all .2s ease;
        }

        .auth-shell .lang a:hover {
            color: var(--teal);
            background: #fff;
        }

        .auth-shell .lang a.active {
            background: var(--teal);
            color: #fff;
            box-shadow: 0 2px 6px rgba(26,63,63,.25);
        }

        /* Center column */
        .auth-shell .form-center {
            margin: auto 0;
            width: 100%;
            max-width: 420px;
            align-self: center;
        }

        /* Eyebrow */
        .auth-shell .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--coral);
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .auth-shell .eyebrow::before {
            content: "";
            width: 24px;
            height: 2px;
            background: var(--coral);
        }

        /* Heading */
        .auth-shell .headline {
            font-size: clamp(28px, 3vw, 36px);
            font-weight: 700;
            line-height: 1.15;
            color: var(--teal);
            margin: 0 0 10px;
            letter-spacing: -0.8px;
        }

        .auth-shell .subhead {
            font-size: 14px;
            line-height: 1.6;
            color: var(--muted);
            margin: 0 0 32px;
        }

        /* Fields */
        .auth-shell .field {
            margin-bottom: 18px;
        }

        .auth-shell .field-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            letter-spacing: .2px;
        }

        .auth-shell .field-input {
            width: 100%;
            height: 50px;
            padding: 0 16px;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            background: #FCFDFE;
            font-size: 14px;
            color: var(--ink);
            outline: none;
            transition: all .2s ease;
            font-family: inherit;
        }

        .auth-shell .field-input::placeholder {
            color: #B4BECD;
        }

        .auth-shell .field-input:hover {
            border-color: #CBD5E1;
        }

        .auth-shell .field-input:focus {
            border-color: var(--teal);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(26,63,63,.08);
        }

        .auth-shell .field-input.error {
            border-color: var(--coral);
            background: #FFF7F8;
        }

        .auth-shell .field-error {
            font-size: 11.5px;
            color: var(--coral-dark);
            margin-top: 6px;
            font-weight: 500;
        }

        /* Show/hide password */
        .auth-shell .link-btn {
            background: none;
            border: none;
            padding: 0;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--coral);
            cursor: pointer;
            transition: color .2s ease;
            font-family: inherit;
        }

        .auth-shell .link-btn:hover {
            color: var(--coral-dark);
        }

        /* Remember row */
        .auth-shell .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #475569;
            cursor: pointer;
            user-select: none;
            margin-bottom: 22px;
        }

        .auth-shell .remember input {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1.5px solid #CBD5E1;
            accent-color: var(--teal);
            cursor: pointer;
        }

        /* Submit */
        .auth-shell .btn-submit {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--teal) 0%, var(--teal-soft) 100%);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: .3px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all .25s ease;
            font-family: inherit;
            position: relative;
            overflow: hidden;
        }

        .auth-shell .btn-submit::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,.15) 100%);
            opacity: 0;
            transition: opacity .25s ease;
        }

        .auth-shell .btn-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(26,63,63,.22);
        }

        .auth-shell .btn-submit:hover:not(:disabled)::after {
            opacity: 1;
        }

        .auth-shell .btn-submit:active:not(:disabled) {
            transform: translateY(0);
        }

        .auth-shell .btn-submit:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        /* Focus ring */
        .auth-shell .field-input:focus-visible,
        .auth-shell .btn-submit:focus-visible,
        .auth-shell .lang a:focus-visible,
        .auth-shell .link-btn:focus-visible,
        .auth-shell .remember input:focus-visible {
            outline: 2px solid var(--coral);
            outline-offset: 2px;
        }

        /* Demo box */
        .auth-shell .demo {
            margin-top: 20px;
            padding: 12px 14px;
            background: var(--blush);
            border: 1px solid rgba(238,119,130,.28);
            border-radius: 10px;
            font-size: 12px;
            color: var(--teal);
        }

        .auth-shell .demo-title {
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: .3px;
        }

        .auth-shell .demo-code {
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 11.5px;
            letter-spacing: .2px;
        }

        /* Footer */
        .auth-shell .form-footer {
            margin-top: 40px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
            text-align: center;
        }

        .auth-shell .secure {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            color: #94A3B8;
            font-weight: 500;
        }

        .auth-shell .secure svg {
            width: 14px;
            height: 14px;
        }

        .auth-shell .copyright {
            font-size: 11px;
            color: #B4BECD;
            letter-spacing: .2px;
        }

        /* ============================================================
           RIGHT — BRAND PANEL
        ============================================================ */
        .auth-shell .brand-panel {
            position: relative;
            background:
                radial-gradient(circle at 20% 20%, rgba(238,119,130,.10) 0%, transparent 45%),
                radial-gradient(circle at 80% 80%, rgba(26,63,63,.10) 0%, transparent 45%),
                linear-gradient(135deg, #FDF1F2 0%, #FFFFFF 50%, #EAF2F0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 60px;
        }

        /* Decorative blobs */
        .auth-shell .brand-panel::before,
        .auth-shell .brand-panel::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
        }

        .auth-shell .brand-panel::before {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
            background: rgba(238,119,130,.22);
        }

        .auth-shell .brand-panel::after {
            width: 450px;
            height: 450px;
            bottom: -150px;
            left: -130px;
            background: rgba(26,63,63,.14);
        }

        /* Subtle grid overlay */
        .auth-shell .brand-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(26,63,63,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(26,63,63,.04) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
            pointer-events: none;
        }

        /* Brand card */
        .auth-shell .brand-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 480px;
            padding: 48px 40px;
            background: rgba(255,255,255,.65);
            backdrop-filter: blur(20px) saturate(140%);
            -webkit-backdrop-filter: blur(20px) saturate(140%);
            border: 1px solid rgba(255,255,255,.9);
            border-radius: 24px;
            box-shadow:
                0 20px 60px rgba(26,63,63,.08),
                0 2px 8px rgba(26,63,63,.04);
            text-align: center;
            animation: brandFadeUp .8s ease both;
        }

        @keyframes brandFadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .auth-shell .brand-logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 96px;
            height: 96px;
            margin: 0 auto 28px;
            border-radius: 50%;
            background: #fff;
            box-shadow:
                0 8px 24px rgba(26,63,63,.10),
                0 0 0 6px rgba(238,119,130,.08);
        }

        .auth-shell .brand-logo {
            max-width: 64px;
            max-height: 64px;
            object-fit: contain;
        }

        /* Accent divider */
        .auth-shell .brand-divider {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
        }

        .auth-shell .brand-divider span {
            display: block;
            height: 2px;
            border-radius: 2px;
        }

        .auth-shell .brand-divider .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            height: 6px;
        }

        .auth-shell .brand-divider .line-coral {
            width: 36px;
            background: var(--coral);
        }

        .auth-shell .brand-divider .line-teal {
            width: 36px;
            background: var(--teal);
        }

        .auth-shell .brand-divider .dot-coral { background: var(--coral); }
        .auth-shell .brand-divider .dot-teal  { background: var(--teal); }

        /* Brand text */
        .auth-shell .brand-title {
            font-size: 26px;
            font-weight: 700;
            line-height: 1.4;
            color: var(--teal);
            margin: 0 0 14px;
            letter-spacing: -0.3px;
        }

        .auth-shell .brand-desc {
            font-size: 14px;
            line-height: 1.7;
            color: #475569;
            margin: 0;
        }

        /* Decorative footer strip */
        .auth-shell .brand-strip {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 32px;
        }

        .auth-shell .brand-strip span {
            display: block;
            height: 1px;
            width: 40px;
            background: rgba(26,63,63,.2);
        }

        .auth-shell .brand-strip .badge {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--teal);
            text-transform: uppercase;
        }

        /* Brand footer */
        .auth-shell .brand-footer {
            position: absolute;
            bottom: 24px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 11px;
            color: #94A3B8;
            letter-spacing: .3px;
            z-index: 2;
        }

        /* ============================================================
           RESPONSIVE
        ============================================================ */
        @media (max-width: 1023px) {
            .auth-shell {
                grid-template-columns: 1fr;
                overflow: visible;
            }

            .auth-shell .brand-panel {
                order: -1;
                min-height: 320px;
                padding: 40px 24px;
            }

            .auth-shell .brand-card {
                padding: 32px 24px;
                border-radius: 20px;
            }

            .auth-shell .brand-logo-wrap {
                width: 72px;
                height: 72px;
                margin-bottom: 20px;
            }

            .auth-shell .brand-logo {
                max-width: 48px;
                max-height: 48px;
            }

            .auth-shell .brand-title {
                font-size: 21px;
            }

            .auth-shell .brand-desc {
                font-size: 13px;
            }

            .auth-shell .brand-footer {
                display: none;
            }

            .auth-shell .form-panel {
                padding: 32px 24px;
                min-height: auto;
            }

            .auth-shell .topbar {
                margin-bottom: 32px;
            }

            .auth-shell .form-center {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .auth-shell .form-panel {
                padding: 24px 18px 32px;
            }

            .auth-shell .brand-panel {
                min-height: 260px;
                padding: 28px 16px;
            }

            .auth-shell .brand-card {
                padding: 24px 18px;
            }

            .auth-shell .headline {
                font-size: 26px;
            }

            .auth-shell .field-input,
            .auth-shell .btn-submit {
                height: 48px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .auth-shell *,
            .auth-shell *::before,
            .auth-shell *::after {
                transition: none !important;
                animation: none !important;
            }
        }
    </style>


    {{-- ==========================================
         LEFT: FORM PANEL
    =========================================== --}}

    <section class="form-panel"
             dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

        {{-- Top bar --}}

        <div class="topbar">

            <div class="brand-mark">DERMOVIVE</div>

            <div class="lang" role="group" aria-label="Language">

                @foreach (['ar' => 'عربي', 'en' => 'EN', 'fr' => 'FR'] as $code => $label)

                    <a href="{{ route('lang', $code) }}"
                       @class(['active' => app()->getLocale() === $code])
                       @if (app()->getLocale() === $code) aria-current="page" @endif>

                        {{ $label }}

                    </a>

                @endforeach

            </div>

        </div>


        {{-- Form center --}}

        <div class="form-center">

            {{-- Eyebrow --}}
            <div class="eyebrow">

                {{ t('auth.welcome_sub') }}

            </div>

            {{-- Heading --}}
            <h1 class="headline">

                {{ t('auth.welcome') }}

            </h1>

            <p class="subhead">

                {{ t('auth.sub') }}

            </p>


            {{-- Form --}}

            <form wire:submit="login"
                  x-data="{ show: false }"
                  novalidate>


                {{-- Email --}}

                <div class="field">

                    <label class="field-label" for="email">

                        {{ t('common.email') }}

                    </label>

                    <input
                        id="email"
                        type="email"
                        wire:model="email"
                        class="field-input @error('email') error @enderror"
                        autocomplete="username"
                        autofocus
                        placeholder="name@company.com"
                    >

                    @error('email')

                        <p class="field-error">{{ $message }}</p>

                    @enderror

                </div>


                {{-- Password --}}

                <div class="field">

                    <div class="field-label">

                        <label for="password">{{ t('auth.password') }}</label>

                        <button type="button"
                                class="link-btn"
                                @click="show = !show"
                                :aria-label="show ? '{{ t('auth.hide') }}' : '{{ t('auth.show') }}'"
                                x-text="show
                                    ? '{{ t('auth.hide') }}'
                                    : '{{ t('auth.show') }}'">
                        </button>

                    </div>

                    <input
                        id="password"
                        :type="show ? 'text' : 'password'"
                        wire:model="password"
                        class="field-input @error('password') error @enderror"
                        autocomplete="current-password"
                        placeholder="••••••••"
                    >

                    @error('password')

                        <p class="field-error">{{ $message }}</p>

                    @enderror

                </div>


                {{-- Remember --}}

                <label class="remember">

                    <input type="checkbox" wire:model="remember">

                    {{ t('auth.remember') }}

                </label>


                {{-- Submit --}}

                <button type="submit"
                        class="btn-submit"
                        wire:loading.attr="disabled"
                        wire:target="login">

                    <svg wire:loading
                         wire:target="login"
                         width="16"
                         height="16"
                         viewBox="0 0 24 24"
                         fill="none"
                         style="animation: spin 1s linear infinite;">

                        <circle cx="12" cy="12" r="9"
                                stroke="currentColor"
                                stroke-width="3"
                                opacity=".3"/>

                        <path d="M21 12a9 9 0 00-9-9"
                              stroke="currentColor"
                              stroke-width="3"
                              stroke-linecap="round"/>

                    </svg>

                    {{ t('auth.submit') }}

                </button>

                <style>
                    @keyframes spin { to { transform: rotate(360deg); } }
                </style>

            </form>


            {{-- Demo credentials --}}

            @if (app()->isLocal())

                <div class="demo">

                    <div class="demo-title">{{ t('auth.demo_creds') }}</div>

                    <div class="demo-code">
                        admin@dermovive.demo · password
                    </div>

                </div>

            @endif


            {{-- Footer --}}

            <div class="form-footer">

                <div class="secure">

                    <svg fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         viewBox="0 0 24 24">

                        <rect x="5" y="11" width="14" height="9" rx="2"/>

                        <path d="M8 11V8a4 4 0 018 0v3"/>

                    </svg>

                    {{ t('auth.secure') }}

                </div>

                <div class="copyright">

                    © {{ date('Y') }} {{ t('app.name') }}. {{ t('auth.rights') ?? 'All rights reserved.' }}

                </div>

            </div>

        </div>

    </section>


    {{-- ==========================================
         RIGHT: BRAND PANEL
    =========================================== --}}

    <aside class="brand-panel"
           dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

        <div class="brand-grid"></div>

        <div class="brand-card">

            {{-- Logo --}}

            <div class="brand-logo-wrap">

                <img src="{{ asset('images/logo.png') }}"
                     alt="{{ t('app.name') }}"
                     class="brand-logo">

            </div>


            {{-- Divider --}}

            <div class="brand-divider">

                <span class="line-coral"></span>

                <span class="dot dot-teal"></span>

                <span class="dot dot-coral"></span>

                <span class="dot dot-teal"></span>

                <span class="line-teal"></span>

            </div>


            {{-- Headline --}}

            <h2 class="brand-title serif">

                {{ t('auth.headline') }}

            </h2>


            {{-- Description --}}

            <p class="brand-desc">

                {{ t('auth.sub') }}

            </p>


            {{-- Strip --}}

            <div class="brand-strip">

                <span></span>

                <div class="badge">Est. 2025</div>

                <span></span>

            </div>

        </div>


        {{-- Footer --}}

        <div class="brand-footer">

            © {{ date('Y') }} {{ t('app.name') }}

        </div>

    </aside>

</div>