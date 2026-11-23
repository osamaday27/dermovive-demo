<div class="min-h-screen grid lg:grid-cols-2">
    <div class="hidden lg:flex bg-ink text-white p-12 flex-col justify-between">
        <div class="flex items-center gap-3"><div class="w-10 h-10 rounded bg-accent flex items-center justify-center font-bold text-lg">D</div><span class="text-xl font-semibold">{{ t('app.name') }}</span></div>
        <div>
            <h1 class="text-3xl font-semibold leading-snug">{{ t('auth.headline') }}</h1>
            <p class="mt-3 text-slate-400 max-w-md">{{ t('auth.sub') }}</p>
        </div>
        <div class="text-xs text-slate-500">© {{ date('Y') }} {{ t('app.name') }} · Demo</div>
    </div>
    <div class="flex items-center justify-center p-6 bg-page">
        <div class="w-full max-w-sm">
            <div class="flex justify-end text-xs mb-6">
                @foreach (['ar' => 'عربي', 'en' => 'EN', 'fr' => 'FR'] as $code => $label)
                    <a href="{{ route('lang', $code) }}" class="px-2.5 py-1 border border-line {{ app()->getLocale() === $code ? 'bg-ink text-white' : 'bg-white text-muted' }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="card p-6">
                <h2 class="text-lg font-semibold">{{ t('auth.title') }}</h2>
                <form wire:submit="login" class="mt-5 space-y-4">
                    <x-field :label="t('common.email')" name="email"><input type="email" wire:model="email" class="input" autocomplete="username" autofocus></x-field>
                    <x-field :label="t('auth.password')" name="password"><input type="password" wire:model="password" class="input" autocomplete="current-password"></x-field>
                    <label class="flex items-center gap-2 text-sm text-muted"><input type="checkbox" wire:model="remember"> {{ t('auth.remember') }}</label>
                    <button class="btn btn-primary w-full" wire:loading.attr="disabled">{{ t('auth.submit') }}</button>
                </form>
                @if (app()->isLocal())
                    <div class="mt-5 rounded border border-line bg-accent-soft p-3 text-xs text-ink">
                        <div class="font-semibold mb-1">{{ t('auth.demo_creds') }}</div>
                        <div class="num">admin@dermovive.demo</div><div class="num">password</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
