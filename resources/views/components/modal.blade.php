@props(['title', 'close' => 'closeModal', 'width' => 'max-w-2xl'])
<div class="fixed inset-0 z-50 flex items-start justify-center p-4 overflow-y-auto print-root" x-data @keydown.escape.window="$wire.{{ $close }}()">
    <div class="fixed inset-0 bg-ink/50 no-print" wire:click="{{ $close }}"></div>
    <div class="relative bg-white rounded-md border border-line w-full {{ $width }} my-8 modal-scroll">
        <div class="flex items-center justify-between px-5 py-3 border-b border-line no-print">
            <h3 class="font-semibold">{{ $title }}</h3>
            <button type="button" class="text-muted hover:text-ink" wire:click="{{ $close }}" aria-label="{{ t('common.close') }}"><x-icon name="x" class="w-5 h-5"/></button>
        </div>
        <div class="p-5">{{ $slot }}</div>
    </div>
</div>
