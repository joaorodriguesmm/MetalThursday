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
 * Testa a autorização dos pedidos complementares das edições.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class PedidosComplementaresEdicaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a ligação da compilação não é validada para um utilizador
     * sem autorização.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_ligacao_compilacao_sem_autorizacao_antes_da_validacao(): void
    {
        $edicao = Edicao::factory()
            ->create();

        $utilizador = $this->criarUtilizadorComum();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->patchJson(
                route(
                    'edicoes.ligacao-compilacao.atualizar',
                    $edicao,
                ),
                [
                    'ligacao_compilacao' => [],
                ],
            )
            ->assertForbidden();
    }

    /**
     * Confirma que um administrador autorizado alcança a validação da ligação
     * da compilação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function administrador_recebe_erro_de_validacao_na_ligacao_compilacao(): void
    {
        $edicao = Edicao::factory()
            ->create();

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
                    'ligacao_compilacao' => [],
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'ligacao_compilacao',
            ]);
    }

    /**
     * Confirma que as músicas favoritas não são validadas para um utilizador
     * sem autorização.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_musicas_favoritas_sem_autorizacao_antes_da_validacao(): void
    {
        $edicao = Edicao::factory()
            ->create();

        $utilizador = $this->criarUtilizadorComum();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'edicoes.musicas-favoritas.guardar',
                    $edicao,
                ),
                [
                    'musicas_favoritas' => 'invalido',
                ],
            )
            ->assertForbidden();
    }

    /**
     * Confirma que um administrador autorizado alcança a validação das
     * músicas favoritas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function administrador_recebe_erro_de_validacao_nas_musicas_favoritas(): void
    {
        $edicao = Edicao::factory()
            ->create();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'edicoes.musicas-favoritas.guardar',
                    $edicao,
                ),
                [
                    'musicas_favoritas' => 'invalido',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'musicas_favoritas',
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
