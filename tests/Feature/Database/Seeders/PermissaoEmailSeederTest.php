<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a materialização do catálogo das permissões de e-mail.
 *
 * @since 2.0.0
 */
final class PermissaoEmailSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o seeder materializa integralmente o catálogo definido
     * pela enumeração.
     *
     * @since 2.0.0
     */
    #[Test]
    public function materializa_catalogo_das_permissoes(): void
    {
        app(
            PermissaoEmailSeeder::class,
        )->run();

        $esperadas = array_map(
            static fn (
                IdentificadorPermissaoEmail $permissao,
            ): array => [
                'identificador' => $permissao->value,

                'nome' => $permissao->nome(),

                'descricao' => $permissao->descricao(),

                'ordem' => $permissao->ordem(),
            ],
            IdentificadorPermissaoEmail::cases(),
        );

        $persistidas = DB::table(
            'permissoes_email',
        )
            ->orderBy(
                'ordem',
            )
            ->get([
                'identificador',
                'nome',
                'descricao',
                'ordem',
            ])
            ->map(
                static fn (object $permissao): array => [
                    'identificador' => (string) $permissao->identificador,

                    'nome' => (string) $permissao->nome,

                    'descricao' => (string) $permissao->descricao,

                    'ordem' => (int) $permissao->ordem,
                ],
            )
            ->all();

        self::assertSame(
            $esperadas,
            $persistidas,
        );
    }

    /**
     * Confirma que execuções repetidas atualizam os metadados sem duplicar
     * uma permissão existente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualiza_catalogo_sem_duplicar_permissoes(): void
    {
        $seeder = app(
            PermissaoEmailSeeder::class,
        );

        $seeder->run();

        $permissaoGlobal =
            IdentificadorPermissaoEmail::TodasNotificacoes;

        $identificadorPersistido = DB::table(
            'permissoes_email',
        )
            ->where(
                'identificador',
                $permissaoGlobal->value,
            )
            ->value(
                'id',
            );

        self::assertNotNull(
            $identificadorPersistido,
        );

        DB::table(
            'permissoes_email',
        )
            ->where(
                'identificador',
                $permissaoGlobal->value,
            )
            ->update([
                'nome' => 'Nome incorreto',

                'descricao' => 'Descrição incorreta',

                'ordem' => 200,
            ]);

        $seeder->run();

        self::assertSame(
            count(
                IdentificadorPermissaoEmail::cases(),
            ),
            DB::table(
                'permissoes_email',
            )->count(),
        );

        $permissaoAtualizada = DB::table(
            'permissoes_email',
        )
            ->where(
                'identificador',
                $permissaoGlobal->value,
            )
            ->first([
                'id',
                'nome',
                'descricao',
                'ordem',
            ]);

        self::assertNotNull(
            $permissaoAtualizada,
        );

        self::assertSame(
            (int) $identificadorPersistido,
            (int) $permissaoAtualizada->id,
        );

        self::assertSame(
            $permissaoGlobal->nome(),
            $permissaoAtualizada->nome,
        );

        self::assertSame(
            $permissaoGlobal->descricao(),
            $permissaoAtualizada->descricao,
        );

        self::assertSame(
            $permissaoGlobal->ordem(),
            (int) $permissaoAtualizada->ordem,
        );
    }

    /**
     * Converte a antiga permissão global de interações na nova escolha pessoal
     * e preserva a associação mesmo quando a permissão nova já existia.
     */
    #[Test]
    public function converte_permissao_antiga_sem_perder_as_preferencias(): void
    {
        $seeder = app(PermissaoEmailSeeder::class);
        $seeder->run();

        $permissaoPessoal = DB::table('permissoes_email')
            ->where(
                'identificador',
                IdentificadorPermissaoEmail::InteracoesNasMinhasPublicacoes->value,
            )
            ->value('id');

        self::assertNotNull($permissaoPessoal);

        $permissaoAntiga = DB::table('permissoes_email')->insertGetId([
            'identificador' => 'todas_interacoes',
            'nome' => 'Todas as interações',
            'descricao' => 'Permissão antiga.',
            'ordem' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $utilizadorA = Utilizador::factory()->create();
        $utilizadorB = Utilizador::factory()->create();

        DB::table('permissao_email_utilizador')->insert([
            [
                'utilizador_id' => $utilizadorA->id,
                'permissao_email_id' => $permissaoAntiga,
            ],
            [
                'utilizador_id' => $utilizadorB->id,
                'permissao_email_id' => $permissaoAntiga,
            ],
            [
                'utilizador_id' => $utilizadorB->id,
                'permissao_email_id' => $permissaoPessoal,
            ],
        ]);

        $seeder->run();

        $this->assertDatabaseMissing('permissoes_email', [
            'identificador' => 'todas_interacoes',
        ]);

        foreach ([$utilizadorA, $utilizadorB] as $utilizador) {
            $this->assertDatabaseHas('permissao_email_utilizador', [
                'utilizador_id' => $utilizador->id,
                'permissao_email_id' => $permissaoPessoal,
            ]);
        }

        self::assertSame(
            2,
            DB::table('permissao_email_utilizador')
                ->where('permissao_email_id', $permissaoPessoal)
                ->count(),
        );
    }
}
