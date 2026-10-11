<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tableau de bord de l'administration (cahier §7) : une seule page, dont les sections dépendent des permissions de
 * l'utilisateur (commercial : demandes ; gestionnaire : contenus ; administrateur : tout, plus comptes et
 * exploitation ; superadmin : tout, plus la supervision).
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): View
    {
        return view('home', ['dashboard' => $dashboard->for($request->user())]);
    }
}
