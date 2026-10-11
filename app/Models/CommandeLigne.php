<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ligne d'une commande : désignation et prix figés au moment de la commande (le produit peut changer ensuite). */
#[Fillable(['commande_id', 'materiel_id', 'designation', 'unit_price', 'normal_price', 'quantity', 'line_total'])]
class CommandeLigne extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return ['unit_price' => 'integer', 'normal_price' => 'integer', 'quantity' => 'integer', 'line_total' => 'integer'];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class)->withTrashed();
    }

    public function materiel(): BelongsTo
    {
        return $this->belongsTo(Materiel::class)->withTrashed();
    }

    public function activityModule(): string
    {
        return 'commandes';
    }

    public function activityNoun(): string
    {
        return 'Ligne de commande';
    }

    public function activityLabel(): string
    {
        return $this->designation.' × '.$this->quantity;
    }
}
