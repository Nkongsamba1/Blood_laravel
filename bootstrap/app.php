<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi(); // <--- INDISPENSABLE pour Sanctum en Laravel 11

        // AJOUTE CE BLOC ICI :
        $middleware->redirectGuestsTo(fn () => response()->json([
            'message' => 'Unauthenticated.'
        ], 401));

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // AJOUTE CECI : Exclure les routes API de la vérification CSRF
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'api/connexion',
            'api/inscription',
            'api/donneur/*',
        ]);

        $middleware->alias([
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
