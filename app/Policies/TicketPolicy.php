<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Permissions du manifeste config/modules/tickets.php, plus le cloisonnement par application : l'Administrateur
 * KalanNet n'ouvre que les tickets venus de KalanNet (Ticket::isVisibleTo), même avec l'adresse d'un autre ticket.
 */
class TicketPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'tickets';
    }

    public function view(User $user, Model $model): bool
    {
        return parent::view($user, $model) && $model->isVisibleTo($user);
    }

    public function update(User $user, Model $model): bool
    {
        return parent::update($user, $model) && $model->isVisibleTo($user);
    }

    public function delete(User $user, Model $model): bool
    {
        return parent::delete($user, $model) && $model->isVisibleTo($user);
    }
}
