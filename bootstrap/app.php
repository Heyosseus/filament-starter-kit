<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    /*
     * Register everything in app/Listeners by the event each handle* method
     * type-hints. Without this call Laravel does no discovery at all, and a
     * listener sitting in that directory simply never runs.
     */
    ->withEvents(discover: [__DIR__.'/../app/Listeners'])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
