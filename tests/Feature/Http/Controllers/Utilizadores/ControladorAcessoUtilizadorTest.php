<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Utilizadores;

use App\Enumeracoes\AcaoAcessoUtilizador;
use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa as operações HTTP de suspensão e reativação dos utilizadores.
 *
 * As garantias transacionais, o encerramento das sessões e as regras
 * concorrentes são testados integralmente no serviço de acesso. Estes testes
 * confirmam a autorização, validação, delegação e persistência através do
 * fluxo HTTP real.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorAcessoUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara cada teste sem depender dos ficheiros produzidos pelo Vite.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Confirma que um visitante não pode suspender um utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function visitante_nao_pode_suspender_utilizador(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this
            ->patch(
                route(
                    'utilizadores.suspender',
                    $utilizador,
                ),
                [
                    'motivo' => 'Suspensão administrativa.',
                ],
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            );
    }

    /**
     * Confirma que um utilizador comum é rejeitado antes da validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_comum_e_rejeitado_antes_da_validacao_da_suspensao(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $utilizadorAfetado =
            Utilizador::factory()
                ->create();

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->patch(
                route(
                    'utilizadores.suspender',
                    $utilizadorAfetado,
                ),
                [],
            )
            ->assertForbidden();
    }

    /**
     * Confirma que o superadministrador não pode suspender o próprio acesso.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function superadministrador_nao_pode_suspender_o_proprio_acesso(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->patch(
                route(
                    'utilizadores.suspender',
                    $superAdministrador,
                ),
                [
                    'motivo' => 'Tentativa de autossuspensão.',
                ],
            )
            ->assertForbidden();

        self::assertTrue(
            $superAdministrador
                ->refresh()
                ->temAcessoAtivo(),
        );
    }

    /**
     * Confirma que o motivo da suspensão é obrigatório.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function suspensao_exige_um_motivo(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->create();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->from(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->patch(
                route(
                    'utilizadores.suspender',
                    $utilizador,
                ),
                [],
            )
            ->assertRedirect(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->assertSessionHasErrorsIn(
                'suspensao',
                [
                    'motivo',
                ],
            );

        self::assertTrue(
            $utilizador
                ->refresh()
                ->temAcessoAtivo(),
        );
    }

    /**
     * Confirma que um superadministrador suspende um utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function superadministrador_suspende_utilizador(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->create();

        $tokenPersistenteAnterior =
            $utilizador->getRememberToken();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->patch(
                route(
                    'utilizadores.suspender',
                    $utilizador,
                ),
                [
                    'motivo' => "  Incumprimento \n reiterado das regras.  ",
                ],
            )
            ->assertRedirect(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->assertSessionHas(
                'sucesso',
                sprintf(
                    'O acesso de %s foi suspenso com sucesso.',
                    $utilizador->nome,
                ),
            );

        $utilizador->refresh();

        self::assertTrue(
            $utilizador->estaSuspenso(),
        );

        self::assertSame(
            'Incumprimento reiterado das regras.',
            $utilizador->motivo_suspensao,
        );

        self::assertSame(
            (int) $superAdministrador->getKey(),
            $utilizador->suspenso_por_id,
        );

        self::assertNotSame(
            $tokenPersistenteAnterior,
            $utilizador->getRememberToken(),
        );

        $this->assertDatabaseHas(
            'registos_acesso_utilizadores',
            [
                'utilizador_id' => $utilizador->getKey(),

                'acao' => AcaoAcessoUtilizador::Suspensao->value,

                'motivo' => 'Incumprimento reiterado das regras.',

                'responsavel_id' => $superAdministrador->getKey(),
            ],
        );
    }

    /**
     * Confirma que um utilizador já suspenso não pode voltar a ser suspenso
     * através do mesmo formulário.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_ja_suspenso_nao_pode_ser_suspenso_novamente(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->suspensoPor(
                    $superAdministrador,
                )
                ->create();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->patch(
                route(
                    'utilizadores.suspender',
                    $utilizador,
                ),
                [
                    'motivo' => 'Nova suspensão.',
                ],
            )
            ->assertForbidden();
    }

    /**
     * Confirma que a reativação exige confirmação explícita.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function reativacao_exige_confirmacao_explicita(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->suspensoPor(
                    $superAdministrador,
                )
                ->create();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->from(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->patch(
                route(
                    'utilizadores.reativar',
                    $utilizador,
                ),
                [],
            )
            ->assertRedirect(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->assertSessionHasErrorsIn(
                'reativacao',
                [
                    'confirmar_reativacao',
                ],
            );

        self::assertTrue(
            $utilizador
                ->refresh()
                ->estaSuspenso(),
        );
    }

    /**
     * Confirma que um superadministrador reativa um utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function superadministrador_reativa_utilizador(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->suspensoPor(
                    $superAdministrador,
                    'Suspensão inicial.',
                )
                ->create();

        $tokenPersistenteAnterior =
            $utilizador->getRememberToken();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->patch(
                route(
                    'utilizadores.reativar',
                    $utilizador,
                ),
                [
                    'confirmar_reativacao' => '1',
                ],
            )
            ->assertRedirect(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->assertSessionHas(
                'sucesso',
                sprintf(
                    'O acesso de %s foi reativado com sucesso.',
                    $utilizador->nome,
                ),
            );

        $utilizador->refresh();

        self::assertTrue(
            $utilizador->temAcessoAtivo(),
        );

        self::assertNull(
            $utilizador->suspenso_em,
        );

        self::assertNull(
            $utilizador->motivo_suspensao,
        );

        self::assertNull(
            $utilizador->suspenso_por_id,
        );

        self::assertNotSame(
            $tokenPersistenteAnterior,
            $utilizador->getRememberToken(),
        );

        $this->assertDatabaseHas(
            'registos_acesso_utilizadores',
            [
                'utilizador_id' => $utilizador->getKey(),

                'acao' => AcaoAcessoUtilizador::Reativacao->value,

                'motivo' => null,

                'responsavel_id' => $superAdministrador->getKey(),
            ],
        );
    }

    /**
     * Confirma que um utilizador ativo não pode ser reativado novamente.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_ativo_nao_pode_ser_reativado(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->create();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->patch(
                route(
                    'utilizadores.reativar',
                    $utilizador,
                ),
                [
                    'confirmar_reativacao' => '1',
                ],
            )
            ->assertForbidden();
    }

    /**
     * Cria um superadministrador com acesso ativo.
     *
     * @return Utilizador Superadministrador criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarSuperAdministrador(): Utilizador
    {
        return Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();
    }
}
