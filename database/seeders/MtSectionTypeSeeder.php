<?php

namespace Database\Seeders;

use App\Models\MtSectionType;
use Illuminate\Database\Seeder;

/**
 * Seeder para a tabela mt_section_types.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class MtSectionTypeSeeder extends Seeder
{
    /**
     * Executa o seeder.
     *
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Texto',
                'description' => 'Secção de texto',
                'has_details' => false,
            ],
            [
                'name' => 'LP',
                'description' => 'Secção de LP',
                'has_details' => true,
            ],
            [
                'name' => 'EP',
                'description' => 'Secção de EP',
                'has_details' => true,
            ],
            [
                'name' => 'Música',
                'description' => 'Secção de Música',
                'has_details' => true,
            ],
        ];

        foreach ($types as $type) {
            MtSectionType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
