<?php

namespace App\Policies;

use App\Models\User;

/** Permissions du manifeste config/modules/medias.php (`upload` au lieu de `create`). */
class MediaPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'medias';
    }

    public function create(User $user): bool
    {
        return $user->can('medias.upload');
    }
}
