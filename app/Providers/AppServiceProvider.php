<?php

namespace App\Providers;

use App\Regras\Autenticacao\PoliticaPalavraPasse;
use App\View\Composers\NavigationComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

/**
 * Define os serviços da aplicação.
 *
 * @version 1.0
 *
 * @since 1.0
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Regista os serviços da aplicação.
     *
     * @version 1.0
     *
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
     *
     * @since 1.0
     */
    public function boot(): void
    {
        Password::defaults(
            static fn (): Password => PoliticaPalavraPasse::regra(),
        );

        Paginator::useBootstrap();
        View::composer('layouts.navigation', NavigationComposer::class);
    }
}
