<?php

namespace App\Services\Numbering;

use Illuminate\Support\Facades\DB;

/**
 * Numéros uniques par préfixe et par année (TCK-2026-0001, CMD-2026-0001), attribués sous verrou dans la transaction
 * de l'appelant : deux enregistrements simultanés n'obtiennent jamais le même numéro. Le compteur repart à 1 chaque année.
 */
class SequenceService
{
    public function next(string $prefix, int $padding = 4): string
    {
        $year = (int) now()->format('Y');

        return DB::transaction(function () use ($prefix, $year, $padding) {
            DB::table('sequences')->insertOrIgnore(['prefix' => $prefix, 'year' => $year, 'last_number' => 0]);
            $row = DB::table('sequences')->where('prefix', $prefix)->where('year', $year)->lockForUpdate()->first();
            $number = $row->last_number + 1;
            DB::table('sequences')->where('id', $row->id)->update(['last_number' => $number]);

            return sprintf('%s-%d-%s', $prefix, $year, str_pad((string) $number, $padding, '0', STR_PAD_LEFT));
        });
    }
}
