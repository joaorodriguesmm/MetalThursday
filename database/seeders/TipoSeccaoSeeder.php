<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Regista os tipos de secção disponíveis numa MetalThursday.
 *
 * O nome `Seeder` permanece em inglês por corresponder à convenção utilizada
 * pelo Laravel.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class TipoSeccaoSeeder extends Seeder
{
    /**
     * Tipos de secção disponibilizados pela aplicação.
     *
     * @var list<array{
     *     identificador: string,
     *     nome: string,
     *     descricao: string,
     *     exige_detalhes: bool,
     *     ordem: int
     * }>
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private const TIPOS_SECCAO = [
        [
            'identificador' => 'texto',

            'nome' => 'Texto',

            'descricao' => 'Secção destinada à apresentação de conteúdo textual.',

            'exige_detalhes' => false,

            'ordem' => 1,
        ],
        [
            'identificador' => 'album',

            'nome' => 'LP',

            'descricao' => 'Secção destinada à apresentação de um álbum de longa duração.',

            'exige_detalhes' => true,

            'ordem' => 2,
        ],
        [
            'identificador' => 'ep',

            'nome' => 'EP',

            'descricao' => 'Secção destinada à apresentação de um lançamento de duração intermédia.',

            'exige_detalhes' => true,

            'ordem' => 3,
        ],
        [
            'identificador' => 'musica',

            'nome' => 'Música',

            'descricao' => 'Secção destinada à apresentação de uma música individual.',

            'exige_detalhes' => true,

            'ordem' => 4,
        ],
    ];

    /**
     * Regista ou atualiza os tipos de secção.
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
        $agora = now();

        /** @var list<array{
         *     identificador: string,
         *     nome: string,
         *     descricao: string,
         *     exige_detalhes: bool,
         *     ordem: int,
         *     created_at: mixed,
         *     updated_at: mixed
         * }> $registos
         */
        $registos = array_map(
            static fn (
                array $tipoSeccao,
            ): array => [
                ...$tipoSeccao,

                'created_at' => $agora,

                'updated_at' => $agora,
            ],
            self::TIPOS_SECCAO,
        );

        DB::table(
            'tipos_seccao',
        )->upsert(
            $registos,
            [
                'identificador',
            ],
            [
                'nome',
                'descricao',
                'exige_detalhes',
                'ordem',
                'updated_at',
            ],
        );
    }
}
