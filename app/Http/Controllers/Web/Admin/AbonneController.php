<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abonne;
use App\Models\Article;
use App\Services\Audit\ActivityLogger;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Abonnés aux actualités : liste, désinscription, suppression, export CSV ; envoi d'un article publié aux abonnés.
 */
class AbonneController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Abonne::class);

        return view('abonnes.index', ['abonnes' => Abonne::latest('id')->get()]);
    }

    public function unsubscribe(Abonne $abonne, NewsletterService $service): RedirectResponse
    {
        Gate::authorize('delete', $abonne);
        $service->unsubscribe($abonne);

        return redirect()->route('abonnes.index')->with('status', "{$abonne->email} désinscrit(e).");
    }

    public function destroy(Abonne $abonne): RedirectResponse
    {
        Gate::authorize('delete', $abonne);
        $abonne->delete();

        return redirect()->route('abonnes.index')->with('status', "{$abonne->email} supprimé(e).");
    }

    public function export(ActivityLogger $logger): StreamedResponse
    {
        Gate::authorize('viewAny', Abonne::class);
        $logger->log('abonnes.exported', 'abonnes', 'Export CSV des abonnés ('.Abonne::count().' ligne(s))');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['E-mail', 'Nom', 'Statut', 'Inscrit le', 'Désinscrit le', 'Origine'], ';');
            Abonne::orderBy('email')->each(fn (Abonne $a) => fputcsv($out, [
                $a->email, $a->name, $a->isActive() ? 'Actif' : 'Désinscrit', $a->subscribed_at?->format('d/m/Y'), $a->unsubscribed_at?->format('d/m/Y'), $a->source,
            ], ';'));
            fclose($out);
        }, 'abonnes-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function send(Article $article, NewsletterService $service): RedirectResponse
    {
        try {
            $count = $service->sendArticle($article, request()->user());
        } catch (RuntimeException $e) {
            return redirect()->route('articles.index')->with('error', $e->getMessage());
        }

        return redirect()->route('articles.index')->with('status', "Article « {$article->title} » envoyé à {$count} abonné(s).");
    }
}
