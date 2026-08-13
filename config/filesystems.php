<?php

declare(strict_types=1);

/**
 * Define os sistemas de ficheiros utilizados pela aplicação.
 *
 * Os nomes das chaves e drivers permanecem em inglês por corresponderem aos
 * contratos internos de configuração do Laravel e do Flysystem. Os nomes dos
 * discos definidos pelo MetalThursday utilizam português.
 *
 * @return array<string, mixed> Configurações dos sistemas de ficheiros.
 *
 * @since 1.0.0
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Disco predefinido
    |--------------------------------------------------------------------------
    */

    'default' => env(
        'FILESYSTEM_DISK',
        'privado',
    ),

    /*
    |--------------------------------------------------------------------------
    | Discos de armazenamento
    |--------------------------------------------------------------------------
    */

    'disks' => [
        /*
         * Disco destinado a ficheiros que não podem ser acedidos
         * diretamente através da Internet.
         */
        'privado' => [
            'driver' => 'local',

            'root' => storage_path(
                'app/private',
            ),

            'throw' => true,

            'report' => false,
        ],

        /*
         * Disco destinado a ficheiros publicamente acessíveis através da
         * ligação simbólica public/storage.
         */
        'publico' => [
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

            'throw' => true,

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
