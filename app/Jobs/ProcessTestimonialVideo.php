<?php

namespace App\Jobs;

use App\Models\Temoignage;
use App\Services\Videos\TestimonialVideoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Conversion d'une vidéo de témoignage en arrière-plan (peut durer plusieurs minutes). */
class ProcessTestimonialVideo implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1900;

    public int $tries = 1;

    public function __construct(public readonly int $temoignageId)
    {
        $this->onQueue('videos');
    }

    public function handle(TestimonialVideoService $service): void
    {
        $temoignage = Temoignage::find($this->temoignageId);
        if ($temoignage) {
            $service->process($temoignage);
        }
    }
}
