<?php

namespace App\Services\Newsletter;

use App\Mail\ArticleNewsletter;
use App\Models\Abonne;
use App\Models\Article;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Abonnements aux actualités : inscription (réinscription d'un ancien abonné), désinscription par jeton, envoi d'un
 * article publié à tous les abonnés actifs (e-mails mis en file d'attente, un article ne s'envoie qu'une fois).
 */
class NewsletterService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function subscribe(string $email, ?string $name = null, string $source = 'site', ?string $ip = null): Abonne
    {
        $email = mb_strtolower(trim($email));
        $abonne = Abonne::firstWhere('email', $email);

        if ($abonne === null) {
            return Abonne::create(['email' => $email, 'name' => $name, 'source' => $source, 'ip_address' => $ip]);
        }
        if (! $abonne->isActive()) {
            $abonne->update(['unsubscribed_at' => null, 'subscribed_at' => now(), 'name' => $name ?? $abonne->name]);
        }

        return $abonne;
    }

    public function unsubscribe(Abonne $abonne): void
    {
        if ($abonne->isActive()) {
            $abonne->update(['unsubscribed_at' => now()]);
        }
    }

    /** @return int nombre d'e-mails mis en file d'attente */
    public function sendArticle(Article $article, User $actor): int
    {
        if (! $article->isVisible()) {
            throw new RuntimeException('Seul un article publié peut être envoyé aux abonnés.');
        }
        if ($article->newsletter_sent_at !== null) {
            throw new RuntimeException('Cet article a déjà été envoyé aux abonnés le '.$article->newsletter_sent_at->format('d/m/Y à H:i').'.');
        }

        return DB::transaction(function () use ($article, $actor) {
            $count = 0;
            Abonne::active()->orderBy('id')->each(function (Abonne $abonne) use ($article, &$count) {
                Mail::to($abonne->email)->queue(new ArticleNewsletter($article, $abonne));
                $count++;
            });

            $article->forceFill(['newsletter_sent_at' => now()])->saveQuietly();
            $this->logger->log('abonnes.sent', 'abonnes', "Envoi aux abonnés — article « {$article->title} » ({$count} destinataire(s))", $article, new: ['destinataires' => $count], actor: $actor);

            return $count;
        });
    }
}
