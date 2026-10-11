<?php

namespace App\Services\Bulk\Definitions;

use App\Enums\CategoryType;
use App\Enums\LogicielStatus;
use App\Enums\Platform;
use App\Models\Category;
use App\Models\Logiciel;
use App\Models\User;
use App\Services\Bulk\BulkDefinition;
use Illuminate\Database\Eloquent\Model;

/** Fiches logiciel en lot : logo et captures s'ajoutent ensuite fiche par fiche. */
class LogicielBulk extends BulkDefinition
{
    public function module(): string
    {
        return 'logiciels';
    }

    public function label(): string
    {
        return 'logiciels';
    }

    public function icon(): string
    {
        return 'bx bx-code-block';
    }

    public function indexRoute(): string
    {
        return 'logiciels.index';
    }

    public function columns(): array
    {
        return [
            'name' => ['label' => 'Nom', 'required' => true, 'rules' => ['string', 'max:150'], 'unique' => true, 'width' => 24],
            'tagline' => ['label' => 'Accroche', 'rules' => ['nullable', 'string', 'max:120'], 'width' => 30, 'example' => 'Gestion scolaire web et mobile'],
            'category_id' => ['label' => 'Catégorie', 'required' => true, 'type' => 'select', 'options' => Category::ofType(CategoryType::Logiciel)->active()->ordered()->pluck('name', 'id')->mapWithKeys(fn ($n, $id) => [(string) $id => $n])->all(), 'width' => 30],
            'summary' => ['label' => 'Résumé', 'required' => true, 'type' => 'textarea', 'rules' => ['string', 'max:300'], 'width' => 60],
            'status' => ['label' => 'Statut', 'required' => true, 'type' => 'select', 'options' => LogicielStatus::options(), 'default' => 'disponible', 'width' => 18],
            'platforms' => ['label' => 'Plateformes', 'type' => 'lines', 'rules' => ['array'], 'width' => 22, 'help' => 'Web, Android, iOS, Windows ou Autre, séparées par « ; ».', 'example' => 'Web ; Android'],
            'features' => ['label' => 'Fonctionnalités', 'type' => 'lines', 'rules' => ['array', 'max:40'], 'width' => 60, 'help' => 'Une par ligne ou séparées par « ; ».'],
            'is_published' => ['label' => 'Publié', 'type' => 'bool', 'default' => false, 'width' => 10],
        ];
    }

    public function extraRules(): array
    {
        $platforms = collect(Platform::cases())->flatMap(fn ($p) => [$p->value, mb_strtolower($p->label())])->unique()->implode(',');

        return [
            'platforms.*' => [fn (string $attribute, mixed $value, \Closure $fail) => in_array(mb_strtolower((string) $value), explode(',', $platforms), true) ? null : $fail("Plateforme « {$value} » inconnue (Web, Android, iOS, Windows, Autre).")],
            'features.*' => ['string', 'max:300'],
        ];
    }

    public function create(array $row, User $user): Model
    {
        $row['platforms'] = array_values(array_unique(array_map(fn ($p) => collect(Platform::cases())->first(fn ($c) => in_array(mb_strtolower($p), [$c->value, mb_strtolower($c->label())], true))->value, $row['platforms'] ?? [])));
        if (! $user->can('logiciels.publish')) {
            $row['is_published'] = false;
            if ($row['status'] === LogicielStatus::Archive->value) {
                $row['status'] = LogicielStatus::Disponible->value;
            }
        }

        return Logiciel::create($row + ['sort_order' => 0]);
    }
}
