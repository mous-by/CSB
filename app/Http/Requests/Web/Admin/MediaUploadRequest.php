<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\Media;
use App\Services\Medias\MediaService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Téléversement d'une ou plusieurs images (cahier §10, REC-09) : type réel, extension, taille, dimensions.
 */
class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Media::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fichiers' => ['required', 'array', 'min:1', 'max:20'],
            'fichiers.*' => ['required', ...MediaService::rules()],
            'alt' => ['nullable', 'string', 'max:255'],
            'folder' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return MediaService::messages('fichiers.*') + [
            'fichiers.required' => 'Choisissez au moins une image.',
            'fichiers.max' => 'Vingt images au plus par envoi.',
        ];
    }

    public function attributes(): array
    {
        return ['fichiers' => 'images', 'fichiers.*' => 'image', 'alt' => 'texte alternatif', 'folder' => 'dossier'];
    }
}
