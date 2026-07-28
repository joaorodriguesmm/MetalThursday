<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Executa os seeders de dados de referência da aplicação.
 *
 * O nome `DatabaseSeeder` permanece em inglês por corresponder à convenção
 * utilizada pelo Laravel.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class DatabaseSeeder extends Seeder
{
    /**
     * Executa os seeders pela ordem necessária.
     *
     * O nome `run` permanece em inglês por corresponder ao método
     * convencional dos seeders do Laravel.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function run(): void
    {
        $this->call(
            [
                OrigemGeograficaSeeder::class,
                PermissaoEmailSeeder::class,
                TipoSeccaoSeeder::class,
            ],
        );
    }
}
