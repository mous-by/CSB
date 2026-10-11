<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

/** Permissions du manifeste config/modules/articles.php. */
class ArticlePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'articles';
    }

    public function publish(User $user, ?Article $article = null): bool
    {
        return $user->can('articles.publish');
    }
}
