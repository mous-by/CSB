<?php

namespace App\Services\Bulk\Definitions;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use App\Services\Bulk\BulkDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

/** Catégories en lot, toutes familles : chaque ligne exige le droit de la famille (CategoryType::permission()). */
class CategoryBulk extends BulkDefinition
{
    public function module(): string
    {
        return 'categories';
    }

    public function label(): string
    {
        return 'catégories';
    }

    public function icon(): string
    {
        return 'bx bx-category';
    }

    public function indexRoute(): string
    {
        return 'categories.index';
    }

    public function permission(): string
    {
        return 'logiciels.update|materiels.update|articles.update';
    }

    public function columns(): array
    {
        return [
            'type' => ['label' => 'Famille', 'required' => true, 'type' => 'select', 'options' => collect(CategoryType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all(), 'width' => 18],
            'name' => ['label' => 'Nom', 'required' => true, 'rules' => ['string', 'max:120'], 'width' => 34],
            'description' => ['label' => 'Description', 'rules' => ['nullable', 'string', 'max:500'], 'width' => 50],
            'sort_order' => ['label' => 'Ordre', 'type' => 'number', 'rules' => ['nullable', 'integer', 'min:0', 'max:9999'], 'default' => 0, 'width' => 10],
        ];
    }

    public function authorizeRow(array $row, User $user): bool
    {
        return $user->can(CategoryType::from($row['type'])->permission());
    }

    public function extraRules(): array
    {
        // Nom unique par famille, y compris parmi les catégories supprimées et dans le lot lui-même.
        return ['name' => [function (string $attribute, mixed $value, \Closure $fail, Validator $validator) {
            $index = explode('.', $attribute)[1];
            $rows = $validator->getData()['rows'];
            $type = $rows[$index]['type'] ?? null;
            if ($type && Category::withTrashed()->where('type', $type)->where('name', $value)->exists()) {
                $fail('Une catégorie de cette famille porte déjà ce nom.');
            }
            foreach ($rows as $other => $row) {
                if ((string) $other !== (string) $index && ($row['type'] ?? null) === $type && mb_strtolower((string) ($row['name'] ?? '')) === mb_strtolower((string) $value) && (string) $other < (string) $index) {
                    $fail('Ce nom apparaît déjà plus haut pour la même famille.');
                }
            }
        }]];
    }

    public function create(array $row, User $user): Model
    {
        return Category::create($row + ['sort_order' => 0]);
    }
}
