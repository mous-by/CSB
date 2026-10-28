<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\Push\WebPush;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Abonnement d'un appareil aux notifications push (bouton « Notifications sur cet appareil »), désabonnement et
 * notification d'essai. Un appareil appartient au dernier membre de l'équipe qui l'a abonné.
 */
class PushController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:2000'],
            'keys.p256dh' => ['required', 'string', 'max:120'],
            'keys.auth' => ['required', 'string', 'max:40'],
        ]);

        PushSubscription::updateOrCreate(['endpoint_hash' => PushSubscription::hashEndpoint($data['endpoint'])], [
            'user_id' => $request->user()->id, 'endpoint' => $data['endpoint'], 'public_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'], 'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        return response()->json(['message' => 'Notifications activées sur cet appareil.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => ['required', 'string', 'max:2000']]);
        PushSubscription::where('user_id', $request->user()->id)->where('endpoint_hash', PushSubscription::hashEndpoint($request->input('endpoint')))->delete();

        return response()->json(['message' => 'Notifications désactivées sur cet appareil.']);
    }

    public function test(Request $request, WebPush $push): JsonResponse
    {
        $subscription = PushSubscription::where('user_id', $request->user()->id)->where('endpoint_hash', PushSubscription::hashEndpoint((string) $request->input('endpoint')))->first();
        abort_unless($subscription && $push->configured(), 404);

        $response = $push->send($subscription, ['title' => 'CSB — essai', 'body' => 'Les notifications fonctionnent sur cet appareil.', 'url' => route('tickets.index'), 'tag' => 'essai']);

        return response()->json(['message' => $response->successful() ? 'Notification d\'essai envoyée.' : "Le service push a refusé l'envoi (HTTP {$response->status()})."], $response->successful() ? 200 : 502);
    }
}
