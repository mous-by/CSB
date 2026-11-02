<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\EcoleReference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Écoles envoyées par une application connectée (même jeton que ses tickets), pour la bande de l'accueil.
 *   POST /api/v1/references/ecoles              → écoles (visible ou non) ; `complet` : retire les absentes de la liste ;
 *                                                 répond les logos à envoyer (empreinte différente)
 *   POST /api/v1/references/ecoles/{ref}/logo   → logo, ré-encodé en PNG de 240 px au plus
 * Une application ne touche qu'à ses propres écoles.
 */
class EcoleReferenceController extends Controller
{
    private const LOGO_MAX = 240;

    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ecoles' => ['present', 'array', 'max:5000'],
            'ecoles.*.ref' => ['required', 'string', 'max:64'],
            'ecoles.*.nom' => ['required', 'string', 'max:200'],
            'ecoles.*.adresse' => ['nullable', 'string', 'max:255'],
            'ecoles.*.logo_hash' => ['nullable', 'string', 'size:32'],
            'ecoles.*.visible' => ['required', 'boolean'],
            'complet' => ['boolean'],
        ]);

        $app = $this->app($request)->name;
        $existantes = EcoleReference::where('source_app', $app)->get()->keyBy('ref');
        $logos = [];

        foreach ($data['ecoles'] as $ligne) {
            $ecole = $existantes->get($ligne['ref']);
            if (! $ligne['visible']) {
                $ecole?->update(['is_visible' => false]);

                continue;
            }

            $ecole ??= new EcoleReference(['source_app' => $app, 'ref' => $ligne['ref']]);
            $ecole->fill(['name' => $ligne['nom'], 'address' => $ligne['adresse'] ?? null, 'is_visible' => true])->save();

            $hash = $ligne['logo_hash'] ?? null;
            if ($hash === null && $ecole->logo_path) {
                $this->retirerLogo($ecole);
            } elseif ($hash !== null && $hash !== $ecole->logo_hash) {
                $logos[] = $ligne['ref'];
            }
        }

        if ($data['complet'] ?? false) {
            EcoleReference::where('source_app', $app)->where('is_visible', true)->whereNotIn('ref', array_column($data['ecoles'], 'ref'))->update(['is_visible' => false]);
        }

        Cache::forget(EcoleReference::CACHE_KEY);

        return response()->json(['logos_a_envoyer' => $logos]);
    }

    public function logo(Request $request, string $ref): JsonResponse
    {
        $data = $request->validate([
            'logo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'logo_hash' => ['required', 'string', 'size:32'],
        ]);
        $ecole = EcoleReference::where('source_app', $this->app($request)->name)->where('ref', $ref)->firstOrFail();

        $image = @imagecreatefromstring((string) file_get_contents($request->file('logo')->getRealPath()));
        if (! $image) {
            throw ValidationException::withMessages(['logo' => 'Image illisible.']);
        }

        $this->retirerLogo($ecole);
        $chemin = 'references/ecoles/'.sha1($ecole->source_app.'|'.$ecole->ref).'-'.substr($data['logo_hash'], 0, 8).'.png';
        Storage::disk('public')->put($chemin, $this->png($image));
        $ecole->update(['logo_path' => $chemin, 'logo_hash' => $data['logo_hash']]);
        Cache::forget(EcoleReference::CACHE_KEY);

        return response()->json(['ok' => true]);
    }

    private function png(\GdImage $image): string
    {
        $ratio = min(1, self::LOGO_MAX / max(imagesx($image), imagesy($image)));
        $sortie = imagecreatetruecolor(max(1, (int) round(imagesx($image) * $ratio)), max(1, (int) round(imagesy($image) * $ratio)));
        imagealphablending($sortie, false);
        imagesavealpha($sortie, true);
        imagecopyresampled($sortie, $image, 0, 0, 0, 0, imagesx($sortie), imagesy($sortie), imagesx($image), imagesy($image));

        ob_start();
        imagepng($sortie, null, 8);

        return (string) ob_get_clean();
    }

    private function retirerLogo(EcoleReference $ecole): void
    {
        if ($ecole->logo_path) {
            Storage::disk('public')->delete($ecole->logo_path);
        }
        $ecole->update(['logo_path' => null, 'logo_hash' => null]);
    }

    private function app(Request $request): ApiToken
    {
        return $request->attributes->get('api_token');
    }
}
