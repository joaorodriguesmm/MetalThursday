<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MetalThursday\TipoSeccao;
use Illuminate\Database\Seeder;

/**
 * Regista os tipos de secção disponíveis numa MetalThursday.
 *
 * O nome `Seeder` permanece em inglês por corresponder à convenção utilizada
 * pelo Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class TipoSeccaoSeeder extends Seeder
{
    /**
     * Tipos de secção disponibilizados pela aplicação.
     *
     * @var list<array{
     *     nome: string,
     *     descricao: string,
     *     tem_detalhes: bool
     * }>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TIPOS_SECCAO = [
        [
            'nome' => 'Texto',

            'descricao' => 'Secção destinada à apresentação de conteúdo textual.',

            'tem_detalhes' => false,
        ],
        [
            'nome' => 'LP',

            'descricao' => 'Secção destinada à apresentação de um álbum de longa duração.',

            'tem_detalhes' => true,
        ],
        [
            'nome' => 'EP',

            'descricao' => 'Secção destinada à apresentação de um lançamento de duração intermédia.',

            'tem_detalhes' => true,
        ],
        [
            'nome' => 'Música',

            'descricao' => 'Secção destinada à apresentação de uma música individual.',

            'tem_detalhes' => true,
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
     * @version 2.0.0
     */
    public function run(): void
    {
        foreach (
            self::TIPOS_SECCAO as $tipoSeccao
        ) {
            TipoSeccao::query()->updateOrCreate(
                [
                    'nome' => $tipoSeccao['nome'],
                ],
                [
                    'descricao' => $tipoSeccao['descricao'],

                    'tem_detalhes' => $tipoSeccao['tem_detalhes'],
                ],
            );
        }
    }
}
