<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Notifications\NotificacaoPapelUtilizadorAlterado;
use App\Servicos\Autenticacao\ServicoSessoesUtilizador;
use App\Servicos\Utilizadores\ServicoPapeisUtilizadores;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa a alteração transacional dos papéis dos utilizadores.
 *
 * @since 2.0.0
 */
final class ServicoPapeisUtilizadoresTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Serviço testado.
     *
     * @since 2.0.0
     */
    private ServicoPapeisUtilizadores $servico;

    /**
     * Prepara o serviço antes de cada teste.
     *
     * O nome permanece em inglês por corresponder ao ciclo de vida do
     * PHPUnit.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->servico =
            new ServicoPapeisUtilizadores(
                new ServicoSessoesUtilizador,
            );
    }

    /**
     * Confirma a alteração completa do papel de um utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function altera_o_papel_e_invalida_as_autenticacoes(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Utilizador,
                )
                ->create();

        $utilizador->setRememberToken(
            'token-original-utilizador',
        );

        $utilizador->saveOrFail();

        $this->criarSessao(
            $utilizador,
            'sessao-utilizador-papel',
        );

        $momento =
            CarbonImmutable::parse(
                '2026-08-04 09:15:00',
            );

        $resultado =
            $this
                ->servico
                ->alterar(
                    utilizador: $utilizador,
                    responsavel: $responsavel,
                    papelNovo: PapelUtilizador::Administrador,
                    momento: $momento,
                );

        self::assertSame(
            PapelUtilizador::Administrador,
            $resultado->papel,
        );

        self::assertNotSame(
            'token-original-utilizador',
            $resultado->getRememberToken(),
        );

        self::assertFalse(
            DB::table(
                'sessoes',
            )
                ->where(
                    'id',
                    'sessao-utilizador-papel',
                )
                ->exists(),
        );

        $this->assertDatabaseHas(
            'registos_papel_utilizadores',
            [
                'utilizador_id' => $utilizador->getKey(),
                'papel_anterior' => PapelUtilizador::Utilizador->value,
                'papel_novo' => PapelUtilizador::Administrador->value,
                'responsavel_id' => $responsavel->getKey(),
                'registado_em' => $momento->format(
                    'Y-m-d H:i:s',
                ),
            ],
        );
    }

    /**
     * Confirma que uma alteração de papel concluída é comunicada ao utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function alteracao_de_papel_bem_sucedida_notifica_o_utilizador(): void
    {
        Notification::fake();

        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Utilizador,
                )
                ->create();

        $this
            ->servico
            ->alterar(
                utilizador: $utilizador,
                responsavel: $responsavel,
                papelNovo: PapelUtilizador::Administrador,
            );

        Notification::assertSentTo(
            $utilizador,
            NotificacaoPapelUtilizadorAlterado::class,
            static function (
                NotificacaoPapelUtilizadorAlterado $notificacao,
            ) use (
                $utilizador,
            ): bool {
                $mensagem =
                    $notificacao->toMail(
                        $utilizador,
                    );

                return $mensagem->subject
                    === 'MetalThursday — Papel da conta alterado'
                    && in_array(
                        'Papel anterior: Utilizador.',
                        $mensagem->introLines,
                        true,
                    )
                    && in_array(
                        'Novo papel: Administrador.',
                        $mensagem->introLines,
                        true,
                    );
            },
        );

        Notification::assertCount(
            1,
        );
    }

    /**
     * Confirma que a alteração do papel preserva a suspensão atual.
     *
     * @since 2.0.0
     */
    #[Test]
    public function altera_o_papel_sem_modificar_a_suspensao(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Administrador,
                )
                ->suspensoPor(
                    $responsavel,
                    'Suspensão que deve ser preservada.',
                )
                ->create();

        $momentoSuspensao =
            $utilizador->suspenso_em;

        $resultado =
            $this
                ->servico
                ->alterar(
                    $utilizador,
                    $responsavel,
                    PapelUtilizador::Utilizador,
                );

        self::assertSame(
            PapelUtilizador::Utilizador,
            $resultado->papel,
        );

        self::assertTrue(
            $resultado->estaSuspenso(),
        );

        self::assertTrue(
            $resultado
                ->suspenso_em
                ?->equalTo(
                    $momentoSuspensao,
                )
                ?? false,
        );

        self::assertSame(
            'Suspensão que deve ser preservada.',
            $resultado->motivo_suspensao,
        );

        self::assertSame(
            (int) $responsavel->getKey(),
            $resultado->suspenso_por_id,
        );
    }

    /**
     * Confirma que um utilizador não pode alterar o próprio papel.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_a_alteracao_do_proprio_papel(): void
    {
        Notification::fake();

        $superAdministrador =
            $this->criarSuperAdministrador();

        try {
            $this
                ->servico
                ->alterar(
                    $superAdministrador,
                    $superAdministrador,
                    PapelUtilizador::Administrador,
                );

            self::fail(
                'Era esperada uma exceção de domínio.',
            );
        } catch (DomainException $excecao) {
            self::assertSame(
                'Um utilizador não pode alterar o próprio papel.',
                $excecao->getMessage(),
            );
        }

        Notification::assertNothingSent();
    }

    /**
     * Confirma que um responsável sem papel de superadministrador é
     * rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_responsavel_sem_papel_de_superadministrador(): void
    {
        Notification::fake();

        $responsavel =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Administrador,
                )
                ->create();

        $utilizador =
            Utilizador::factory()
                ->create();

        try {
            $this
                ->servico
                ->alterar(
                    $utilizador,
                    $responsavel,
                    PapelUtilizador::Administrador,
                );

            self::fail(
                'Era esperada uma exceção de domínio.',
            );
        } catch (DomainException $excecao) {
            self::assertSame(
                'A gestão dos papéis exige um superadministrador com acesso ativo.',
                $excecao->getMessage(),
            );
        }

        Notification::assertNothingSent();
    }

    /**
     * Confirma que um superadministrador suspenso não pode gerir papéis.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_superadministrador_suspenso(): void
    {
        Notification::fake();

        $outroSuperAdministrador =
            $this->criarSuperAdministrador();

        $responsavel =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::SuperAdministrador,
                )
                ->suspensoPor(
                    $outroSuperAdministrador,
                )
                ->create();

        $utilizador =
            Utilizador::factory()
                ->create();

        try {
            $this
                ->servico
                ->alterar(
                    $utilizador,
                    $responsavel,
                    PapelUtilizador::Administrador,
                );

            self::fail(
                'Era esperada uma exceção de domínio.',
            );
        } catch (DomainException) {
            // Exceção esperada.
        }

        Notification::assertNothingSent();
    }

    /**
     * Confirma que uma alteração sem efeito é rejeitada sem efeitos
     * colaterais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_papel_ja_atribuido(): void
    {
        Notification::fake();

        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Administrador,
                )
                ->create();

        $utilizador->setRememberToken(
            'token-preservado',
        );

        $utilizador->saveOrFail();

        $this->criarSessao(
            $utilizador,
            'sessao-preservada',
        );

        try {
            $this
                ->servico
                ->alterar(
                    $utilizador,
                    $responsavel,
                    PapelUtilizador::Administrador,
                );

            self::fail(
                'Era esperada uma exceção para uma alteração sem efeito.',
            );
        } catch (DomainException $excecao) {
            self::assertSame(
                'O utilizador já possui o papel selecionado.',
                $excecao->getMessage(),
            );
        }

        $utilizador->refresh();

        self::assertSame(
            PapelUtilizador::Administrador,
            $utilizador->papel,
        );

        self::assertSame(
            'token-preservado',
            $utilizador->getRememberToken(),
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

        $this->assertDatabaseCount(
            'registos_papel_utilizadores',
            0,
        );

        Notification::assertNothingSent();
    }

    /**
     * Confirma que a remoção do papel de um superadministrador ativo preserva
     * outro superadministrador ativo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preserva_um_superadministrador_com_acesso_ativo(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador =
            $this->criarSuperAdministrador();

        $this
            ->servico
            ->alterar(
                $utilizador,
                $responsavel,
                PapelUtilizador::Administrador,
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
     * Confirma que todos os efeitos pertencem à transação exterior.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reverte_toda_a_alteracao_quando_a_transacao_exterior_falha(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Utilizador,
                )
                ->create();

        $utilizador->setRememberToken(
            'token-antes-do-rollback',
        );

        $utilizador->saveOrFail();

        $this->criarSessao(
            $utilizador,
            'sessao-antes-do-rollback',
        );

        try {
            DB::transaction(
                function () use (
                    $utilizador,
                    $responsavel,
                ): never {
                    $this
                        ->servico
                        ->alterar(
                            $utilizador,
                            $responsavel,
                            PapelUtilizador::Administrador,
                        );

                    throw new RuntimeException(
                        'Forçar rollback.',
                    );
                },
            );

            self::fail(
                'Era esperada uma exceção para provocar o rollback.',
            );
        } catch (RuntimeException $excecao) {
            self::assertSame(
                'Forçar rollback.',
                $excecao->getMessage(),
            );
        }

        $utilizador->refresh();

        self::assertSame(
            PapelUtilizador::Utilizador,
            $utilizador->papel,
        );

        self::assertSame(
            'token-antes-do-rollback',
            $utilizador->getRememberToken(),
        );

        self::assertTrue(
            DB::table(
                'sessoes',
            )
                ->where(
                    'id',
                    'sessao-antes-do-rollback',
                )
                ->exists(),
        );

        $this->assertDatabaseCount(
            'registos_papel_utilizadores',
            0,
        );
    }

    /**
     * Confirma que um utilizador afetado não persistido é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_utilizador_afetado_nao_persistido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this
            ->servico
            ->alterar(
                new Utilizador,
                $this->criarSuperAdministrador(),
                PapelUtilizador::Administrador,
            );
    }

    /**
     * Confirma que um responsável não persistido é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_responsavel_nao_persistido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this
            ->servico
            ->alterar(
                Utilizador::factory()->create(),
                new Utilizador,
                PapelUtilizador::Administrador,
            );
    }

    /**
     * Cria um superadministrador com acesso ativo.
     *
     * @return Utilizador Superadministrador criado.
     *
     * @since 2.0.0
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
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  string  $identificador  Identificador da sessão.
     *
     * @since 2.0.0
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
