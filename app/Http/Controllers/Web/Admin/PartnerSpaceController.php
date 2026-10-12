<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partenaire;
use App\Services\Partenaires\PartnerStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Espace du partenaire connecté (rôle Communication) : son lien, son code promo, ses résultats et ses commissions. */
class PartnerSpaceController extends Controller
{
    public function __invoke(Request $request, PartnerStats $stats): View
    {
        $partenaire = Partenaire::where('user_id', $request->user()->id)->first();

        return view('partenaires.espace', [
            'partenaire' => $partenaire,
            'stats' => $partenaire ? $stats->for($partenaire) : null,
            'commissions' => $partenaire ? $partenaire->commissions()->limit(50)->get() : collect(),
        ]);
    }
}
