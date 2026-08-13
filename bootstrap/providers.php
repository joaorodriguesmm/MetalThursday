<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;

/**
 * Define os providers específicos da aplicação.
 *
 * Os nomes das classes permanecem em inglês por seguirem as convenções dos
 * providers do Laravel.
 *
 * @since 1.0.0
 */
return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
];
