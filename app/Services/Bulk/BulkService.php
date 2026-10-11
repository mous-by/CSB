<?php

namespace App\Services\Bulk;

use App\Models\User;
use App\Services\Audit\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Moteur de saisie en masse (BulkDefinition) : normalisation des valeurs saisies ou importées, validation commune
 * (erreurs par ligne et par colonne), enregistrement en TOUT OU RIEN dans une transaction avec un résumé dans l'audit,
 * modèle Excel (listes déroulantes, mode d'emploi) et lecture des fichiers .xlsx, .xls, .ods, .csv.
 */
class BulkService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Normalise, valide et enregistre des lignes. Les lignes entièrement vides sont ignorées.
     *
     * @param  array<int|string, array<string, mixed>>  $rows
     * @return int nombre d'éléments créés
     *
     * @throws ValidationException erreurs « rows.N.colonne » (N = index de la ligne reçue)
     */
    public function store(BulkDefinition $definition, array $rows, User $user, string $source = 'saisie'): int
    {
        $rows = $this->clean($definition, $rows);

        if ($rows === []) {
            throw ValidationException::withMessages(['rows' => 'Saisissez au moins une ligne.']);
        }
        if (count($rows) > $definition->maxRows()) {
            throw ValidationException::withMessages(['rows' => $definition->maxRows().' lignes au plus par envoi.']);
        }

        $validated = $this->validate($definition, $rows);

        foreach ($validated as $index => $row) {
            if (! $definition->authorizeRow($row, $user)) {
                throw ValidationException::withMessages(["rows.{$index}" => 'Vous n\'avez pas le droit de créer cet élément.']);
            }
        }

        return DB::transaction(function () use ($definition, $validated, $user, $source) {
            foreach ($validated as $row) {
                $definition->create($row, $user);
            }

            $count = count($validated);
            $this->logger->log($definition->module().'.bulk_created', $definition->module(),
                "Création en lot ({$source}) — {$count} {$definition->label()}", new: ['nombre' => $count, 'source' => $source], actor: $user);

            return $count;
        });
    }

    /**
     * Importe un fichier tableur. Rien n'est enregistré s'il y a une erreur.
     *
     * @return array{ok: bool, created: int, errors: list<array{line: int, column: string, message: string}>}
     */
    public function import(BulkDefinition $definition, string $path, string $originalName, User $user): array
    {
        $report = ['ok' => false, 'created' => 0, 'errors' => []];

        try {
            $reader = IOFactory::createReaderForFile($path);
            if ($reader instanceof Csv) {
                $reader->setInputEncoding(Csv::GUESS_ENCODING)->setDelimiter(null);
            }
            $reader->setReadDataOnly(true);
            $book = $reader->load($path);
        } catch (Throwable) {
            $report['errors'][] = ['line' => 0, 'column' => '—', 'message' => 'Fichier illisible : utilisez le modèle Excel (.xlsx), ou un fichier .xls, .ods ou .csv.'];

            return $report;
        }

        $sheet = $book->getSheetByName($this->sheetTitle($definition)) ?? $book->getSheet(0);
        $raw = $sheet->toArray(null, true, false, false);
        $map = $this->mapHeaders($definition, $raw[0] ?? []);

        foreach ($definition->columns() as $key => $column) {
            if (($column['required'] ?? false) && ! isset($map[$key])) {
                $report['errors'][] = ['line' => 1, 'column' => $column['label'], 'message' => 'Colonne obligatoire absente : téléchargez et utilisez le modèle.'];
            }
        }
        if ($report['errors'] !== []) {
            return $report;
        }

        $rows = [];
        foreach (array_slice($raw, 1, null, true) as $index => $cells) {
            $row = [];
            foreach (array_keys($definition->columns()) as $key) {
                $row[$key] = isset($map[$key]) ? ($cells[$map[$key]] ?? null) : null;
            }
            $rows[$index + 1] = $row; // numéro de ligne Excel
        }

        try {
            $report['created'] = $this->store($definition, $rows, $user, 'import « '.Str::limit($originalName, 80).' »');
            $report['ok'] = true;
        } catch (ValidationException $e) {
            $labels = collect($definition->columns())->map(fn ($c) => $c['label']);
            foreach ($e->errors() as $key => $messages) {
                [, $line, $column] = array_pad(explode('.', $key, 3), 3, null);
                $report['errors'][] = [
                    'line' => (int) $line,
                    'column' => $column ? ($labels[explode('.', $column)[0]] ?? $column) : '—',
                    'message' => $messages[0],
                ];
            }
        }

        return $report;
    }

    /** Modèle Excel : feuille de saisie (titres, listes déroulantes), « Listes » et « Mode d'emploi ». */
    public function template(BulkDefinition $definition): Spreadsheet
    {
        $book = new Spreadsheet;
        $book->getProperties()->setTitle('Modèle d\'import — '.$definition->label())->setCreator(config('brand.name'));
        $columns = $definition->columns();
        $maxRow = $definition->maxRows() + 1;

        $lists = $book->getActiveSheet()->setTitle('Listes');
        $sheet = $book->createSheet(0)->setTitle($this->sheetTitle($definition));

        $listColumn = 1;
        $col = 1;
        foreach ($columns as $column) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($letter.'1', $column['label'].(($column['required'] ?? false) ? ' *' : ''));
            $sheet->getColumnDimension($letter)->setWidth($column['width'] ?? 22);

            $options = $this->optionLabels($column);
            if ($options !== []) {
                $listLetter = Coordinate::stringFromColumnIndex($listColumn);
                $lists->setCellValue($listLetter.'1', $column['label']);
                foreach (array_values($options) as $i => $option) {
                    $lists->setCellValue($listLetter.($i + 2), $option);
                }
                $lists->getColumnDimension($listLetter)->setWidth(28);
                $validation = new DataValidation;
                $validation->setType(DataValidation::TYPE_LIST)->setErrorStyle(DataValidation::STYLE_STOP)->setAllowBlank(true)
                    ->setShowDropDown(true)->setShowInputMessage(true)->setShowErrorMessage(true)
                    ->setPromptTitle($column['label'])->setPrompt('Choisissez une valeur de la liste.')
                    ->setErrorTitle($column['label'])->setError('Choisissez une valeur de la liste.')
                    ->setFormula1('=Listes!$'.$listLetter.'$2:$'.$listLetter.'$'.(count($options) + 1));
                $sheet->setDataValidation("{$letter}2:{$letter}{$maxRow}", $validation);
                $listColumn++;
            }
            if (($column['type'] ?? 'text') === 'text') {
                $sheet->getStyle("{$letter}2:{$letter}{$maxRow}")->getNumberFormat()->setFormatCode('@');
            }
            $col++;
        }
        $lastLetter = Coordinate::stringFromColumnIndex(count($columns));
        $this->styleHeader($sheet, "A1:{$lastLetter}1");
        $this->styleHeader($lists, 'A1:'.Coordinate::stringFromColumnIndex(max(1, $listColumn - 1)).'1');
        $sheet->freezePane('A2');

        $help = $book->createSheet()->setTitle('Mode d\'emploi');
        $lines = [
            ['Import — '.$definition->label().' — mode d\'emploi'],
            [''],
            ['1. Remplissez la feuille « '.$this->sheetTitle($definition).' » : une ligne par élément, à partir de la ligne 2. Ne modifiez pas la ligne des titres.'],
            ['2. Les colonnes marquées * sont obligatoires. Les colonnes à liste déroulante n\'acceptent que les valeurs proposées.'],
            ['3. L\'import est « tout ou rien » : à la moindre erreur, rien n\'est enregistré et la liste des erreurs (ligne, colonne, motif) vous est affichée.'],
            ['4. '.$definition->maxRows().' lignes au plus par fichier. Formats acceptés : .xlsx, .xls, .ods, .csv (séparateur « ; » ou « , »).'],
            [''],
            ['Colonnes :'],
        ];
        foreach ($columns as $column) {
            $lines[] = ['• '.$column['label'].(($column['required'] ?? false) ? ' (obligatoire)' : '').(isset($column['help']) ? ' : '.$column['help'] : '').(isset($column['example']) ? ' — exemple : '.$column['example'] : '')];
        }
        foreach ($definition->instructions() as $instruction) {
            $lines[] = [$instruction];
        }
        $help->fromArray($lines, null, 'A1');
        $help->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $help->getColumnDimension('A')->setWidth(140);

        $book->setActiveSheetIndex(0);

        return $book;
    }

    public function sheetTitle(BulkDefinition $definition): string
    {
        return Str::limit(Str::ucfirst($definition->label()), 30, '');
    }

    /** Libellés proposés d'une colonne à choix. @return array<string, string> */
    public function optionLabels(array $column): array
    {
        return match ($column['type'] ?? 'text') {
            'select' => $column['options'] ?? [],
            'bool' => ['1' => 'Oui', '0' => 'Non'],
            default => [],
        };
    }

    /**
     * Valeurs nettoyées : textes rognés, nombres « 250 000 » ou « 2,5 », listes par libellé ou par valeur, booléens
     * oui / non. Lignes vides retirées (index conservés).
     *
     * @param  array<int|string, array<string, mixed>>  $rows
     * @return array<int|string, array<string, mixed>>
     */
    private function clean(BulkDefinition $definition, array $rows): array
    {
        $clean = [];
        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $out = [];
            foreach ($definition->columns() as $key => $column) {
                $value = $row[$key] ?? null;
                $value = is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : $value;
                $value = $value === '' ? null : $value;

                $out[$key] = match ($column['type'] ?? 'text') {
                    'number' => $value === null ? null : (is_numeric($n = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', str_replace(',', '.', (string) $value))) ? $n + 0 : $value),
                    'select' => $value === null ? null : $this->optionValue($column['options'] ?? [], (string) $value),
                    'bool' => $value === null ? ($column['default'] ?? false) : in_array(mb_strtolower((string) $value), ['1', 'oui', 'vrai', 'true', 'x', 'on', 'yes'], true),
                    'lines' => $value === null ? [] : array_values(array_filter(array_map('trim', is_array($value) ? $value : preg_split('/\R|;/', (string) $value)))),
                    default => $value === null ? null : (string) $value,
                };
            }

            $meaningful = array_filter($out, fn ($v, $k) => ! in_array(($definition->columns()[$k]['type'] ?? 'text'), ['bool'], true) && $v !== null && $v !== [], ARRAY_FILTER_USE_BOTH);
            if ($meaningful !== []) {
                $clean[$index] = $out;
            }
        }

        return $clean;
    }

    private function optionValue(array $options, string $value): string
    {
        foreach ($options as $key => $label) {
            if (mb_strtolower((string) $key) === mb_strtolower($value) || mb_strtolower((string) $label) === mb_strtolower($value)) {
                return (string) $key;
            }
        }

        return $value; // valeur inconnue : refusée par la règle « in »
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $rows
     * @return array<int|string, array<string, mixed>>
     */
    private function validate(BulkDefinition $definition, array $rows): array
    {
        $rules = [];
        $attributes = [];
        foreach ($definition->columns() as $key => $column) {
            $rule = $column['rules'] ?? [];
            if (($column['required'] ?? false) && ! in_array('required', $rule, true)) {
                array_unshift($rule, 'required');
            }
            if (($column['type'] ?? 'text') === 'select') {
                $rule[] = 'in:'.implode(',', array_map('strval', array_keys($column['options'] ?? [])));
            }
            if ($column['unique'] ?? false) {
                $rule[] = 'distinct:ignore_case';
            }
            $rules["rows.*.{$key}"] = $rule;
            $attributes["rows.*.{$key}"] = mb_strtolower($column['label']);
        }
        foreach ($definition->extraRules() as $key => $rule) {
            $rules["rows.*.{$key}"] = $rule;
        }

        $validator = Validator::make(['rows' => $rows], $rules, [
            'rows.*.*.distinct' => 'Cette valeur apparaît sur plusieurs lignes.',
            'rows.*.*.in' => 'Choisissez une valeur de la liste.',
        ], $attributes);

        return $validator->validate()['rows'];
    }

    /**
     * Colonnes repérées par leur titre (insensible à la casse, aux accents et à « * »), quel que soit leur ordre.
     *
     * @return array<string, int>
     */
    private function mapHeaders(BulkDefinition $definition, array $headers): array
    {
        $normalize = fn ($v) => Str::of((string) $v)->replace('*', '')->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '')->toString();
        $wanted = collect($definition->columns())->mapWithKeys(fn ($column, $key) => [$normalize($column['label']) => $key])->all();

        $map = [];
        foreach ($headers as $index => $header) {
            $key = $wanted[$normalize($header)] ?? null;
            if ($key !== null && ! isset($map[$key])) {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    private function styleHeader(Worksheet $sheet, string $range): void
    {
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1D4E89');
    }
}
