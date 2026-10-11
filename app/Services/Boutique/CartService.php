<?php

namespace App\Services\Boutique;

use App\Enums\Availability;
use App\Models\Materiel;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Panier de la boutique, gardé en session (identifiant du matériel => quantité). Les prix ne sont jamais stockés :
 * ils sont relus à chaque affichage et à la commande (prix promotionnel compris). Seul un matériel publié, avec un prix
 * et pas « indisponible », peut être commandé.
 */
class CartService
{
    public const KEY = 'boutique.panier';

    public const MAX_QUANTITY = 20;

    public const MAX_LINES = 30;

    public function __construct(private readonly Session $session) {}

    public static function purchasable(Materiel $materiel): bool
    {
        return $materiel->is_published && $materiel->price !== null && $materiel->availability !== Availability::Indisponible
            && ($materiel->stock_quantity === null || $materiel->stock_quantity > 0);
    }

    /** Quantité maximale commandable : le stock synchronisé s'il est connu, sinon MAX_QUANTITY. */
    public static function maxFor(Materiel $materiel): int
    {
        return $materiel->stock_quantity === null ? self::MAX_QUANTITY : max(0, min(self::MAX_QUANTITY, $materiel->stock_quantity));
    }

    /** @return array<int, int> */
    public function raw(): array
    {
        return array_filter((array) $this->session->get(self::KEY, []), fn ($q) => is_int($q) && $q > 0);
    }

    public function add(Materiel $materiel, int $quantity = 1): void
    {
        $cart = $this->raw();
        if (! isset($cart[$materiel->id]) && count($cart) >= self::MAX_LINES) {
            return;
        }
        $cart[$materiel->id] = min(self::maxFor($materiel), ($cart[$materiel->id] ?? 0) + max(1, $quantity));
        $this->session->put(self::KEY, $cart);
    }

    public function set(Materiel $materiel, int $quantity): void
    {
        $cart = $this->raw();
        if ($quantity <= 0) {
            unset($cart[$materiel->id]);
        } elseif (isset($cart[$materiel->id])) {
            $cart[$materiel->id] = min(self::maxFor($materiel), $quantity);
        }
        $this->session->put(self::KEY, $cart);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    /**
     * Lignes commandables, prix du moment : un produit retiré, dépublié ou devenu indisponible disparaît du panier.
     *
     * @return Collection<int, array{materiel: Materiel, quantity: int, unit_price: int, normal_price: int, line_total: int}>
     */
    public function lines(): Collection
    {
        $cart = $this->raw();
        if (! $cart) {
            return collect();
        }

        $materiels = Materiel::with(['mainImage', 'gallery'])->whereKey(array_keys($cart))->get()->filter(fn (Materiel $m) => self::purchasable($m))->keyBy('id');
        if ($materiels->count() !== count($cart)) {
            $this->session->put(self::KEY, array_intersect_key($cart, $materiels->all()));
        }

        return collect($cart)->filter(fn ($q, $id) => $materiels->has($id))->map(function (int $quantity, int $id) use ($materiels) {
            $materiel = $materiels[$id];
            $quantity = min($quantity, self::maxFor($materiel));
            $unit = (int) $materiel->currentPrice();

            return ['materiel' => $materiel, 'quantity' => $quantity, 'unit_price' => $unit, 'normal_price' => (int) $materiel->price, 'line_total' => $unit * $quantity];
        })->values();
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function total(?Collection $lines = null): int
    {
        return (int) ($lines ?? $this->lines())->sum('line_total');
    }
}
