<?php

namespace App\Livewire\Concerns;

use Livewire\WithPagination;

/** Pagination + automatic "back to page 1" when any property listed in $resetOn changes. */
trait HasFilters
{
    use WithPagination;

    public function updated($name): void
    {
        if (property_exists($this, 'resetOn') && in_array($name, $this->resetOn, true)) {
            $this->resetPage();
        }
    }

    protected function notify(string $message, string $type = 'success'): void
    {
        $this->dispatch('notify', message: $message, type: $type);
    }

    protected static function like(string $term): string
    {
        return '%'.addcslashes(trim($term), '%_\\').'%';   // escape LIKE wildcards in user input
    }
}
