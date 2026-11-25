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

        DB::transaction(static function () use ($registos): void {
            DB::table('permissoes_email')->upsert(
                $registos,
                ['identificador'],
                ['nome', 'descricao', 'ordem', 'updated_at'],
            );

            $permissaoAntiga = DB::table('permissoes_email')
                ->where('identificador', 'todas_interacoes')
                ->value('id');

            if ($permissaoAntiga === null) {
                return;
            }

            $permissaoPessoal = DB::table('permissoes_email')
                ->where(
                    'identificador',
                    IdentificadorPermissaoEmail::InteracoesNasMinhasPublicacoes->value,
                )
                ->value('id');

            if (! is_numeric($permissaoPessoal)) {
                throw new \LogicException('Não existe a permissão de interações pessoais.');
            }

            DB::table('permissao_email_utilizador')
                ->where('permissao_email_id', $permissaoAntiga)
                ->orderBy('utilizador_id')
                ->pluck('utilizador_id')
                ->each(static function (mixed $identificadorUtilizador) use ($permissaoPessoal): void {
                    DB::table('permissao_email_utilizador')->insertOrIgnore([
                        'utilizador_id' => $identificadorUtilizador,
                        'permissao_email_id' => $permissaoPessoal,
                    ]);
                });

            DB::table('permissoes_email')
                ->where('id', $permissaoAntiga)
                ->delete();
        });
    }
}
