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
 * Testa o encerramento administrativo das sessões dos utilizadores.
 *
 * Os testes confirmam a autorização, a confirmação explícita, a preservação
 * do estado do acesso, a rotação do token persistente e a eliminação seletiva
 * das sessões.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorSessoesUtilizadorTest extends TestCase
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
     * Confirma que um visitante não pode encerrar sessões.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function visitante_nao_pode_encerrar_sessoes(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this
            ->delete(
                route(
                    'utilizadores.encerrar-sessoes',
                    $utilizador,
                ),
                [
                    'confirmar_encerramento_sessoes' => '1',
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
            ->delete(
                route(
                    'utilizadores.encerrar-sessoes',
                    $utilizadorAfetado,
                ),
                [],
            )
            ->assertForbidden();
    }

    /**
     * Confirma que o superadministrador não pode encerrar as próprias
     * sessões através da área administrativa.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function superadministrador_nao_pode_encerrar_as_proprias_sessoes(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $this->criarSessao(
            $superAdministrador,
            'sessao-superadministrador',
        );

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->delete(
                route(
                    'utilizadores.encerrar-sessoes',
                    $superAdministrador,
                ),
                [
                    'confirmar_encerramento_sessoes' => '1',
                ],
            )
            ->assertForbidden();

        self::assertTrue(
            DB::table(
                'sessoes',
            )
                ->where(
                    'id',
                    'sessao-superadministrador',
                )
                ->exists(),
        );
    }

    /**
     * Confirma que a operação exige confirmação explícita.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function encerramento_exige_confirmacao_explicita(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->create();

        $this->criarSessao(
            $utilizador,
            'sessao-preservada',
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
            ->delete(
                route(
                    'utilizadores.encerrar-sessoes',
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
                'sessoes',
                [
                    'confirmar_encerramento_sessoes',
                ],
            );

        self::assertTrue(
            DB::table(
                'sessoes',
            )
                ->where(
                    'id',
                    'sessao-preservada',
                )
                ->exists(),
        );
    }

    /**
     * Confirma o encerramento seletivo das sessões de um utilizador ativo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function encerra_sessoes_de_utilizador_ativo(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->create([
                    'nome' => 'Utilizador Ativo',
                ]);

        $outroUtilizador =
            Utilizador::factory()
                ->create();

        $this->criarSessao(
            $utilizador,
            'sessao-utilizador-1',
        );

        $this->criarSessao(
            $utilizador,
            'sessao-utilizador-2',
        );

        $this->criarSessao(
            $outroUtilizador,
            'sessao-outro-utilizador',
        );

        $tokenPersistenteAnterior =
            $utilizador->getRememberToken();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->delete(
                route(
                    'utilizadores.encerrar-sessoes',
                    $utilizador,
                ),
                [
                    'confirmar_encerramento_sessoes' => '1',
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
                'Foram encerradas 2 sessões de Utilizador Ativo e as autenticações persistentes foram invalidadas.',
            );

        $utilizador->refresh();

        self::assertTrue(
            $utilizador->temAcessoAtivo(),
        );

        self::assertNotSame(
            $tokenPersistenteAnterior,
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

        $this->assertDatabaseCount(
            'registos_acesso_utilizadores',
            0,
        );
    }

    /**
     * Confirma que as sessões de um utilizador suspenso também podem ser
     * encerradas sem alterar a suspensão.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function encerra_sessoes_de_utilizador_suspenso(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->suspensoPor(
                    $superAdministrador,
                    'Suspensão preservada.',
                )
                ->create([
                    'nome' => 'Utilizador Suspenso',
                ]);

        $this->criarSessao(
            $utilizador,
            'sessao-utilizador-suspenso',
        );

        $momentoSuspensao =
            $utilizador->suspenso_em;

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->delete(
                route(
                    'utilizadores.encerrar-sessoes',
                    $utilizador,
                ),
                [
                    'confirmar_encerramento_sessoes' => '1',
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
                'Foi encerrada 1 sessão de Utilizador Suspenso e as autenticações persistentes foram invalidadas.',
            );

        $utilizador->refresh();

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
            'Suspensão preservada.',
            $utilizador->motivo_suspensao,
        );

        self::assertSame(
            (int) $superAdministrador->getKey(),
            $utilizador->suspenso_por_id,
        );

        self::assertFalse(
            DB::table(
                'sessoes',
            )
                ->where(
                    'id',
                    'sessao-utilizador-suspenso',
                )
                ->exists(),
        );
    }

    /**
     * Confirma que a inexistência de sessões continua a invalidar a
     * autenticação persistente.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function invalida_token_quando_nao_existem_sessoes(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->create([
                    'nome' => 'Utilizador Sem Sessões',
                ]);

        $tokenPersistenteAnterior =
            $utilizador->getRememberToken();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->delete(
                route(
                    'utilizadores.encerrar-sessoes',
                    $utilizador,
                ),
                [
                    'confirmar_encerramento_sessoes' => '1',
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
                'As autenticações persistentes de Utilizador Sem Sessões foram invalidadas. Não existiam sessões ativas para encerrar.',
            );

        $utilizador->refresh();

        self::assertNotSame(
            $tokenPersistenteAnterior,
            $utilizador->getRememberToken(),
        );

        self::assertTrue(
            $utilizador->temAcessoAtivo(),
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
