<?php

namespace App\Providers;

use App\View\Composers\NavigationComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Define os serviços da aplicação.
 *
 * @version 1.0
 * @since 1.0
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Regista os serviços da aplicação.
     *
     * @version 1.0
     * @since 1.0
     */
    public function register(): void
    {
        //
    }

    /**
     * Executa os serviços da aplicação.
     *
     * @version 1.0
     * @since 1.0
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        View::composer('layouts.navigation', NavigationComposer::class);
    }
}
