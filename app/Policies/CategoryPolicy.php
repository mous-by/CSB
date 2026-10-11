<?php

namespace App\Policies;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;

/**
 * Les catégories d'une famille relèvent de la permission de modification du module concerné
 * (logiciels.update, materiels.update, articles.update : CategoryType::permission()).
 */
class CategoryPolicy
{
    public function viewAny(User $user, ?CategoryType $type = null): bool
    {
        $types = $type ? [$type] : CategoryType::cases();

        return collect($types)->contains(fn (CategoryType $t) => $user->can($t->permission()));
    }

    public function create(User $user, CategoryType $type): bool
    {
        return $user->can($type->permission());
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can($category->type->permission());
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can($category->type->permission());
    }
}
