<?php

use App\Http\Middleware\AuthenticateSyncToken;
use App\Http\Middleware\EnsureClientIsActive;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureSuperadminsExist;
use App\Http\Middleware\EnsureUserIsActive;
use App\Models\Redirection;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'client.active' => EnsureClientIsActive::class,
            'sync.token' => AuthenticateSyncToken::class,
            'password.changed' => EnsurePasswordChanged::class,
            'superadmins.ensure' => EnsureSuperadminsExist::class,
            // Toutes les routes sont protégées par `permission:module.action` (pas de `role:` dans les routes, CLAUDE.md §4.4).
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Espace client (/espace-client) : sa propre page de connexion et son propre tableau de bord.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('espace-client', 'espace-client/*') ? route('client.login') : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('espace-client', 'espace-client/*') ? route('client.home') : route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Site public : une adresse introuvable qui a changé (contenu renommé) redirige vers la nouvelle (301,
        // cahier §11.1) au lieu d'afficher la page 404.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->isMethod('GET') || $request->is(config('site.admin_path'), config('site.admin_path').'/*')) {
                return null;
            }

            return Redirection::follow($request);
        });
    })->create();
