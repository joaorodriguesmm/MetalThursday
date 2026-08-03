<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a autorização dos pedidos dos dados principais das edições.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class PedidoEdicaoRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que um utilizador comum é rejeitado antes da validação de uma
     * criação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_criacao_sem_autorizacao_antes_da_validacao(): void
    {
        $utilizador =
            $this->criarUtilizadorComum();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'edicoes.guardar',
                ),
                $this->dadosInvalidos(),
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'edicoes',
            0,
        );
    }

    /**
     * Confirma que um administrador autorizado alcança a validação da
     * criação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function administrador_recebe_erros_de_validacao_na_criacao(): void
    {
        $administrador =
            $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'edicoes.guardar',
                ),
                $this->dadosInvalidos(),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nome',
                'data_inicio',
                'data_fim',
            ]);
    }

    /**
     * Confirma que um utilizador comum é rejeitado antes da validação de uma
     * atualização.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_atualizacao_sem_autorizacao_antes_da_validacao(): void
    {
        $edicao = Edicao::factory()
            ->create();

        $nomeOriginal =
            $edicao->nome;

        $utilizador =
            $this->criarUtilizadorComum();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->patchJson(
                route(
                    'edicoes.atualizar',
                    $edicao,
                ),
                $this->dadosInvalidos(),
            )
            ->assertForbidden();

        self::assertSame(
            $nomeOriginal,
            $edicao
                ->refresh()
                ->nome,
        );
    }

    /**
     * Confirma que um administrador autorizado alcança a validação da
     * atualização.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function administrador_recebe_erros_de_validacao_na_atualizacao(): void
    {
        $edicao = Edicao::factory()
            ->create();

        $administrador =
            $this->criarAdministrador();

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
                $this->dadosInvalidos(),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nome',
                'data_inicio',
                'data_fim',
            ]);
    }

    /**
     * Cria um utilizador comum.
     *
     * @return Utilizador Utilizador criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarUtilizadorComum(): Utilizador
    {
        return Utilizador::factory()
            ->create([
                'papel' => PapelUtilizador::Utilizador,
            ]);
    }

    /**
     * Cria um utilizador com privilégios administrativos.
     *
     * O papel é fornecido diretamente para não depender do estado
     * personalizado ainda não auditado da factory dos utilizadores.
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

    /**
     * Obtém dados deliberadamente inválidos.
     *
     * @return array<string, mixed> Dados inválidos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function dadosInvalidos(): array
    {
        return [
            'nome' => [],

            'data_inicio' => 'data-invalida',

            'data_fim' => 'data-invalida',
        ];
    }
}
