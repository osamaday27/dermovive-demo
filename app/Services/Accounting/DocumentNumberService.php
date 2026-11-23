<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /** Gap-free, race-safe sequence. MUST be called inside a DB transaction (row lock). */
    public function next(string $type, int $pad = 6): string
    {
        $row = DB::table('document_sequences')->where('type', $type)->lockForUpdate()->first();
        if (! $row) {
            DB::table('document_sequences')->insert(['type' => $type, 'last_number' => 0]);
            $row = DB::table('document_sequences')->where('type', $type)->lockForUpdate()->first();
        }
        $n = $row->last_number + 1;
        DB::table('document_sequences')->where('type', $type)->update(['last_number' => $n]);
        return $type.'-'.str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
    }
}
