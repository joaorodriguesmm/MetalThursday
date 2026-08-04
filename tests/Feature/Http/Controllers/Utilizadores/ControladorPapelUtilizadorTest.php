<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a alteração administrativa dos papéis dos utilizadores.
 *
 * Os testes confirmam a autorização, a validação, a delegação no serviço, a
 * preservação da suspensão e a invalidação das autenticações.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorPapelUtilizadorTest extends TestCase
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
     * Confirma que um visitante não pode alterar papéis.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function visitante_nao_pode_alterar_papel(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this
            ->patch(
                route(
                    'utilizadores.alterar-papel',
                    $utilizador,
                ),
                [
                    'papel' => PapelUtilizador::Administrador->value,

                    'confirmar_alteracao_papel' => '1',
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
    public function utilizador_comum_e_rejeitado_antes_da_validacao(): void
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
                    'utilizadores.alterar-papel',
                    $utilizadorAfetado,
                ),
                [],
            )
            ->assertForbidden();
    }

    /**
     * Confirma que o superadministrador não pode alterar o próprio papel.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function superadministrador_nao_pode_alterar_o_proprio_papel(): void
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
                    'utilizadores.alterar-papel',
                    $superAdministrador,
                ),
                [
                    'papel' => PapelUtilizador::Administrador->value,

                    'confirmar_alteracao_papel' => '1',
                ],
            )
            ->assertForbidden();

        self::assertTrue(
            $superAdministrador
                ->refresh()
                ->eSuperAdministrador(),
        );
    }

    /**
     * Confirma que o novo papel é obrigatório.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function alteracao_exige_um_papel(): void
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
                    'utilizadores.alterar-papel',
                    $utilizador,
                ),
                [
                    'confirmar_alteracao_papel' => '1',
                ],
            )
            ->assertRedirect(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->assertSessionHasErrorsIn(
                'papel',
                [
                    'papel',
                ],
            );

        self::assertSame(
            PapelUtilizador::Utilizador,
            $utilizador
                ->refresh()
                ->papel,
        );
    }

    /**
     * Confirma que um papel desconhecido é rejeitado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_um_papel_desconhecido(): void
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
                    'utilizadores.alterar-papel',
                    $utilizador,
                ),
                [
                    'papel' => 'editor',

                    'confirmar_alteracao_papel' => '1',
                ],
            )
            ->assertRedirect(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->assertSessionHasErrorsIn(
                'papel',
                [
                    'papel',
                ],
            );
    }

    /**
     * Confirma que a alteração exige confirmação explícita.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function alteracao_exige_confirmacao_explicita(): void
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
                    'utilizadores.alterar-papel',
                    $utilizador,
                ),
                [
                    'papel' => PapelUtilizador::Administrador->value,
                ],
            )
            ->assertRedirect(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->assertSessionHasErrorsIn(
                'papel',
                [
                    'confirmar_alteracao_papel',
                ],
            );

        self::assertSame(
            PapelUtilizador::Utilizador,
            $utilizador
                ->refresh()
                ->papel,
        );
    }

    /**
     * Confirma a alteração completa do papel e a invalidação das
     * autenticações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function superadministrador_altera_o_papel_do_utilizador(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->create([
                    'nome' => 'Helena Power',
                ]);

        $outroUtilizador =
            Utilizador::factory()
                ->create();

        $utilizador->setRememberToken(
            'token-original-papel',
        );

        $utilizador->saveOrFail();

        $this->criarSessao(
            $utilizador,
            'sessao-papel-1',
        );

        $this->criarSessao(
            $utilizador,
            'sessao-papel-2',
        );

        $this->criarSessao(
            $outroUtilizador,
            'sessao-outro-utilizador',
        );

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->patch(
                route(
                    'utilizadores.alterar-papel',
                    $utilizador,
                ),
                [
                    'papel' => '  ADMINISTRADOR  ',

                    'confirmar_alteracao_papel' => '1',
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
                'O papel de Helena Power foi alterado para Administrador com sucesso.',
            );

        $utilizador->refresh();

        self::assertSame(
            PapelUtilizador::Administrador,
            $utilizador->papel,
        );

        self::assertNotSame(
            'token-original-papel',
            $utilizador->getRememberToken(),
        );

        self::assertSame(
            0,
            DB::table(
                'sessoes',
            )
                ->where(
                    'user_id',
                    $utilizador->getKey(),
                )
                ->count(),
        );

        self::assertSame(
            1,
            DB::table(
                'sessoes',
            )
                ->where(
                    'user_id',
                    $outroUtilizador->getKey(),
                )
                ->count(),
        );

        $this->assertDatabaseHas(
            'registos_papel_utilizadores',
            [
                'utilizador_id' => $utilizador->getKey(),

                'papel_anterior' => PapelUtilizador::Utilizador->value,

                'papel_novo' => PapelUtilizador::Administrador->value,

                'responsavel_id' => $superAdministrador->getKey(),
            ],
        );
    }

    /**
     * Confirma que a alteração do papel preserva a suspensão atual.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function altera_o_papel_de_utilizador_suspenso(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Administrador,
                )
                ->suspensoPor(
                    $superAdministrador,
                    'Suspensão preservada durante a alteração.',
                )
                ->create();

        $momentoSuspensao =
            $utilizador->suspenso_em;

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->patch(
                route(
                    'utilizadores.alterar-papel',
                    $utilizador,
                ),
                [
                    'papel' => PapelUtilizador::Utilizador->value,

                    'confirmar_alteracao_papel' => '1',
                ],
            )
            ->assertRedirect(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            );

        $utilizador->refresh();

        self::assertSame(
            PapelUtilizador::Utilizador,
            $utilizador->papel,
        );

        self::assertTrue(
            $utilizador->estaSuspenso(),
        );

        self::assertTrue(
            $utilizador
                ->suspenso_em
                ?->equalTo(
                    $momentoSuspensao,
                )
                ?? false,
        );

        self::assertSame(
            'Suspensão preservada durante a alteração.',
            $utilizador->motivo_suspensao,
        );

        self::assertSame(
            (int) $superAdministrador->getKey(),
            $utilizador->suspenso_por_id,
        );
    }

    /**
     * Confirma que uma alteração sem efeito devolve o erro do serviço sem
     * invalidar as autenticações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function papel_ja_atribuido_e_rejeitado_sem_efeitos_colaterais(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Administrador,
                )
                ->create();

        $utilizador->setRememberToken(
            'token-preservado-papel',
        );

        $utilizador->saveOrFail();

        $this->criarSessao(
            $utilizador,
            'sessao-preservada-papel',
        );

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
                    'utilizadores.alterar-papel',
                    $utilizador,
                ),
                [
                    'papel' => PapelUtilizador::Administrador->value,

                    'confirmar_alteracao_papel' => '1',
                ],
            )
            ->assertRedirect(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            )
            ->assertSessionHasErrorsIn(
                'papel',
                [
                    'papel',
                ],
            );

        $utilizador->refresh();

        self::assertSame(
            PapelUtilizador::Administrador,
            $utilizador->papel,
        );

        self::assertSame(
            'token-preservado-papel',
            $utilizador->getRememberToken(),
        );

        self::assertTrue(
            DB::table(
                'sessoes',
            )
                ->where(
                    'id',
                    'sessao-preservada-papel',
                )
                ->exists(),
        );

        $this->assertDatabaseCount(
            'registos_papel_utilizadores',
            0,
        );
    }

    /**
     * Confirma que a remoção do papel de outro superadministrador mantém o
     * responsável como superadministrador ativo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function alteracao_de_outro_superadministrador_preserva_um_ativo(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador =
            $this->criarSuperAdministrador();

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->patch(
                route(
                    'utilizadores.alterar-papel',
                    $utilizador,
                ),
                [
                    'papel' => PapelUtilizador::Administrador->value,

                    'confirmar_alteracao_papel' => '1',
                ],
            )
            ->assertRedirect(
                route(
                    'utilizadores.detalhes',
                    $utilizador,
                ),
            );

        self::assertSame(
            1,
            Utilizador::query()
                ->where(
                    'papel',
                    PapelUtilizador::SuperAdministrador->value,
                )
                ->whereNull(
                    'suspenso_em',
                )
                ->count(),
        );

        self::assertTrue(
            $responsavel
                ->refresh()
                ->eSuperAdministrador(),
        );

        self::assertSame(
            PapelUtilizador::Administrador,
            $utilizador
                ->refresh()
                ->papel,
        );
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

    /**
     * Cria uma sessão técnica associada a um utilizador.
     *
     * @param  Utilizador  $utilizador  Utilizador associado.
     * @param  string  $identificador  Identificador da sessão.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarSessao(
        Utilizador $utilizador,
        string $identificador,
    ): void {
        DB::table(
            'sessoes',
        )->insert([
            'id' => $identificador,

            'user_id' => $utilizador->getKey(),

            'ip_address' => '127.0.0.1',

            'user_agent' => 'PHPUnit',

            'payload' => 'conteudo-'.$identificador,

            'last_activity' => now()->timestamp,
        ]);
    }
}
