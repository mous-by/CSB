<?php

namespace App\Http\Requests\Web\Admin;

use App\Enums\CategoryType;
use App\Enums\LogicielStatus;
use App\Enums\Platform;
use App\Models\Logiciel;
use App\Services\Medias\MediaService;
use Illuminate\Validation\Rule;

/**
 * Fiche logiciel (cahier §5.2) : nom et résumé obligatoires, catégorie principale active, catégories secondaires,
 * fonctionnalités (une par ligne), plateformes réellement disponibles, statut contrôlé, tarif facultatif, visuels, SEO.
 */
class LogicielRequest extends ContentRequest
{
    protected function routeKey(): string
    {
        return 'logiciel';
    }

    protected function modelClass(): string
    {
        return Logiciel::class;
    }

    protected function nullableFields(): array
    {
        return ['slug', 'tagline', 'problem', 'description', 'price_text', 'video_url', 'logo_media_id', 'seo_title', 'seo_description', 'seo_media_id'];
    }

    protected function booleanFields(): array
    {
        return ['is_published', 'is_featured'];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Fonctionnalités : liste de lignes (« ajouter une ligne ») ; un texte collé est découpé ligne par ligne.
        $features = $this->input('features');
        $features = is_array($features) ? $features : preg_split('/\R/', (string) $features);
        $this->merge(['features' => array_values(array_filter(array_map(fn ($f) => trim(ltrim(trim((string) $f), '-•*')), $features), fn ($f) => $f !== ''))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $logiciel = $this->model();

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('logiciels', 'slug')->ignore($logiciel)],
            'summary' => ['required', 'string', 'max:300'],
            'problem' => ['nullable', 'string', 'max:3000'],
            'description' => ['nullable', 'string', 'max:30000'],
            'category_id' => ['required', 'integer', $this->categoryRule($logiciel?->category_id)],
            'secondary_categories' => ['nullable', 'array', 'max:10'],
            'secondary_categories.*' => ['integer', 'different:category_id', Rule::exists('categories', 'id')->where('type', CategoryType::Logiciel->value)->whereNull('deleted_at')],
            'tagline' => ['nullable', 'string', 'max:120'],
            'features' => ['nullable', 'array', 'max:60'],
            'features.*' => ['string', 'max:300'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => [Rule::enum(Platform::class)],
            'status' => ['required', Rule::enum(LogicielStatus::class)],
            'price_text' => ['nullable', 'string', 'max:150'],
            'video_url' => ['nullable', 'url:http,https', 'max:500'],
            'logo_media_id' => ['nullable', 'integer', Rule::exists('medias', 'id')->whereNull('deleted_at')],
            'logo_upload' => ['nullable', ...MediaService::rules()],
            'gallery' => ['nullable', 'array', 'max:20'],
            'gallery.*' => ['integer', Rule::exists('medias', 'id')->whereNull('deleted_at')],
            'gallery_upload' => ['nullable', 'array', 'max:10'],
            'gallery_upload.*' => MediaService::rules(),
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'seo_media_id' => ['nullable', 'integer', Rule::exists('medias', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return MediaService::messages('logo_upload') + MediaService::messages('gallery_upload.*') + [
            'slug.regex' => 'L\'adresse ne contient que des minuscules sans accents, des chiffres et des tirets (ex. kalannet).',
            'slug.unique' => 'Cette adresse est déjà utilisée par un autre logiciel (éventuellement supprimé).',
            'category_id.exists' => 'Choisissez une catégorie de logiciels active.',
            'secondary_categories.*.different' => 'Une catégorie secondaire ne peut pas être la catégorie principale.',
            'video_url.url' => 'Le lien de la vidéo doit être une adresse web complète (https://…).',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom', 'tagline' => 'accroche', 'slug' => 'adresse', 'summary' => 'résumé', 'problem' => 'problème traité', 'description' => 'description',
            'category_id' => 'catégorie principale', 'secondary_categories' => 'catégories secondaires', 'features' => 'fonctionnalités',
            'platforms' => 'plateformes', 'status' => 'statut', 'price_text' => 'tarif', 'video_url' => 'vidéo', 'logo_media_id' => 'logo',
            'gallery' => 'captures', 'sort_order' => 'ordre d\'affichage', 'seo_title' => 'titre SEO', 'seo_description' => 'méta-description',
            'seo_media_id' => 'image de partage',
        ];
    }

    /** Catégorie de logiciels active, ou celle que la fiche porte déjà (une catégorie désactivée reste valable). */
    private function categoryRule(?int $current): mixed
    {
        return Rule::exists('categories', 'id')->where('type', CategoryType::Logiciel->value)->whereNull('deleted_at')
            ->where(fn ($query) => $query->where('is_active', true)->when($current, fn ($q) => $q->orWhere('id', $current)));
    }
}
