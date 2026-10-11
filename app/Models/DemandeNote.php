<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Note interne sur une demande (cahier §7 « ajouter des notes internes », §9.1 « accès réservé au personnel autorisé »).
 */
#[Fillable(['demande_id', 'user_id', 'body'])]
class DemandeNote extends Model
{
    use LogsActivity;

    public function demande(): BelongsTo
    {
        return $this->belongsTo(Demande::class)->withTrashed();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function activityModule(): string
    {
        return 'demandes';
    }

    public function activityNoun(): string
    {
        return 'Note interne';
    }

    public function activityLabel(): string
    {
        return 'Demande #'.$this->demande_id;
    }
}
