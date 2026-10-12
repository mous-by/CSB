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
            $playable = in_array(pathinfo($source, PATHINFO_EXTENSION), ['mp4', 'webm', 'm4v'], true);
            $temoignage->forceFill([
                'video_status' => $playable ? Temoignage::VIDEO_READY : Temoignage::VIDEO_FAILED,
                'video_error' => $playable ? null : 'Format non lisible par tous les navigateurs et ffmpeg absent du serveur : envoyez un MP4.',
            ])->saveQuietly();

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

    private function deleteFiles(Temoignage $temoignage): void
    {
        foreach ([$temoignage->video_path, $temoignage->video_poster_path] as $path) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
