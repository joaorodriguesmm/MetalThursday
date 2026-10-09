<?php

declare(strict_types=1);

/*
 * Evita divulgar a versão do PHP mesmo quando expose_php está ativo
 * no ambiente de execução.
 */
header_remove('X-Powered-By');

use Illuminate\Http\Request;

/**
 * Inicia a aplicação Laravel.
 *
 * A constante `LARAVEL_START` permanece em inglês por ser utilizada
 * internamente pelo Laravel.
 *
 * @since 1.0.0
 */
define('LARAVEL_START', microtime(true));

$ficheiroManutencao = __DIR__.'/../storage/framework/maintenance.php';

if (file_exists($ficheiroManutencao)) {
    require $ficheiroManutencao;
}

require __DIR__.'/../vendor/autoload.php';

$aplicacao = require_once __DIR__.'/../bootstrap/app.php';

$aplicacao->handleRequest(Request::capture());
