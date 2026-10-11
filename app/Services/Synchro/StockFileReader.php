<?php

namespace App\Services\Synchro;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * Lecture d'un fichier de stock (.xlsx, .xls, .ods, .csv) : colonnes repérées par leur titre (« Référence »,
 * « Quantité » ou « Stock », « Prix » facultatif), dans n'importe quel ordre.
 */
class StockFileReader
{
    private const HEADERS = [
        'reference' => ['reference', 'ref', 'code', 'code article', 'reference article'],
        'quantity' => ['quantite', 'qte', 'stock', 'quantite en stock', 'stock disponible'],
        'price' => ['prix', 'prix de vente', 'prix unitaire', 'pu'],
    ];

    /** @return list<array{reference: string, quantity: mixed, price: mixed}> */
    public function read(UploadedFile $file): array
    {
        try {
            $sheet = IOFactory::load($file->getRealPath())->getActiveSheet()->toArray(null, true, false, false);
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'Fichier illisible : enregistrez-le au format Excel (.xlsx) ou CSV.']);
        }

        $header = array_map(fn ($v) => Str::of((string) $v)->ascii()->lower()->trim()->toString(), array_shift($sheet) ?? []);
        $columns = [];
        foreach (self::HEADERS as $key => $names) {
            foreach ($header as $index => $title) {
                if (in_array($title, $names, true)) {
                    $columns[$key] = $index;
                    break;
                }
            }
        }
        if (! isset($columns['reference'], $columns['quantity'])) {
            throw ValidationException::withMessages(['file' => 'Colonnes « Référence » et « Quantité » introuvables sur la première ligne.']);
        }

        $items = [];
        foreach ($sheet as $row) {
            $reference = trim((string) ($row[$columns['reference']] ?? ''));
            if ($reference === '') {
                continue;
            }
            $items[] = [
                'reference' => $reference,
                'quantity' => $this->number($row[$columns['quantity']] ?? null),
                'price' => isset($columns['price']) ? $this->number($row[$columns['price']] ?? null) : null,
            ];
        }
        if (! $items) {
            throw ValidationException::withMessages(['file' => 'Aucune ligne à importer.']);
        }

        return $items;
    }

    /** Modèle à télécharger (une ligne d'exemple par matériel qui a une référence). */
    public function template(iterable $materiels): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('Stock');
        $sheet->fromArray(['Référence', 'Désignation (information)', 'Quantité', 'Prix (FCFA, facultatif)'], null, 'A1');
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $line = 2;
        foreach ($materiels as $materiel) {
            $sheet->fromArray([$materiel->reference, $materiel->designation, $materiel->stock_quantity ?? 0, $materiel->price], null, 'A'.$line++);
        }
        foreach (['A' => 18, 'B' => 45, 'C' => 12, 'D' => 22] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $path = tempnam(sys_get_temp_dir(), 'stock').'.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    /** « 1 250 », « 1.250 », 12 → entier ; texte non numérique gardé tel quel (refusé par la validation). */
    private function number(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) round((float) $value);
        }
        $clean = preg_replace('/[\s.\x{00A0}\x{202F}]/u', '', (string) $value);

        return is_numeric($clean) ? (int) $clean : $value;
    }
}
