<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\Temoignage;
use App\Services\Medias\MediaService;
use App\Services\Videos\ChunkedVideoUpload;
use Illuminate\Validation\Validator;

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
        return ['author_title', 'organization', 'content', 'video_url', 'video_token', 'rating'];
    }

    protected function booleanFields(): array
    {
        return ['is_published', 'is_featured', 'remove_video', 'remove_photo'];
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
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'content' => ['nullable', 'string', 'max:1500'],
            'video_url' => ['nullable', 'url:http,https', 'max:500'],
            'video_token' => ['nullable', 'string'],
            'remove_video' => ['boolean'],
            'photo_upload' => ['nullable', ...MediaService::rules()],
            'remove_photo' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
        ];
    }

    /** Un témoignage a un texte ou une vidéo (envoyée, déjà présente ou en lien). */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $model = $this->model();
                $hasVideo = app(ChunkedVideoUpload::class)->resolve($this->input('video_token')) !== null
                    || filled($this->input('video_url'))
                    || ($model?->video_path && ! $this->boolean('remove_video'));
                if (! $hasVideo && blank($this->input('content'))) {
                    $validator->errors()->add('content', 'Écrivez le témoignage ou ajoutez une vidéo.');
                }
                if (filled($this->input('video_token')) && app(ChunkedVideoUpload::class)->resolve($this->input('video_token')) === null) {
                    $validator->errors()->add('video_token', 'La vidéo envoyée est introuvable : envoyez-la de nouveau.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return MediaService::messages('photo_upload') + ['video_url.url' => 'Le lien de la vidéo doit commencer par https:// (YouTube, Facebook, TikTok…).'];
    }

    public function attributes(): array
    {
        return [
            'author_name' => 'nom de l\'auteur', 'author_title' => 'fonction', 'organization' => 'organisation', 'content' => 'témoignage',
            'sort_order' => 'ordre d\'affichage', 'rating' => 'note', 'video_url' => 'lien de la vidéo', 'photo_upload' => 'photo',
        ];
    }
}
