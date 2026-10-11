<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\Realisation;
use App\Services\Medias\MediaService;
use Illuminate\Validation\Rule;

/**
 * Réalisation (cahier §4) : titre et résumé obligatoires, client facultatif (seulement avec son autorisation), contexte,
 * besoin, solution, technologies (une par ligne), visuels, logiciel CSB utilisé, SEO.
 */
class RealisationRequest extends ContentRequest
{
    protected function routeKey(): string
    {
        return 'realisation';
    }

    protected function modelClass(): string
    {
        return Realisation::class;
    }

    protected function nullableFields(): array
    {
        return ['slug', 'client_name', 'sector', 'year', 'context', 'need', 'solution', 'cover_media_id', 'logiciel_id', 'seo_title', 'seo_description', 'seo_media_id'];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Technologies : liste de lignes ; un texte est découpé par lignes, virgules ou points-virgules.
        $items = $this->input('technologies');
        $items = is_array($items) ? $items : preg_split('/[\n,;]+/', (string) $items);
        $this->merge(['technologies' => array_values(array_filter(array_map(fn ($t) => trim((string) $t), $items), fn ($t) => $t !== ''))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('realisations', 'slug')->ignore($this->model())],
            'client_name' => ['nullable', 'string', 'max:150'],
            'sector' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1990', 'max:'.(now()->year + 1)],
            'summary' => ['required', 'string', 'max:300'],
            'context' => ['nullable', 'string', 'max:5000'],
            'need' => ['nullable', 'string', 'max:5000'],
            'solution' => ['nullable', 'string', 'max:20000'],
            'technologies' => ['nullable', 'array', 'max:30'],
            'technologies.*' => ['string', 'max:60'],
            'logiciel_id' => ['nullable', 'integer', Rule::exists('logiciels', 'id')->whereNull('deleted_at')],
            'cover_media_id' => ['nullable', 'integer', Rule::exists('medias', 'id')->whereNull('deleted_at')],
            'cover_upload' => ['nullable', ...MediaService::rules()],
            'gallery' => ['nullable', 'array', 'max:20'],
            'gallery.*' => ['integer', Rule::exists('medias', 'id')->whereNull('deleted_at')],
            'gallery_upload' => ['nullable', 'array', 'max:10'],
            'gallery_upload.*' => MediaService::rules(),
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'seo_media_id' => ['nullable', 'integer', Rule::exists('medias', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return MediaService::messages('cover_upload') + MediaService::messages('gallery_upload.*') + [
            'slug.regex' => 'L\'adresse ne contient que des minuscules sans accents, des chiffres et des tirets.',
            'slug.unique' => 'Cette adresse est déjà utilisée par une autre réalisation (éventuellement supprimée).',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'titre', 'slug' => 'adresse', 'client_name' => 'client', 'sector' => 'secteur', 'year' => 'année', 'summary' => 'résumé',
            'context' => 'contexte', 'need' => 'besoin', 'solution' => 'solution', 'technologies' => 'technologies', 'logiciel_id' => 'logiciel',
            'cover_media_id' => 'visuel principal', 'gallery' => 'visuels', 'sort_order' => 'ordre d\'affichage',
        ];
    }
}
