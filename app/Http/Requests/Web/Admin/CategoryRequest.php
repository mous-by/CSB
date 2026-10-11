<?php

namespace App\Http\Requests\Web\Admin;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Validation\Rule;

class CategoryRequest extends ContentRequest
{
    protected function routeKey(): string
    {
        return 'category';
    }

    protected function modelClass(): string
    {
        return Category::class;
    }

    protected function nullableFields(): array
    {
        return ['description'];
    }

    protected function booleanFields(): array
    {
        return ['is_active'];
    }

    /** Famille : celle de la catégorie modifiée, ou celle de l'adresse en création. */
    public function type(): CategoryType
    {
        return $this->model()?->type ?? CategoryType::from((string) $this->route('type'));
    }

    public function authorize(): bool
    {
        $model = $this->model();

        return $model !== null
            ? ($this->user()?->can('update', $model) ?? false)
            : ($this->user()?->can('create', [Category::class, $this->type()]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->where('type', $this->type()->value)->ignore($this->model())],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'Une catégorie de cette famille porte déjà ce nom (éventuellement supprimée).'];
    }

    public function attributes(): array
    {
        return ['name' => 'nom', 'description' => 'description', 'sort_order' => 'ordre d\'affichage'];
    }
}
