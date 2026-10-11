<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Number;

/**
 * Document déposé par CSB pour un client (devis, facture…) : fichier sur le disque privé, téléchargeable seulement par
 * ce client (espace client) et par l'équipe autorisée.
 */
#[Fillable(['client_id', 'title', 'type', 'path', 'original_name', 'size', 'uploaded_by', 'first_downloaded_at'])]
class ClientDocument extends Model
{
    use LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return ['type' => DocumentType::class, 'size' => 'integer', 'first_downloaded_at' => 'datetime'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function sizeForDisplay(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }

    public function activityModule(): string
    {
        return 'clients';
    }

    public function activityNoun(): string
    {
        return 'Document client';
    }

    public function activityLabel(): string
    {
        return (string) $this->title;
    }
}
