<?php

namespace App\Http\Requests\Web\Admin;

use App\Enums\CategoryType;
use App\Models\Article;
use App\Services\Medias\MediaService;
use Illuminate\Validation\Rule;

/**
 * Article (cahier §4) : titre, catégorie et texte obligatoires, extrait, image de couverture, date de publication
 * (future = programmé), SEO.
 */
class ArticleRequest extends ContentRequest
{
    protected function routeKey(): string
    {
        return 'article';
    }

    protected function modelClass(): string
    {
        return Article::class;
    }

    protected function nullableFields(): array
    {
        return ['slug', 'excerpt', 'cover_media_id', 'published_at', 'seo_title', 'seo_description', 'seo_media_id'];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $article = $this->model();

        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('articles', 'slug')->ignore($article)],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'body' => ['required', 'string', 'max:100000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('type', CategoryType::Article->value)->whereNull('deleted_at')
                ->where(fn ($q) => $q->where('is_active', true)->when($article?->category_id, fn ($q, $id) => $q->orWhere('id', $id)))],
            'cover_media_id' => ['nullable', 'integer', Rule::exists('medias', 'id')->whereNull('deleted_at')],
            'cover_upload' => ['nullable', ...MediaService::rules()],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:170'],
            'seo_media_id' => ['nullable', 'integer', Rule::exists('medias', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return MediaService::messages('cover_upload') + [
            'slug.regex' => 'L\'adresse ne contient que des minuscules sans accents, des chiffres et des tirets.',
            'slug.unique' => 'Cette adresse est déjà utilisée par un autre article (éventuellement supprimé).',
            'category_id.exists' => 'Choisissez une catégorie d\'actualités active.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'titre', 'slug' => 'adresse', 'excerpt' => 'extrait', 'body' => 'texte', 'category_id' => 'catégorie',
            'cover_media_id' => 'image de couverture', 'published_at' => 'date de publication',
        ];
    }
}
