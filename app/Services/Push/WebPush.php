<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Envoi Web Push sans bibliothèque externe : message chiffré « aes128gcm » (RFC 8291 / 8188) et authentification VAPID
 * (RFC 8292, jeton ES256). Clés VAPID au format standard base64url (`php artisan push:keys`), dans `.env`.
 */
class WebPush
{
    /** En-tête DER d'une clé publique P-256 (SubjectPublicKeyInfo) avant les 65 octets du point. */
    private const P256_SPKI_PREFIX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    public function configured(): bool
    {
        return filled(config('services.webpush.public_key')) && filled(config('services.webpush.private_key'));
    }

    public function publicKey(): ?string
    {
        return config('services.webpush.public_key') ?: null;
    }

    /** Nouvelle paire de clés VAPID. @return array{public: string, private: string} */
    public static function generateKeys(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $ec = openssl_pkey_get_details($key)['ec'];

        return ['public' => self::b64(self::point($ec)), 'private' => self::b64(str_pad($ec['d'], 32, "\0", STR_PAD_LEFT))];
    }

    /** @param array<string, mixed> $payload */
    public function send(PushSubscription $subscription, array $payload, int $ttl = 86400): Response
    {
        if (! $this->configured()) {
            throw new RuntimeException('Clés VAPID absentes (VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY).');
        }

        $body = $this->encrypt(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), self::unb64($subscription->public_key), self::unb64($subscription->auth_token));
        $parts = parse_url($subscription->endpoint);

        return Http::timeout(10)->withHeaders([
            'Authorization' => 'vapid t='.$this->vapidJwt($parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')).', k='.$this->publicKey(),
            'Content-Encoding' => 'aes128gcm',
            'TTL' => (string) $ttl,
            'Urgency' => 'high',
        ])->withBody($body, 'application/octet-stream')->post($subscription->endpoint);
    }

    /** Chiffrement RFC 8291 : ECDH avec une clé éphémère, HKDF, AES-128-GCM en un seul enregistrement. */
    public function encrypt(string $plaintext, string $uaPublic, string $authSecret): string
    {
        $ephemeral = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $asPublic = self::point(openssl_pkey_get_details($ephemeral)['ec']);
        $shared = openssl_pkey_derive(self::publicKeyFromPoint($uaPublic), $ephemeral);
        if ($shared === false) {
            throw new RuntimeException('Clé de l\'abonnement push invalide.');
        }

        $ikm = hash_hkdf('sha256', $shared, 32, "WebPush: info\0".$uaPublic.$asPublic, $authSecret);
        $salt = random_bytes(16);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
        $cipher = openssl_encrypt($plaintext."\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

        return $salt.pack('N', 4096).chr(strlen($asPublic)).$asPublic.$cipher.$tag;
    }

    private function vapidJwt(string $audience): string
    {
        $input = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'ES256'])).'.'.self::b64(json_encode([
            'aud' => $audience, 'exp' => time() + 12 * 3600, 'sub' => config('services.webpush.subject') ?: 'mailto:contact@example.com',
        ], JSON_UNESCAPED_SLASHES));
        openssl_sign($input, $der, $this->privatePem(), OPENSSL_ALGO_SHA256);

        return $input.'.'.self::b64(self::derToRaw($der));
    }

    /** Clé privée VAPID (32 octets) → PEM « EC PRIVATE KEY » (SEC 1) lisible par OpenSSL. */
    private function privatePem(): string
    {
        $der = hex2bin('30770201010420').self::unb64((string) config('services.webpush.private_key'))
            .hex2bin('a00a06082a8648ce3d030107a144034200').self::unb64((string) $this->publicKey());

        return "-----BEGIN EC PRIVATE KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END EC PRIVATE KEY-----\n";
    }

    /** Signature DER (SEQUENCE de deux INTEGER) → r‖s sur 64 octets, format exigé par JWT. */
    private static function derToRaw(string $der): string
    {
        $offset = 2;
        $raw = '';
        for ($i = 0; $i < 2; $i++) {
            $length = ord($der[$offset + 1]);
            $raw .= str_pad(ltrim(substr($der, $offset + 2, $length), "\0"), 32, "\0", STR_PAD_LEFT);
            $offset += 2 + $length;
        }

        return $raw;
    }

    /** @param array{x: string, y: string} $ec */
    private static function point(array $ec): string
    {
        return "\x04".str_pad($ec['x'], 32, "\0", STR_PAD_LEFT).str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);
    }

    private static function publicKeyFromPoint(string $point): mixed
    {
        $pem = "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode(hex2bin(self::P256_SPKI_PREFIX).$point), 64, "\n")."-----END PUBLIC KEY-----\n";

        return openssl_pkey_get_public($pem) ?: throw new RuntimeException('Clé de l\'abonnement push invalide.');
    }

    public static function b64(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public static function unb64(string $text): string
    {
        return (string) base64_decode(strtr($text, '-_', '+/'), true);
    }
}
