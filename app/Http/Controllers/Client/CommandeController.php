<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Commandes passées par le client connecté (le détail est la page de suivi de la commande). */
class CommandeController extends Controller
{
    public function index(Request $request): View
    {
        return view('client.commandes', [
            'commandes' => Commande::withCount('lignes')->where('client_id', $request->user('client')->id)->latest('id')->paginate(15),
            'seo' => Seo::make('Mes commandes')->noIndex(),
        ]);
    }
}
