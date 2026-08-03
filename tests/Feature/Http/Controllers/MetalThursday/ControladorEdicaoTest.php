<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa as operações HTTP de atualização e eliminação das edições.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorEdicaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que um administrador atualiza os dados principais da edição.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function administrador_atualiza_edicao(): void
    {
        $edicao = $this->criarEdicao();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->patchJson(
                route(
                    'edicoes.atualizar',
                    $edicao,
                ),
                [
                    'nome' => '  Edição   atualizada  ',

                    'data_inicio' => '2026-02-01',

                    'data_fim' => '2026-02-28',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'edicao.nome',
                'Edição atualizada',
            )
            ->assertJsonPath(
                'edicao.data_inicio',
                '2026-02-01',
            )
            ->assertJsonPath(
                'edicao.data_fim',
                '2026-02-28',
            );

        $this->assertDatabaseHas(
            'edicoes',
            [
                'id' => $edicao->getKey(),

                'nome' => 'Edição atualizada',

                'data_inicio' => '2026-02-01',

                'data_fim' => '2026-02-28',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que um utilizador comum não elimina uma edição.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_comum_nao_elimina_edicao(): void
    {
        $edicao = $this->criarEdicao();

        $utilizador = Utilizador::factory()
            ->create([
                'papel' => PapelUtilizador::Utilizador,
            ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->deleteJson(
                route(
                    'edicoes.eliminar',
                    $edicao,
                ),
            )
            ->assertForbidden();

        $this->assertNotSoftDeleted(
            'edicoes',
            [
                'id' => $edicao->getKey(),
            ],
        );
    }

    /**
     * Confirma que uma edição sem MetalThursdays pode ser eliminada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function administrador_elimina_edicao_vazia(): void
    {
        $edicao = $this->criarEdicao();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->deleteJson(
                route(
                    'edicoes.eliminar',
                    $edicao,
                ),
            )
            ->assertNoContent();

        $this->assertSoftDeleted(
            'edicoes',
            [
                'id' => $edicao->getKey(),
            ],
        );
    }

    /**
     * Confirma que uma edição com MetalThursdays não pode ser eliminada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function administrador_nao_elimina_edicao_com_metal_thursdays(): void
    {
        $edicao = $this->criarEdicao();

        MetalThursday::factory()
            ->comEdicao(
                $edicao,
            )
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-08',
                ),
            )
            ->create();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->deleteJson(
                route(
                    'edicoes.eliminar',
                    $edicao,
                ),
            )
            ->assertConflict()
            ->assertJsonPath(
                'mensagem',
                'A edição não pode ser eliminada enquanto possuir MetalThursdays.',
            );

        $this->assertNotSoftDeleted(
            'edicoes',
            [
                'id' => $edicao->getKey(),
            ],
        );
    }

    /**
     * Confirma que um administrador atualiza a ligação da compilação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function administrador_atualiza_ligacao_compilacao(): void
    {
        $edicao = $this->criarEdicao();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->patchJson(
                route(
                    'edicoes.ligacao-compilacao.atualizar',
                    $edicao,
                ),
                [
                    'ligacao_compilacao' => 'https://example.com/compilacao',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'ligacao_compilacao',
                'https://example.com/compilacao',
            );

        $this->assertDatabaseHas(
            'edicoes',
            [
                'id' => $edicao->getKey(),

                'ligacao_compilacao' => 'https://example.com/compilacao',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Cria uma edição com um período conhecido.
     *
     * @return Edicao Edição criada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarEdicao(): Edicao
    {
        return Edicao::factory()
            ->comNome(
                'Edição de janeiro',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();
    }

    /**
     * Cria um utilizador com privilégios administrativos.
     *
     * @return Utilizador Administrador criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarAdministrador(): Utilizador
    {
        return Utilizador::factory()
            ->create([
                'papel' => PapelUtilizador::Administrador,
            ]);
    }
}
