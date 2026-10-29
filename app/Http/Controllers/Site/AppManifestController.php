<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\SiteSettings;
use Illuminate\Http\JsonResponse;

/**
 * Manifestes des deux applications installables (PWA) avec l'icône de CSB : le site public (`/`) et l'administration
 * de l'équipe (`/admin`). Publics tous les deux : le navigateur les télécharge sans la session.
 */
class AppManifestController extends Controller
{
    public function site(SiteSettings $site): JsonResponse
    {
        return $this->manifest([
            'id' => '/',
            'name' => $site->get('company.name'),
            'short_name' => $site->get('company.short_name'),
            'description' => $site->get('company.slogan'),
            'start_url' => '/',
            'scope' => '/',
        ], 'icon');
    }

    public function team(SiteSettings $site): JsonResponse
    {
        $admin = '/'.trim((string) config('site.admin_path'), '/');

        return $this->manifest([
            'id' => $admin,
            'name' => $site->get('company.short_name').' — Administration',
            'short_name' => $site->get('company.short_name').' Admin',
            'description' => 'Demandes, commandes, tickets et contenus du site '.$site->get('company.short_name').'.',
            'start_url' => $admin,
            'scope' => $admin,
            'background_color' => '#103b8e',
        ], 'admin-icon');
    }

    /**
     * @param  array<string, string>  $app
     * @param  string  $icons  préfixe des icônes de public/assets/pwa : « icon » (logo sur fond blanc) ou
     *                         « admin-icon » (fond bleu et bandeau « ADMIN », pour ne pas confondre les deux applications)
     */
    private function manifest(array $app, string $icons): JsonResponse
    {
        return response()->json($app + [
            'lang' => 'fr',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#103b8e',
            'icons' => [
                ['src' => asset("assets/pwa/{$icons}-192.png"), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset("assets/pwa/{$icons}-512.png"), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset("assets/pwa/{$icons}-maskable-512.png"), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
