<?php

namespace App\Policies;

/** Permissions du manifeste config/modules/tickets.php. */
class TicketPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'tickets';
    }
}
