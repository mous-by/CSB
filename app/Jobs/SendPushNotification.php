<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Services\Push\WebPush;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envoi d'une notification push à un appareil. Abonnement expiré (404 / 410) : supprimé. Autre échec : journalisé
 * dans le journal de l'application, sans rien bloquer.
 */
class SendPushNotification implements ShouldQueue
{
    use Queueable;

    /** @param array{title: string, body: string, url: string, tag: ?string} $payload */
    public function __construct(public readonly int $subscriptionId, public readonly array $payload) {}

    public function handle(WebPush $push): void
    {
        $subscription = PushSubscription::find($this->subscriptionId);
        if (! $subscription) {
            return;
        }

        try {
            $response = $push->send($subscription, $this->payload);
        } catch (Throwable $e) {
            Log::warning("Notification push non envoyée (abonnement {$subscription->id}) : ".$e->getMessage());

            return;
        }

        if (in_array($response->status(), [404, 410], true)) {
            $subscription->delete();
        } elseif ($response->successful()) {
            $subscription->forceFill(['last_used_at' => now()])->save();
        } else {
            Log::warning("Notification push refusée (abonnement {$subscription->id}) : HTTP {$response->status()} ".mb_substr($response->body(), 0, 200));
        }
    }
}
