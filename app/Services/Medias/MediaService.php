<?php

namespace App\Services\Medias;

use App\Models\Media;
use App\Models\User;
use GdImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Enregistrement des images (cahier §9 « stockage organisé, noms sûrs, validation serveur et optimisation des images »,
 * §10 « type réel, taille, extension autorisée et nom de stockage généré »). Le fichier envoyé n'est jamais conservé
 * tel quel : il est décodé par GD puis ré-encodé (ce qui retire les métadonnées EXIF et neutralise un fichier piégé),
 * redimensionné à `max_width` et accompagné d'une vignette.
 */
class MediaService
{
    /**
     * Règles de validation d'une image envoyée (FormRequest et formulaires de contenu).
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        $extensions = implode(',', array_unique([...array_values(config('medias.mimes')), 'jpeg']));
        $max = config('medias.max_dimension');

        return [
            'file', 'mimetypes:'.implode(',', array_keys(config('medias.mimes'))), "extensions:{$extensions}",
            'max:'.config('medias.max_kilobytes'), "dimensions:max_width={$max},max_height={$max}",
        ];
    }

    /** Messages en français de ces règles, pour un champ donné (« fichier », « images.* »). @return array<string, string> */
    public static function messages(string $field): array
    {
        return [
            "{$field}.mimetypes" => 'Seules les images JPEG, PNG ou WebP sont acceptées.',
            "{$field}.extensions" => 'Seules les images .jpg, .png ou .webp sont acceptées.',
            "{$field}.max" => 'L\'image dépasse la taille maximale de '.(int) (config('medias.max_kilobytes') / 1024).' Mo.',
            "{$field}.dimensions" => 'L\'image est trop grande (plus de '.config('medias.max_dimension').' pixels de côté).',
            "{$field}.uploaded" => 'L\'envoi de l\'image a échoué (fichier trop lourd pour le serveur ?).',
        ];
    }

    /**
     * Crée une image à partir d'un fichier déjà validé par les règles ci-dessus.
     *
     * @throws ValidationException si l'image ne peut pas être décodée (fichier corrompu ou déguisé)
     */
    public function store(UploadedFile $file, ?User $uploader, ?string $alt = null, ?string $folder = null): Media
    {
        $mime = (string) $file->getMimeType();
        $extension = config('medias.mimes')[$mime] ?? throw ValidationException::withMessages(['fichier' => 'Type d\'image non accepté.']);

        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $image instanceof GdImage) {
            throw ValidationException::withMessages(['fichier' => 'Le fichier « '.$file->getClientOriginalName().' » n\'est pas une image lisible.']);
        }
        $image = $this->orient($image, $file, $mime);

        $directory = 'medias/'.now()->format('Y/m');
        $name = Str::uuid()->toString();
        $path = "{$directory}/{$name}.{$extension}";
        $thumbPath = "{$directory}/{$name}-thumb.{$extension}";

        $main = $this->resize($image, (int) config('medias.max_width'));
        $thumb = $this->resize($image, (int) config('medias.thumb_width'));

        try {
            Storage::disk('public')->put($path, $this->encode($main, $mime));
            Storage::disk('public')->put($thumbPath, $this->encode($thumb, $mime));

            return Media::create([
                'path' => $path,
                'thumb_path' => $thumbPath,
                'original_name' => Str::limit($this->safeName($file->getClientOriginalName()), 250, ''),
                'mime' => $mime,
                'size' => Storage::disk('public')->size($path),
                'width' => imagesx($main),
                'height' => imagesy($main),
                'alt' => $alt !== null && trim($alt) !== '' ? trim($alt) : null,
                'folder' => $folder !== null && trim($folder) !== '' ? trim($folder) : null,
                'uploaded_by' => $uploader?->id,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete([$path, $thumbPath]);

            throw $e;
        }
    }

    /**
     * Enregistre plusieurs images envoyées depuis un formulaire de contenu.
     *
     * @param  array<int, UploadedFile>|null  $files
     * @return list<int> identifiants des images créées
     */
    public function storeMany(?array $files, ?User $uploader, ?string $folder = null, ?string $alt = null): array
    {
        return array_values(array_map(fn (UploadedFile $file) => $this->store($file, $uploader, $alt, $folder)->id, array_filter($files ?? [])));
    }

    /**
     * Remplace la galerie d'un contenu par ces images, dans cet ordre (table `mediables`).
     *
     * @param  list<int|string>  $mediaIds
     */
    public function syncGallery(Model $owner, array $mediaIds): void
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($mediaIds))));
        $existing = Media::whereIn('id', $ids)->pluck('id')->all();
        $ids = array_values(array_filter($ids, fn ($id) => in_array($id, $existing, true)));

        DB::transaction(function () use ($owner, $ids) {
            DB::table('mediables')->where('mediable_type', $owner->getMorphClass())->where('mediable_id', $owner->getKey())
                ->whereNotIn('media_id', $ids ?: [0])->delete();

            foreach ($ids as $position => $id) {
                DB::table('mediables')->updateOrInsert(
                    ['media_id' => $id, 'mediable_type' => $owner->getMorphClass(), 'mediable_id' => $owner->getKey()],
                    ['position' => $position],
                );
            }
        });
    }

    /** Supprime une image inutilisée (soft delete ; les fichiers restent jusqu'à la purge). */
    public function delete(Media $media): void
    {
        if ($media->usageCount() > 0) {
            throw new RuntimeException('Cette image est utilisée par un contenu : retirez-la du contenu avant de la supprimer.');
        }

        $media->delete();
    }

    /** Remet droite une photo de téléphone (orientation EXIF), avant que le ré-encodage n'efface cette information. */
    private function orient(GdImage $image, UploadedFile $file, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        return $angle !== 0 ? (imagerotate($image, $angle, 0) ?: $image) : $image;
    }

    private function resize(GdImage $image, int $maxWidth): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxWidth) {
            return $image;
        }

        $newHeight = (int) max(1, round($height * $maxWidth / $width));
        $resized = imagecreatetruecolor($maxWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);

        return $resized;
    }

    private function encode(GdImage $image, string $mime): string
    {
        ob_start();
        $quality = (int) config('medias.quality');
        match ($mime) {
            'image/png' => (function () use ($image) {
                imagesavealpha($image, true);
                imagepng($image, null, 8);
            })(),
            'image/webp' => imagewebp($image, null, $quality),
            default => (function () use ($image, $quality) {
                imageinterlace($image, true); // JPEG progressif : affichage plus rapide (cahier §11.2)
                imagejpeg($image, null, $quality);
            })(),
        };

        return (string) ob_get_clean();
    }

    private function safeName(string $name): string
    {
        $base = Str::slug(pathinfo($name, PATHINFO_FILENAME)) ?: 'image';

        return $base.'.'.strtolower(pathinfo($name, PATHINFO_EXTENSION) ?: 'jpg');
    }
}
