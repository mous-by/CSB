<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\Statistiques\SiteStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Statistiques du site : fréquentation, provenance, appareils, pages vues, comptes clients et conversion. */
class StatistiquesController extends Controller
{
    public function index(Request $request, SiteStats $stats): View
    {
        return view('statistiques.index', ['report' => $stats->report((int) $request->query('periode', 30))]);
    }
}
