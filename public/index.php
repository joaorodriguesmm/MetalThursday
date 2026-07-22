<?php

use Illuminate\Http\Request;

/**
 * Arranca a Aplicação.
 *
 * @since 1.0
 *
 * @version 1.0
 */
define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
