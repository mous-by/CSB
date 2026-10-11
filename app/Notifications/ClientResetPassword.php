<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Lien de réinitialisation du mot de passe d'un client (espace client), valable 60 minutes. */
class ClientResetPassword extends Notification
{
    public function __construct(public readonly string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Vous avez demandé à réinitialiser le mot de passe de votre espace client.')
            ->action('Choisir un nouveau mot de passe', route('client.password.reset', ['token' => $this->token, 'email' => $notifiable->email]))
            ->line('Ce lien est valable 60 minutes. Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message.')
            ->salutation('L\'équipe '.config('brand.name'));
    }
}
