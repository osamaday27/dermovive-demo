@if ($paginator->hasPages())
<div class="flex items-center justify-between px-3 py-3 text-sm text-muted no-print">
    <span class="num">{{ t('common.page') }} {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }} · {{ $paginator->total() }} {{ t('common.records') }}</span>
    <div class="flex gap-2">
        <button class="btn btn-sm" wire:click="previousPage('{{ $paginator->getPageName() }}')" @disabled($paginator->onFirstPage())>‹ {{ t('common.prev') }}</button>
        <button class="btn btn-sm" wire:click="nextPage('{{ $paginator->getPageName() }}')" @disabled(! $paginator->hasMorePages())>{{ t('common.next') }} ›</button>
    </div>
</div>
@endif
