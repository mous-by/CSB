<?php

namespace App\Jobs;

use App\Services\Audit\ActivityLogger;
use App\Services\WhatsApp\WhatsAppNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

/** Envoi d'une alerte WhatsApp (3 essais) ; l'échec définitif est journalisé dans l'audit (`whatsapp.failed`). */
class SendWhatsAppAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly string $to, public readonly string $text, public readonly string $event) {}

    public function handle(WhatsAppNotifier $notifier): void
    {
        $response = $notifier->send($this->to, $this->text);
        $response->throw();
    }

    public function failed(?Throwable $e): void
    {
        $masked = Str::mask($this->to, '*', 3, max(0, strlen($this->to) - 5));
        app(ActivityLogger::class)->log('whatsapp.failed', 'whatsapp', "Alerte WhatsApp non envoyée ({$this->event}) au {$masked}", new: ['error' => Str::limit((string) $e?->getMessage(), 300)], actor: null);
    }
}
