<?php

namespace App\Services\Videos;

use App\Jobs\ProcessTestimonialVideo;
use App\Models\Temoignage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Vidéo d'un témoignage : rattachement d'une vidéo envoyée (déplacée sur le disque public), conversion en arrière-plan
 * par ffmpeg (MP4 H.264 720p lisible par tous les navigateurs, image d'aperçu, durée), retrait.
 * Sans ffmpeg sur le serveur, la vidéo d'origine est publiée telle quelle (MP4 ou WebM conseillé), sans aperçu.
 */
class TestimonialVideoService
{
    public const DIRECTORY = 'temoignages/videos';

    public function attach(Temoignage $temoignage, string $localPath): void
    {
        $extension = pathinfo($localPath, PATHINFO_EXTENSION);
        $target = self::DIRECTORY.'/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->makeDirectory(self::DIRECTORY);
        rename(Storage::disk('local')->path($localPath), Storage::disk('public')->path($target));

        $this->deleteFiles($temoignage);
        Temoignage::withoutActivityLog(fn () => $temoignage->update([
            'video_path' => $target, 'video_poster_path' => null, 'video_duration' => null, 'video_status' => Temoignage::VIDEO_PROCESSING, 'video_error' => null,
        ]));

        // Sans ffmpeg (hébergement mutualisé), il n'y a rien à convertir : la vidéo est prête (MP4, WebM) ou refusée
        // tout de suite, sans dépendre de la file d'attente (cron) qui peut ne pas tourner.
        if (! $this->ffmpegAvailable()) {
            $this->process($temoignage);

            return;
        }

        ProcessTestimonialVideo::dispatch($temoignage->id);
    }

    public function remove(Temoignage $temoignage): void
    {
        $this->deleteFiles($temoignage);
        $temoignage->update(['video_path' => null, 'video_poster_path' => null, 'video_duration' => null, 'video_status' => null, 'video_error' => null]);
    }

    public function ffmpegAvailable(): bool
    {
        try {
            return Process::timeout(10)->run([config('services.ffmpeg.ffmpeg'), '-version'])->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /** Conversion (appelée par la file d'attente). */
    public function process(Temoignage $temoignage): void
    {
        if (! $temoignage->video_path) {
            return;
        }
        $disk = Storage::disk('public');
        $source = $disk->path($temoignage->video_path);

        if (! $this->ffmpegAvailable()) {
            $this->publishWithoutConversion($temoignage, $source);

            return;
        }

        $base = self::DIRECTORY.'/'.Str::uuid();
        $mp4 = $base.'.mp4';
        $poster = $base.'.jpg';

        $convert = Process::timeout(1800)->run([
            config('services.ffmpeg.ffmpeg'), '-y', '-i', $source,
            '-vf', "scale='min(1280,iw)':-2", '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '26', '-pix_fmt', 'yuv420p',
            '-c:a', 'aac', '-b:a', '128k', '-movflags', '+faststart', $disk->path($mp4),
        ]);
        if (! $convert->successful()) {
            $temoignage->forceFill(['video_status' => Temoignage::VIDEO_FAILED, 'video_error' => Str::limit(trim($convert->errorOutput()) ?: 'Conversion impossible.', 480)])->saveQuietly();

            return;
        }

        Process::timeout(120)->run([config('services.ffmpeg.ffmpeg'), '-y', '-ss', '1', '-i', $disk->path($mp4), '-frames:v', '1', '-vf', 'scale=720:-2', '-q:v', '4', $disk->path($poster)]);
        $probe = Process::timeout(60)->run([config('services.ffmpeg.ffprobe'), '-v', 'error', '-show_entries', 'format=duration', '-of', 'default=nw=1:nk=1', $disk->path($mp4)]);

        if ($source !== $disk->path($mp4)) {
            @unlink($source);
        }
        $temoignage->forceFill([
            'video_path' => $mp4,
            'video_poster_path' => $disk->exists($poster) ? $poster : null,
            'video_duration' => $probe->successful() && is_numeric(trim($probe->output())) ? (int) round((float) trim($probe->output())) : null,
            'video_status' => Temoignage::VIDEO_READY, 'video_error' => null,
        ])->saveQuietly();
    }

    /**
     * Sans ffmpeg (hébergement mutualisé) : rien n'est converti. Un MP4 ou un QuickTime (.mov, .3gp) en H.264 est lu par
     * tous les navigateurs — un .mov est alors simplement renommé en .mp4, c'est le même conteneur ; un WebM aussi.
     * Le HEVC (iPhone réglé sur « Haute efficacité ») et les autres formats sont refusés avec une explication.
     */
    private function publishWithoutConversion(Temoignage $temoignage, string $source): void
    {
        $disk = Storage::disk('public');
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $codec = in_array($extension, ['mp4', 'm4v', 'mov', '3gp'], true) ? $this->videoCodec($source) : null;
        $playable = $extension === 'webm' || $codec === 'h264' || ($codec === null && in_array($extension, ['mp4', 'm4v'], true));

        if (! $playable) {
            $temoignage->forceFill(['video_status' => Temoignage::VIDEO_FAILED, 'video_error' => $codec === 'hevc'
                ? 'Vidéo en HEVC (iPhone réglé sur « Haute efficacité ») : tous les navigateurs ne la lisent pas, et ffmpeg, qui la convertirait, est absent du serveur. Téléchargez-la, convertissez-la en MP4 (H.264), puis remplacez-la ; ou réglez l\'iPhone sur « Le plus compatible ».'
                : 'Format non lisible par tous les navigateurs et ffmpeg absent du serveur : envoyez une vidéo MP4 (H.264).',
            ])->saveQuietly();

            return;
        }

        $path = $temoignage->video_path;
        if (in_array($extension, ['mov', '3gp'], true)) {
            $path = preg_replace('/\.\w+$/', '.mp4', $path);
            $disk->move($temoignage->video_path, $path);
        }
        $temoignage->forceFill(['video_path' => $path, 'video_status' => Temoignage::VIDEO_READY, 'video_error' => null])->saveQuietly();
    }

    /**
     * Codec vidéo lu dans le fichier MP4 / QuickTime sans ffmpeg : type de l'entrée de la boîte « stsd » (avc1 = H.264,
     * hvc1 / hev1 = HEVC). Lecture par morceaux (fichiers jusqu'à 300 Mo). @return 'h264'|'hevc'|null
     */
    public function videoCodec(string $file): ?string
    {
        $handle = @fopen($file, 'rb');
        if (! $handle) {
            return null;
        }
        $found = null;
        $tail = '';
        while ($found !== 'h264' && ! feof($handle)) {
            $buffer = $tail.fread($handle, 1048576);
            $offset = 0;
            while (($at = strpos($buffer, 'stsd', $offset)) !== false) {
                // stsd : version/drapeaux (4), nombre d'entrées (4), puis taille (4) et type (4) de la première entrée.
                $type = substr($buffer, $at + 16, 4);
                if (strlen($type) < 4) {
                    break;
                }
                $found = match ($type) {
                    'avc1', 'avc3' => 'h264',
                    'hvc1', 'hev1' => $found ?? 'hevc',
                    default => $found,
                };
                $offset = $at + 4;
            }
            $tail = substr($buffer, -24);
        }
        fclose($handle);

        return $found;
    }

    private function deleteFiles(Temoignage $temoignage): void
    {
        foreach ([$temoignage->video_path, $temoignage->video_poster_path] as $path) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
