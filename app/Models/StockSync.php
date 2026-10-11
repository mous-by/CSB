<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Journal d'une synchronisation du stock (API ou fichier) : éléments reçus, reconnus, modifiés, références inconnues. */
#[Fillable(['source', 'user_id', 'api_token_id', 'file_name', 'items_count', 'matched_count', 'updated_count', 'unknown_references'])]
class StockSync extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return ['unknown_references' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(ApiToken::class, 'api_token_id');
    }

    public function activityModule(): string
    {
        return 'synchro';
    }

    public function activityNoun(): string
    {
        return 'Synchronisation du stock';
    }

    public function activityLabel(): string
    {
        return '#'.$this->id.' ('.$this->source.')';
    }
}
