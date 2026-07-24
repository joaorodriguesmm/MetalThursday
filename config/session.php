<?php

declare(strict_types=1);

use Illuminate\Support\Str;

/**
 * Define as configurações das sessões da aplicação.
 *
 * Os nomes das chaves permanecem em inglês por corresponderem aos contratos
 * de configuração utilizados pelo Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
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
     * Número de minutos durante os quais uma sessão pode permanecer inativa
     * antes de expirar.
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
     * Determina se os dados da sessão devem ser encriptados antes de serem
     * armazenados.
     */
    'encrypt' => env(
        'SESSION_ENCRYPT',
        false,
    ),

    /*
    |--------------------------------------------------------------------------
    | Armazenamento
    |--------------------------------------------------------------------------
    */

    /*
     * Diretório utilizado pelo driver `file`.
     */
    'files' => storage_path(
        'framework/sessions',
    ),

    /*
     * Ligação utilizada pelos drivers `database` e `redis`.
     */
    'connection' => env(
        'SESSION_CONNECTION',
    ),

    /*
     * Tabela utilizada pelo driver `database`.
     */
    'table' => env(
        'SESSION_TABLE',
        'sessions',
    ),

    /*
     * Armazenamento de cache utilizado pelos drivers `dynamodb`,
     * `memcached` e `redis`.
     */
    'store' => env(
        'SESSION_STORE',
    ),

    /*
    |--------------------------------------------------------------------------
    | Limpeza de sessões expiradas
    |--------------------------------------------------------------------------
    */

    /*
     * Em cada pedido existe uma probabilidade de 2 em 100 de executar a
     * limpeza das sessões expiradas.
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
        ).'-session',
    ),

    /*
     * Caminho no qual o cookie está disponível.
     */
    'path' => env(
        'SESSION_PATH',
        '/',
    ),

    /*
     * Domínio e subdomínios nos quais o cookie está disponível.
     */
    'domain' => env(
        'SESSION_DOMAIN',
    ),

    /*
     * Quando ativo, o cookie é transmitido apenas através de HTTPS.
     *
     * A ausência de um valor predefinido permite distinguir entre uma
     * configuração explicitamente falsa e uma configuração não definida.
     */
    'secure' => env(
        'SESSION_SECURE_COOKIE',
    ),

    /*
     * Impede o acesso ao cookie através de JavaScript.
     */
    'http_only' => env(
        'SESSION_HTTP_ONLY',
        true,
    ),

    /*
     * Controla o envio do cookie em pedidos entre sites.
     *
     * Valores suportados: `lax`, `strict`, `none` ou `null`.
     */
    'same_site' => env(
        'SESSION_SAME_SITE',
        'lax',
    ),

    /*
     * Associa o cookie ao site de nível superior em contextos entre sites.
     *
     * Cookies particionados exigem normalmente `secure` ativo e
     * `same_site` definido como `none`.
     */
    'partitioned' => env(
        'SESSION_PARTITIONED_COOKIE',
        false,
    ),
];
