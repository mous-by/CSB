<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\Prestation;
use Illuminate\Validation\Rule;

class PrestationRequest extends ContentRequest
{
    protected function routeKey(): string
    {
        return 'prestation';
    }

    protected function modelClass(): string
    {
        return Prestation::class;
    }

    protected function nullableFields(): array
    {
        return ['description'];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'icon' => ['required', Rule::in(array_keys(Prestation::ICONS))],
            'summary' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:10000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'titre', 'icon' => 'icône', 'summary' => 'résumé', 'description' => 'description', 'sort_order' => 'ordre d\'affichage'];
    }
}
