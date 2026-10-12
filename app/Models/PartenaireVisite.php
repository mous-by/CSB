<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Visites du site venues du lien d'un partenaire, comptées par jour (compteur incrémenté en SQL : pas d'audit par visite). */
#[Fillable(['partenaire_id', 'day', 'visits'])]
class PartenaireVisite extends Model
{
    use LogsActivity;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['day' => 'date', 'visits' => 'integer'];
    }

    public function activityModule(): string
    {
        return 'partenaires';
    }
}
