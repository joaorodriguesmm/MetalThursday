<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Regista as permissões de comunicação por correio eletrónico.
 *
 * O catálogo funcional das permissões é definido pela enumeração
 * IdentificadorPermissaoEmail. Este seeder limita-se a materializar esse
 * catálogo na base de dados.
 *
 * O nome `Seeder` permanece em inglês por corresponder à convenção utilizada
 * pelo Laravel.
 *
 * @since 1.0.0
 */
final class PermissaoEmailSeeder extends Seeder
{
    /**
     * Regista ou atualiza as permissões.
     *
     * O nome `run` permanece em inglês por corresponder ao método
     * convencional dos seeders do Laravel.
     *
     * @since 1.0.0
     */
    public function run(): void
    {
        $agora = now();

        /** @var list<array{
         *     identificador: string,
         *     nome: string,
         *     descricao: string,
         *     ordem: int,
         *     created_at: mixed,
         *     updated_at: mixed
         * }> $registos
         */
        $registos = array_map(
            static fn (
                IdentificadorPermissaoEmail $permissao,
            ): array => [
                'identificador' => $permissao->value,

                'nome' => $permissao->nome(),

                'descricao' => $permissao->descricao(),

                'ordem' => $permissao->ordem(),

                'created_at' => $agora,

                'updated_at' => $agora,
            ],
            IdentificadorPermissaoEmail::cases(),
        );

        DB::table(
            'permissoes_email',
        )->upsert(
            $registos,
            [
                'identificador',
            ],
            [
                'nome',
                'descricao',
                'ordem',
                'updated_at',
            ],
        );
    }
}
