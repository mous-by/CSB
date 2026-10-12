<?php

namespace App\Models;

use App\Enums\CommissionKind;
use App\Enums\CommissionStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Commission d'un partenaire sur une vente : montant de base encaissé × taux du moment (figé). */
#[Fillable(['partenaire_id', 'source_type', 'source_id', 'kind', 'label', 'base_amount', 'rate', 'amount', 'status', 'paid_at', 'payment_reference', 'notes', 'created_by'])]
class Commission extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'kind' => CommissionKind::class, 'status' => CommissionStatus::class, 'base_amount' => 'integer', 'rate' => 'float',
            'amount' => 'integer', 'paid_at' => 'datetime',
        ];
    }

    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(Partenaire::class)->withTrashed();
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function activityNoun(): string
    {
        return 'Commission';
    }

    public function activityLabel(): string
    {
        return $this->label.' — '.Materiel::money($this->amount);
    }
}
