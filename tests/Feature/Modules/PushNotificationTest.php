<?php

namespace Tests\Feature\Modules;

use App\Jobs\SendPushNotification;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Push\WebPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Notifications push (Web Push) : abonnement d'un appareil, chiffrement RFC 8291 lisible par le navigateur, en-tête
 * VAPID, abonnement expiré supprimé.
 */
class PushNotificationTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $keys = WebPush::generateKeys();
        config(['services.webpush.public_key' => $keys['public'], 'services.webpush.private_key' => $keys['private'], 'services.webpush.subject' => 'mailto:test@example.com']);
    }

    /** Clé d'un faux navigateur. @return array{0: \OpenSSLAsymmetricKey, 1: string, 2: string} */
    private function browserKeys(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $ec = openssl_pkey_get_details($key)['ec'];

        return [$key, "\x04".str_pad($ec['x'], 32, "\0", STR_PAD_LEFT).str_pad($ec['y'], 32, "\0", STR_PAD_LEFT), random_bytes(16)];
    }

    public function test_a_team_member_subscribes_and_unsubscribes_a_device(): void
    {
        $this->syncPermissions();
        $agent = User::factory()->commercial()->create();
        $subscription = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc', 'keys' => ['p256dh' => 'cle', 'auth' => 'secret']];

        $this->actingAs($agent)->get('/admin/tickets')->assertOk()->assertSee('data-push-toggle', false);
        $this->postJson('/admin/notifications-push', $subscription)->assertOk();
        $this->postJson('/admin/notifications-push', $subscription)->assertOk();
        $this->assertSame(1, PushSubscription::where('user_id', $agent->id)->count());
        $this->postJson('/admin/notifications-push', ['endpoint' => 'http://insecure.example.com', 'keys' => ['p256dh' => 'a', 'auth' => 'b']])->assertUnprocessable();

        $this->deleteJson('/admin/notifications-push', ['endpoint' => $subscription['endpoint']])->assertOk();
        $this->assertSame(0, PushSubscription::count());

        $this->actingAs(User::factory()->gestionnaire()->create())->postJson('/admin/notifications-push', $subscription)->assertForbidden();
    }

    public function test_the_payload_is_encrypted_for_the_browser_and_signed_with_vapid(): void
    {
        [$browserKey, $browserPublic, $auth] = $this->browserKeys();
        $user = User::factory()->commercial()->create();
        $subscription = PushSubscription::create(['user_id' => $user->id, 'endpoint' => 'https://push.example.com/send/1', 'endpoint_hash' => PushSubscription::hashEndpoint('https://push.example.com/send/1'), 'public_key' => WebPush::b64($browserPublic), 'auth_token' => WebPush::b64($auth)]);
        Http::fake(['push.example.com/*' => Http::response('', 201)]);

        (new SendPushNotification($subscription->id, ['title' => 'Nouveau ticket TCK-2026-0001', 'body' => 'Écran noir', 'url' => '/admin/tickets/1', 'tag' => 'TCK-2026-0001']))->handle(app(WebPush::class));

        Http::assertSent(function ($request) use ($browserKey, $browserPublic, $auth) {
            $this->assertStringStartsWith('vapid t=', $request->header('Authorization')[0]);
            $this->assertSame('aes128gcm', $request->header('Content-Encoding')[0]);

            // Déchiffrement côté « navigateur » (RFC 8291).
            $body = $request->body();
            $salt = substr($body, 0, 16);
            $serverPublic = substr($body, 21, ord($body[20]));
            $cipher = substr($body, 21 + ord($body[20]));
            $pem = "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode(hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').$serverPublic), 64, "\n")."-----END PUBLIC KEY-----\n";
            $ikm = hash_hkdf('sha256', openssl_pkey_derive(openssl_pkey_get_public($pem), $browserKey), 32, "WebPush: info\0".$browserPublic.$serverPublic, $auth);
            $plain = openssl_decrypt(substr($cipher, 0, -16), 'aes-128-gcm', hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt), OPENSSL_RAW_DATA, hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt), substr($cipher, -16));

            return json_decode(rtrim((string) $plain, "\x02"), true)['body'] === 'Écran noir';
        });
        $this->assertNotNull($subscription->fresh()->last_used_at);
    }

    public function test_an_expired_subscription_is_removed(): void
    {
        [, $browserPublic, $auth] = $this->browserKeys();
        $user = User::factory()->commercial()->create();
        $subscription = PushSubscription::create(['user_id' => $user->id, 'endpoint' => 'https://push.example.com/send/2', 'endpoint_hash' => PushSubscription::hashEndpoint('https://push.example.com/send/2'), 'public_key' => WebPush::b64($browserPublic), 'auth_token' => WebPush::b64($auth)]);
        Http::fake(['push.example.com/*' => Http::response('', 410)]);

        (new SendPushNotification($subscription->id, ['title' => 'T', 'body' => 'B', 'url' => '/', 'tag' => null]))->handle(app(WebPush::class));

        $this->assertNull($subscription->fresh());
    }
}
