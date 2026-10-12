<?php

namespace App\Services\Videos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Envoi d'une vidéo par morceaux de 1 Mo (le serveur limite chaque envoi à quelques Mo ; une vidéo de téléphone en fait
 * des dizaines) : chaque morceau est ajouté au fichier en cours, dans l'ordre ; au dernier, le type réel est vérifié et
 * un jeton chiffré est rendu. Le formulaire envoie ce jeton, et la vidéo n'est rattachée qu'à son enregistrement.
 * Les envois abandonnés sont supprimés chaque jour (`videos:clean`).
 */
class ChunkedVideoUpload
{
    public const CHUNK_SIZE = 1024 * 1024;

    public const MAX_BYTES = 300 * 1024 * 1024;

    public const DIRECTORY = 'video-uploads';

    /** Types acceptés : MP4 (Android, iPhone récent), MOV (iPhone), WebM, 3GP (anciens téléphones). */
    public const MIMES = [
        'video/mp4' => 'mp4', 'video/quicktime' => 'mov', 'video/webm' => 'webm', 'video/3gpp' => '3gp', 'video/x-matroska' => 'mkv', 'video/x-m4v' => 'm4v',
    ];

    /** Ajoute un morceau ; renvoie le jeton au dernier, null sinon. */
    public function receive(string $uploadId, int $index, int $total, UploadedFile $chunk): ?string
    {
        if (! Str::isUuid($uploadId) || $index < 0 || $total < 1 || $index >= $total || $total > intdiv(self::MAX_BYTES, self::CHUNK_SIZE) + 1) {
            throw ValidationException::withMessages(['video' => 'Envoi de la vidéo invalide.']);
        }

        $disk = Storage::disk('local');
        $path = self::DIRECTORY."/{$uploadId}.part";
        $absolute = $disk->path($path);
        $disk->makeDirectory(self::DIRECTORY);

        // Les morceaux arrivent dans l'ordre : le n-ième commence là où le fichier s'arrête.
        $current = is_file($absolute) ? filesize($absolute) : 0;
        if ($index === 0 && $current > 0) {
            @unlink($absolute);
            $current = 0;
        }
        if ($current !== $index * self::CHUNK_SIZE) {
            throw ValidationException::withMessages(['video' => 'Morceau de vidéo reçu dans le désordre : recommencez l\'envoi.']);
        }
        if ($current + $chunk->getSize() > self::MAX_BYTES) {
            @unlink($absolute);
            throw ValidationException::withMessages(['video' => 'Vidéo trop lourde : 300 Mo au plus.']);
        }

        file_put_contents($absolute, file_get_contents($chunk->getRealPath()), FILE_APPEND | LOCK_EX);

        if ($index < $total - 1) {
            return null;
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($absolute);
        $extension = self::MIMES[$mime] ?? null;
        if ($extension === null) {
            @unlink($absolute);
            throw ValidationException::withMessages(['video' => 'Ce fichier n\'est pas une vidéo acceptée (MP4, MOV, WebM, 3GP).']);
        }
        rename($absolute, $disk->path(self::DIRECTORY."/{$uploadId}.{$extension}"));

        return Crypt::encryptString($uploadId.'.'.$extension);
    }

    /** Chemin (disque local) de la vidéo désignée par un jeton, ou null si le jeton est invalide ou le fichier absent. */
    public function resolve(?string $token): ?string
    {
        if (! $token) {
            return null;
        }
        try {
            $name = Crypt::decryptString($token);
        } catch (Throwable) {
            return null;
        }
        if (! preg_match('/^[0-9a-f-]{36}\.[a-z0-9]{2,5}$/', $name)) {
            return null;
        }
        $path = self::DIRECTORY.'/'.$name;

        return Storage::disk('local')->exists($path) ? $path : null;
    }

    /** Supprime les envois de plus de $hours heures jamais rattachés. */
    public function clean(int $hours = 24): int
    {
        $count = 0;
        foreach (Storage::disk('local')->files(self::DIRECTORY) as $file) {
            if (Storage::disk('local')->lastModified($file) < now()->subHours($hours)->getTimestamp()) {
                Storage::disk('local')->delete($file);
                $count++;
            }
        }

        return $count;
    }
}
