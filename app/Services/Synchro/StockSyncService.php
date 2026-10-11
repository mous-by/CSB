<?php

namespace App\Services\Synchro;

use App\Enums\ActivityChannel;
use App\Enums\Availability;
use App\Models\ApiToken;
use App\Models\Materiel;
use App\Models\StockSync;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Synchronisation du stock (cahier §15.3) : le logiciel de stock de CSB (API) ou un fichier envoie, par référence,
 * la quantité en stock et éventuellement le prix. Tout ou rien : un élément invalide refuse l'envoi entier.
 * Règles : quantité > 0 → « Disponible », 0 → « Indisponible » ; un prix reçu remplace le prix affiché (une promotion
 * devenue supérieure ou égale au nouveau prix est retirée). Une référence inconnue est signalée, jamais créée.
 */
class StockSyncService
{
    public const MAX_ITEMS = 5000;

    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * @param  list<array{reference?: mixed, quantity?: mixed, price?: mixed}>  $items
     *
     * @throws ValidationException
     */
    public function apply(array $items, string $source, ?User $user = null, ?ApiToken $token = null, ?string $fileName = null): StockSync
    {
        $validator = Validator::make(['items' => $items], [
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*.reference' => ['required', 'string', 'max:60'],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'items.*.price' => ['nullable', 'integer', 'min:0', 'max:999999999'],
        ], [], ['items.*.reference' => 'référence', 'items.*.quantity' => 'quantité', 'items.*.price' => 'prix']);
        $validator->validate();

        // Une référence présente deux fois : la dernière valeur l'emporte.
        $byReference = [];
        foreach ($items as $item) {
            $byReference[mb_strtoupper(trim((string) $item['reference']))] = ['quantity' => (int) $item['quantity'], 'price' => isset($item['price']) && $item['price'] !== '' ? (int) $item['price'] : null];
        }

        return DB::transaction(function () use ($byReference, $items, $source, $user, $token, $fileName) {
            $materiels = Materiel::whereIn('reference', array_keys($byReference))->lockForUpdate()->get()->keyBy(fn (Materiel $m) => mb_strtoupper($m->reference));
            $updated = [];

            Materiel::withoutActivityLog(function () use ($materiels, $byReference, &$updated) {
                foreach ($materiels as $reference => $materiel) {
                    $data = $byReference[$reference];
                    $changes = ['stock_quantity' => $data['quantity'], 'availability' => $data['quantity'] > 0 ? Availability::Disponible : Availability::Indisponible];
                    if ($data['price'] !== null) {
                        $changes['price'] = $data['price'];
                        if ($materiel->promo_price !== null && $materiel->promo_price >= $data['price']) {
                            $changes['promo_price'] = null;
                            $changes['promo_ends_on'] = null;
                        }
                    }
                    $materiel->fill($changes);
                    $dirty = array_keys($materiel->getDirty());
                    $materiel->stock_synced_at = now();
                    $materiel->save();
                    if ($dirty) {
                        $updated[$materiel->reference] = $data['quantity'];
                    }
                }
            });

            $unknown = array_values(array_diff(array_keys($byReference), $materiels->keys()->all()));
            $sync = StockSync::withoutActivityLog(fn () => StockSync::create([
                'source' => $source, 'user_id' => $user?->id, 'api_token_id' => $token?->id, 'file_name' => $fileName,
                'items_count' => count($items), 'matched_count' => $materiels->count(), 'updated_count' => count($updated),
                'unknown_references' => array_slice($unknown, 0, 500) ?: null,
            ]));

            $this->logger->log('synchro.applied', 'synchro',
                "Synchronisation du stock ({$source}) : {$sync->items_count} reçu(s), {$sync->matched_count} reconnu(s), {$sync->updated_count} modifié(s)".($unknown ? ', '.count($unknown).' référence(s) inconnue(s)' : ''),
                $sync, new: ['modifiés' => array_slice($updated, 0, 100, true)], actor: $user,
                channel: $token ? ActivityChannel::Api : null, subjectLabel: $token ? 'Jeton '.$token->name : null);

            return $sync;
        });
    }
}
