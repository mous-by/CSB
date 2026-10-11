<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\Temoignage;

class TemoignageRequest extends ContentRequest
{
    protected function routeKey(): string
    {
        return 'temoignage';
    }

    protected function modelClass(): string
    {
        return Temoignage::class;
    }

    protected function nullableFields(): array
    {
        return ['author_title', 'organization'];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:150'],
            'author_title' => ['nullable', 'string', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:1500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['author_name' => 'nom de l\'auteur', 'author_title' => 'fonction', 'organization' => 'organisation', 'content' => 'témoignage', 'sort_order' => 'ordre d\'affichage'];
    }
}
