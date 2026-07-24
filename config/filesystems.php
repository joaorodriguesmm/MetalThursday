<?php

declare(strict_types=1);

/**
 * Define as configurações dos sistemas de ficheiros da aplicação.
 *
 * Os nomes das chaves, discos e drivers permanecem em inglês por
 * corresponderem aos contratos de configuração utilizados pelo Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Disco predefinido
    |--------------------------------------------------------------------------
    */

    'default' => env(
        'FILESYSTEM_DISK',
        'local',
    ),

    /*
    |--------------------------------------------------------------------------
    | Discos de armazenamento
    |--------------------------------------------------------------------------
    */

    'disks' => [
        /*
         * Disco privado armazenado localmente.
         */
        'local' => [
            'driver' => 'local',

            'root' => storage_path(
                'app/private',
            ),

            'serve' => true,

            'throw' => false,

            'report' => false,
        ],

        /*
         * Disco local destinado a ficheiros publicamente acessíveis.
         */
        'public' => [
            'driver' => 'local',

            'root' => storage_path(
                'app/public',
            ),

            'url' => rtrim(
                (string) env(
                    'APP_URL',
                    'http://localhost',
                ),
                '/',
            ).'/storage',

            'visibility' => 'public',

            'throw' => false,

            'report' => false,
        ],

        /*
         * Disco compatível com Amazon S3.
         */
        's3' => [
            'driver' => 's3',

            'key' => env(
                'AWS_ACCESS_KEY_ID',
            ),

            'secret' => env(
                'AWS_SECRET_ACCESS_KEY',
            ),

            'region' => env(
                'AWS_DEFAULT_REGION',
            ),

            'bucket' => env(
                'AWS_BUCKET',
            ),

            'url' => env(
                'AWS_URL',
            ),

            'endpoint' => env(
                'AWS_ENDPOINT',
            ),

            'use_path_style_endpoint' => env(
                'AWS_USE_PATH_STYLE_ENDPOINT',
                false,
            ),

            'throw' => false,

            'report' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ligações simbólicas
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path(
            'storage',
        ) => storage_path(
            'app/public',
        ),
    ],
];
