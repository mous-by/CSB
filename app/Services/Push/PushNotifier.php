<?php

namespace App\Services\Push;

use App\Jobs\SendPushNotification;
use App\Models\PushSubscription;

/**
 * Notifications push à l'équipe (nouveau ticket, réponse d'un demandeur…) : chaque appareil abonné d'un membre actif
 * qui a la permission reçoit la notification, même site fermé. Envoyée après la réponse HTTP : le demandeur n'attend
 * jamais le service push, et un échec n'empêche rien (SendPushNotification le journalise).
 */
class PushNotifier
{
    public function __construct(private readonly WebPush $push) {}

    public function notifyTeam(string $permission, string $title, string $body, string $url, ?string $tag = null): int
    {
        if (! $this->push->configured()) {
            return 0;
        }

        $subscriptions = PushSubscription::with('user')->whereHas('user', fn ($q) => $q->active())->get()
            ->filter(fn (PushSubscription $s) => $s->user->can($permission));

        foreach ($subscriptions as $subscription) {
            SendPushNotification::dispatchAfterResponse($subscription->id, ['title' => $title, 'body' => $body, 'url' => $url, 'tag' => $tag]);
        }

        return $subscriptions->count();
    }
}
