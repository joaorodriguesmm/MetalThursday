<?php

declare(strict_types=1);

use Illuminate\Support\Str;

/**
 * Define as configurações das sessões da aplicação.
 *
 * Os nomes das chaves e drivers permanecem em inglês por corresponderem aos
 * contratos internos de configuração do Laravel.
 *
 * @return array<string, mixed> Configurações das sessões.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Driver de sessão
    |--------------------------------------------------------------------------
    */

    'driver' => env(
        'SESSION_DRIVER',
        'database',
    ),

    /*
    |--------------------------------------------------------------------------
    | Duração da sessão
    |--------------------------------------------------------------------------
    */

    /*
     * Número de minutos durante os quais uma sessão pode permanecer inativa.
     */
    'lifetime' => (int) env(
        'SESSION_LIFETIME',
        120,
    ),

    /*
     * Determina se a sessão termina quando o navegador é fechado.
     */
    'expire_on_close' => env(
        'SESSION_EXPIRE_ON_CLOSE',
        false,
    ),

    /*
    |--------------------------------------------------------------------------
    | Encriptação
    |--------------------------------------------------------------------------
    */

    /*
     * Determina se o conteúdo da sessão é encriptado antes de ser armazenado.
     */
    'encrypt' => env(
        'SESSION_ENCRYPT',
        true,
    ),

    /*
    |--------------------------------------------------------------------------
    | Armazenamento
    |--------------------------------------------------------------------------
    */

    /*
     * Diretório utilizado exclusivamente pelo driver `file`.
     */
    'files' => storage_path(
        'framework/sessions',
    ),

    /*
     * Ligação utilizada pelo driver `database`.
     */
    'connection' => 'mysql',

    /*
     * Tabela utilizada pelo driver `database`.
     */
    'table' => 'sessoes',

    /*
     * Armazenamento aplicável apenas a drivers de sessão baseados em cache.
     */
    'store' => null,

    /*
    |--------------------------------------------------------------------------
    | Limpeza de sessões expiradas
    |--------------------------------------------------------------------------
    */

    /*
     * Probabilidade de 2 em 100 de limpar sessões expiradas num pedido.
     */
    'lottery' => [
        2,
        100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cookie de sessão
    |--------------------------------------------------------------------------
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(
            (string) env(
                'APP_NAME',
                'MetalThursday',
            ),
        ).'-sessao',
    ),

    /*
     * Caminho no qual o cookie fica disponível.
     */
    'path' => env(
        'SESSION_PATH',
        '/',
    ),

    /*
     * Domínio e subdomínios nos quais o cookie fica disponível.
     */
    'domain' => env(
        'SESSION_DOMAIN',
    ),

    /*
     * Determina se o cookie só pode ser enviado através de HTTPS.
     */
    'secure' => env(
        'SESSION_SECURE_COOKIE',
    ),

    /*
     * Impede que JavaScript aceda ao cookie.
     */
    'http_only' => env(
        'SESSION_HTTP_ONLY',
        true,
    ),

    /*
     * Controla o envio do cookie em pedidos entre sites.
     */
    'same_site' => env(
        'SESSION_SAME_SITE',
        'lax',
    ),

    /*
     * Associa o cookie ao site de nível superior em contextos entre sites.
     */
    'partitioned' => env(
        'SESSION_PARTITIONED_COOKIE',
        false,
    ),
];
