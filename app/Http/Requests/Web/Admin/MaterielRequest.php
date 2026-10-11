<?php

namespace App\Http\Requests\Web\Admin;

use App\Enums\Availability;
use App\Enums\CategoryType;
use App\Enums\MaterielCondition;
use App\Models\Materiel;
use App\Services\Medias\MediaService;
use Illuminate\Validation\Rule;

/**
 * Fiche matériel (cahier §5.4) : désignation et catégorie obligatoires, marque et modèle s'ils sont connus,
 * caractéristiques (« Libellé : valeur », une par ligne), état, prix facultatif en FCFA, disponibilité, photos, SEO.
 */
class MaterielRequest extends ContentRequest
{
    protected function routeKey(): string
    {
        return 'materiel';
    }

    protected function modelClass(): string
    {
        return Materiel::class;
    }

    protected function nullableFields(): array
    {
        return ['slug', 'reference', 'brand', 'model', 'summary', 'description', 'price', 'promo_price', 'promo_ends_on', 'price_conditions', 'main_media_id', 'seo_title', 'seo_description', 'seo_media_id'];
    }

    protected function booleanFields(): array
    {
        return ['is_published', 'is_featured'];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Caractéristiques : lignes « libellé / valeur » ; un texte « Libellé : valeur » (une par ligne) est converti.
        $specs = $this->input('specs');
        if (! is_array($specs)) {
            $specs = array_map(fn ($line) => str_contains($line, ':') ? array_combine(['label', 'value'], array_map('trim', explode(':', $line, 2))) : ['label' => trim($line), 'value' => ''],
                array_filter(preg_split('/\R/', (string) $specs), fn ($l) => trim($l) !== ''));
        }
        $specs = array_values(array_filter(array_map(fn ($row) => ['label' => trim((string) ($row['label'] ?? '')), 'value' => trim((string) ($row['value'] ?? ''))], $specs), fn ($row) => $row['label'] !== '' || $row['value'] !== ''));
        $this->merge(['specs' => $specs]);

        if (is_string($this->input('reference'))) {
            $this->merge(['reference' => mb_strtoupper(trim($this->input('reference'))) ?: null]);
        }

        // Saisie « 250 000 » ou « 250.000 » acceptée comme 250000.
        foreach (['price', 'promo_price'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => preg_replace('/[\s.\x{00A0}\x{202F}]/u', '', $this->input($field)) ?: null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $materiel = $this->model();

        return [
            'designation' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('materiels', 'slug')->ignore($materiel)],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('type', CategoryType::Materiel->value)->whereNull('deleted_at')
                ->where(fn ($q) => $q->where('is_active', true)->when($materiel?->category_id, fn ($q, $id) => $q->orWhere('id', $id)))],
            'reference' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9._\/-]+$/', Rule::unique('materiels', 'reference')->ignore($materiel)],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:150'],
            'summary' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:30000'],
            'specs' => ['nullable', 'array', 'max:40'],
            'specs.*.label' => ['required', 'string', 'max:100'],
            'specs.*.value' => ['required', 'string', 'max:250'],
            'condition' => ['required', Rule::enum(MaterielCondition::class)],
            'price' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'promo_price' => ['nullable', 'integer', 'min:0', 'required_with:promo_ends_on', function ($attribute, $value, $fail) {
                $price = $this->input('price');
                if ($price === null || $price === '') {
                    $fail('Indiquez d\'abord le prix normal : le prix promotionnel s\'affiche à côté du prix barré.');
                } elseif (is_numeric($price) && (int) $value >= (int) $price) {
                    $fail('Le prix promotionnel doit être inférieur au prix normal.');
                }
            }],
            'promo_ends_on' => ['nullable', 'date'],
            'price_conditions' => ['nullable', 'string', 'max:200'],
            'availability' => ['required', Rule::enum(Availability::class)],
            'main_media_id' => ['nullable', 'integer', Rule::exists('medias', 'id')->whereNull('deleted_at')],
            'main_upload' => ['nullable', ...MediaService::rules()],
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
        return MediaService::messages('main_upload') + MediaService::messages('gallery_upload.*') + [
            'slug.regex' => 'L\'adresse ne contient que des minuscules sans accents, des chiffres et des tirets.',
            'slug.unique' => 'Cette adresse est déjà utilisée par un autre matériel (éventuellement supprimé).',
            'category_id.exists' => 'Choisissez une catégorie de matériel active.',
            'specs.*.label.required' => 'Indiquez le libellé (ex. « Mémoire »).',
            'specs.*.value.required' => 'Indiquez la valeur (ex. « 8 Go »).',
            'reference.regex' => 'La référence ne contient que des lettres, des chiffres, et les signes - _ . /.',
            'reference.unique' => 'Cette référence est déjà utilisée par un autre matériel.',
            'price.integer' => 'Le prix s\'écrit en chiffres, en FCFA, sans décimales (ex. 250 000).',
            'promo_price.integer' => 'Le prix promotionnel s\'écrit en chiffres, en FCFA, sans décimales (ex. 225 000).',
        ];
    }

    public function attributes(): array
    {
        return [
            'designation' => 'désignation', 'reference' => 'référence', 'slug' => 'adresse', 'category_id' => 'catégorie', 'brand' => 'marque', 'model' => 'modèle',
            'summary' => 'résumé', 'description' => 'description', 'specs' => 'caractéristiques', 'condition' => 'état', 'price' => 'prix', 'promo_price' => 'prix promotionnel', 'promo_ends_on' => 'fin de la promotion',
            'price_conditions' => 'conditions du prix', 'availability' => 'disponibilité', 'main_media_id' => 'photo principale',
            'gallery' => 'photos', 'sort_order' => 'ordre d\'affichage', 'seo_title' => 'titre SEO', 'seo_description' => 'méta-description',
        ];
    }
}
