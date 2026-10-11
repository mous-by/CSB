<?php

namespace App\Mail;

use App\Models\Abonne;
use App\Models\Article;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/**
 * Article envoyé aux abonnés : extrait, lien vers l'article, lien de désinscription en un clic (aussi dans l'en-tête
 * List-Unsubscribe, reconnu par les messageries).
 */
class ArticleNewsletter extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Article $article, public readonly Abonne $abonne) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->article->title);
    }

    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<'.route('site.newsletter.unsubscribe', $this->abonne->token).'>']);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.article-newsletter', with: ['article' => $this->article, 'abonne' => $this->abonne]);
    }
}
