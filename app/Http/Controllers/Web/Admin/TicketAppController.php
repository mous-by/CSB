<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Applications de CSB connectées aux tickets (KalanNet, DojoManager…) : un jeton par application, dont le nom est celui
 * affiché sur les tickets et sert à les cloisonner. Révoquer un jeton coupe l'accès ; un nouveau jeton du même nom
 * retrouve les tickets de l'application.
 */
class TicketAppController extends Controller
{
    public function index(): View
    {
        return view('tickets.apps', [
            'tokens' => ApiToken::scoped(ApiToken::SCOPE_TICKETS)->with('creator')->latest('id')->get(),
            'counts' => Ticket::whereNotNull('source_app')->selectRaw('source_app, count(*) as total')->groupBy('source_app')->pluck('total', 'source_app'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100',
            Rule::unique('api_tokens', 'name')->where('scope', ApiToken::SCOPE_TICKETS)->whereNull('revoked_at')]],
            ['name.unique' => 'Cette application a déjà un jeton actif : révoquez-le d\'abord pour en créer un nouveau.'], ['name' => 'nom de l\'application']);
        [, $plain] = ApiToken::issue(trim($data['name']), $request->user(), ApiToken::SCOPE_TICKETS);

        return redirect()->route('tickets.apps.index')->with('new_token', $plain);
    }

    public function revoke(ApiToken $token): RedirectResponse
    {
        abort_unless($token->scope === ApiToken::SCOPE_TICKETS, 404);
        $token->update(['revoked_at' => now()]);

        return redirect()->route('tickets.apps.index')->with('status', "Jeton de « {$token->name} » révoqué : l'application ne peut plus ouvrir ni suivre de ticket.");
    }
}
