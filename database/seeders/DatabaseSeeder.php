<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder da base de dados.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Executa os seeders registados.
     *
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            EmailPermissionSeeder::class,
            MtSectionTypeSeeder::class,
            // UserSeeder::class,
        ]);
    }
}
