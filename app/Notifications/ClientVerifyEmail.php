<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/** Lien signé de confirmation de l'adresse e-mail d'un client (valable 3 jours). */
class ClientVerifyEmail extends Notification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('client.verification.verify', now()->addDays(3), ['client' => $notifiable->id, 'hash' => sha1($notifiable->email)]);

        return (new MailMessage)
            ->subject('Confirmez votre adresse e-mail')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Merci d\'avoir créé votre espace client. Confirmez votre adresse pour y retrouver vos demandes.')
            ->action('Confirmer mon adresse', $url)
            ->line('Ce lien est valable 3 jours.')
            ->salutation('L\'équipe '.config('brand.name'));
    }
}
