<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Utilizadores;

use App\Enumeracoes\AcaoAcessoUtilizador;
use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\RegistoAcessoUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Servicos\Autenticacao\ServicoSessoesUtilizador;
use App\Servicos\Utilizadores\ServicoAcessoUtilizadores;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa a gestão transacional do acesso dos utilizadores.
 *
 * @since 2.0.0
 */
final class ServicoAcessoUtilizadoresTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Serviço testado.
     *
     * @since 2.0.0
     */
    private ServicoAcessoUtilizadores $servico;

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
            new ServicoAcessoUtilizadores(
                new ServicoSessoesUtilizador,
            );
    }

    /**
     * Confirma a suspensão completa de um utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function suspende_o_utilizador_e_invalida_as_autenticacoes(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $tokenOriginal =
            'token-persistente-original';

        $utilizador = Utilizador::factory()
            ->state([
                'remember_token' => $tokenOriginal,
            ])
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
            $responsavel,
            'sessao-responsavel',
        );

        $momento = CarbonImmutable::parse(
            '2026-08-03 14:30:00',
        );

        $resultado =
            $this
                ->servico
                ->suspender(
                    utilizador: $utilizador,
                    responsavel: $responsavel,
                    motivo: "  Incumprimento \n reiterado. ",
                    momento: $momento,
                );

        self::assertTrue(
            $resultado->estaSuspenso(),
        );

        self::assertTrue(
            $momento->equalTo(
                $resultado->suspenso_em,
            ),
        );

        self::assertSame(
            'Incumprimento reiterado.',
            $resultado->motivo_suspensao,
        );

        self::assertSame(
            (int) $responsavel->getKey(),
            $resultado->suspenso_por_id,
        );

        $tokenNovo =
            $resultado->getRememberToken();

        self::assertIsString(
            $tokenNovo,
        );

        self::assertNotSame(
            $tokenOriginal,
            $tokenNovo,
        );

        self::assertSame(
            60,
            strlen(
                $tokenNovo,
            ),
        );

        self::assertSame(
            0,
            $this->contarSessoes(
                $utilizador,
            ),
        );

        self::assertSame(
            1,
            $this->contarSessoes(
                $responsavel,
            ),
        );

        $registo =
            RegistoAcessoUtilizador::query()
                ->sole();

        self::assertSame(
            (int) $utilizador->getKey(),
            $registo->utilizador_id,
        );

        self::assertSame(
            (int) $responsavel->getKey(),
            $registo->responsavel_id,
        );

        self::assertSame(
            AcaoAcessoUtilizador::Suspensao,
            $registo->acao,
        );

        self::assertSame(
            'Incumprimento reiterado.',
            $registo->motivo,
        );

        self::assertTrue(
            $momento->equalTo(
                $registo->registado_em,
            ),
        );
    }

    /**
     * Confirma a reativação completa de um utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reativa_o_utilizador_sem_restaurar_sessoes(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $tokenOriginal =
            'token-suspenso-original';

        $utilizador = Utilizador::factory()
            ->suspensoPor(
                $responsavel,
                'Motivo anterior.',
            )
            ->state([
                'remember_token' => $tokenOriginal,
            ])
            ->create();

        $this->criarSessao(
            $utilizador,
            'sessao-concorrente',
        );

        $momento = CarbonImmutable::parse(
            '2026-08-03 15:00:00',
        );

        $resultado =
            $this
                ->servico
                ->reativar(
                    utilizador: $utilizador,
                    responsavel: $responsavel,
                    momento: $momento,
                );

        self::assertTrue(
            $resultado->temAcessoAtivo(),
        );

        self::assertNull(
            $resultado->suspenso_em,
        );

        self::assertNull(
            $resultado->motivo_suspensao,
        );

        self::assertNull(
            $resultado->suspenso_por_id,
        );

        $tokenNovo =
            $resultado->getRememberToken();

        self::assertIsString(
            $tokenNovo,
        );

        self::assertNotSame(
            $tokenOriginal,
            $tokenNovo,
        );

        self::assertSame(
            60,
            strlen(
                $tokenNovo,
            ),
        );

        self::assertSame(
            0,
            $this->contarSessoes(
                $utilizador,
            ),
        );

        $registo =
            RegistoAcessoUtilizador::query()
                ->sole();

        self::assertSame(
            AcaoAcessoUtilizador::Reativacao,
            $registo->acao,
        );

        self::assertNull(
            $registo->motivo,
        );

        self::assertTrue(
            $momento->equalTo(
                $registo->registado_em,
            ),
        );
    }

    /**
     * Confirma o encerramento administrativo das sessões sem alterar o
     * estado do acesso.
     *
     * @since 2.0.0
     */
    #[Test]
    public function encerra_as_sessoes_sem_alterar_o_estado_do_acesso(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $tokenOriginal =
            'token-ativo-original';

        $utilizador = Utilizador::factory()
            ->state([
                'remember_token' => $tokenOriginal,
            ])
            ->create();

        $this->criarSessao(
            $utilizador,
            'sessao-administrativa-1',
        );

        $this->criarSessao(
            $utilizador,
            'sessao-administrativa-2',
        );

        $eliminadas =
            $this
                ->servico
                ->encerrarSessoes(
                    $utilizador,
                    $responsavel,
                );

        self::assertSame(
            2,
            $eliminadas,
        );

        $utilizador->refresh();

        self::assertTrue(
            $utilizador->temAcessoAtivo(),
        );

        self::assertNotSame(
            $tokenOriginal,
            $utilizador->getRememberToken(),
        );

        self::assertSame(
            0,
            $this->contarSessoes(
                $utilizador,
            ),
        );

        self::assertSame(
            0,
            RegistoAcessoUtilizador::query()
                ->count(),
        );
    }

    /**
     * Confirma que um utilizador não pode suspender-se a si próprio.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_a_autossuspensao(): void
    {
        $utilizador =
            $this->criarSuperAdministrador();

        $this->expectException(
            DomainException::class,
        );

        $this
            ->servico
            ->suspender(
                $utilizador,
                $utilizador,
                'Motivo válido.',
            );
    }

    /**
     * Confirma que um utilizador comum não pode gerir o acesso.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_responsavel_sem_papel_de_superadministrador(): void
    {
        $responsavel = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $utilizador = Utilizador::factory()
            ->create();

        $tokenOriginal =
            $utilizador->getRememberToken();

        $this->criarSessao(
            $utilizador,
            'sessao-preservada',
        );

        try {
            $this
                ->servico
                ->suspender(
                    $utilizador,
                    $responsavel,
                    'Motivo válido.',
                );

            self::fail(
                'Era esperada uma exceção de domínio.',
            );
        } catch (DomainException) {
            $utilizador->refresh();
        }

        self::assertTrue(
            $utilizador->temAcessoAtivo(),
        );

        self::assertSame(
            $tokenOriginal,
            $utilizador->getRememberToken(),
        );

        self::assertSame(
            1,
            $this->contarSessoes(
                $utilizador,
            ),
        );

        self::assertSame(
            0,
            RegistoAcessoUtilizador::query()
                ->count(),
        );
    }

    /**
     * Confirma que um superadministrador suspenso não pode gerir o acesso.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_superadministrador_suspenso(): void
    {
        $superAdministradorAtivo =
            $this->criarSuperAdministrador();

        $responsavelSuspenso = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->suspensoPor(
                $superAdministradorAtivo,
                'Suspensão administrativa.',
            )
            ->create();

        $utilizador = Utilizador::factory()
            ->create();

        $this->expectException(
            DomainException::class,
        );

        $this
            ->servico
            ->suspender(
                $utilizador,
                $responsavelSuspenso,
                'Motivo válido.',
            );
    }

    /**
     * Confirma que uma segunda suspensão é rejeitada sem efeitos colaterais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_utilizador_ja_suspenso(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->suspensoPor(
                $responsavel,
                'Motivo inicial.',
            )
            ->create();

        $tokenOriginal =
            $utilizador->getRememberToken();

        $this->criarSessao(
            $utilizador,
            'sessao-preservada',
        );

        try {
            $this
                ->servico
                ->suspender(
                    $utilizador,
                    $responsavel,
                    'Novo motivo.',
                );

            self::fail(
                'Era esperada uma exceção de domínio.',
            );
        } catch (DomainException) {
            $utilizador->refresh();
        }

        self::assertSame(
            'Motivo inicial.',
            $utilizador->motivo_suspensao,
        );

        self::assertSame(
            $tokenOriginal,
            $utilizador->getRememberToken(),
        );

        self::assertSame(
            1,
            $this->contarSessoes(
                $utilizador,
            ),
        );

        self::assertSame(
            0,
            RegistoAcessoUtilizador::query()
                ->count(),
        );
    }

    /**
     * Confirma que um utilizador já ativo não pode ser reativado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_um_utilizador_que_ja_possui_acesso_ativo(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->create();

        $this->expectException(
            DomainException::class,
        );

        $this
            ->servico
            ->reativar(
                $utilizador,
                $responsavel,
            );
    }

    /**
     * Confirma que a suspensão de um superadministrador preserva outro
     * superadministrador ativo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preserva_um_superadministrador_com_acesso_ativo(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $utilizador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $this
            ->servico
            ->suspender(
                $utilizador,
                $responsavel,
                'Alteração da equipa administrativa.',
            );

        self::assertSame(
            1,
            Utilizador::query()
                ->where(
                    'papel',
                    PapelUtilizador::SuperAdministrador->value,
                )
                ->comAcessoAtivo()
                ->count(),
        );

        self::assertTrue(
            $responsavel
                ->fresh()
                ->temAcessoAtivo(),
        );
    }

    /**
     * Confirma que todos os efeitos da suspensão pertencem à transação
     * exterior.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reverte_toda_a_suspensao_quando_a_transacao_exterior_falha(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $tokenOriginal =
            'token-antes-do-rollback';

        $utilizador = Utilizador::factory()
            ->state([
                'remember_token' => $tokenOriginal,
            ])
            ->create();

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
                        ->suspender(
                            $utilizador,
                            $responsavel,
                            'Suspensão revertida.',
                        );

                    throw new RuntimeException(
                        'Forçar rollback exterior.',
                    );
                },
            );

            self::fail(
                'Era esperada uma exceção para provocar o rollback.',
            );
        } catch (RuntimeException $excecao) {
            self::assertSame(
                'Forçar rollback exterior.',
                $excecao->getMessage(),
            );
        }

        $utilizador->refresh();

        self::assertTrue(
            $utilizador->temAcessoAtivo(),
        );

        self::assertSame(
            $tokenOriginal,
            $utilizador->getRememberToken(),
        );

        self::assertSame(
            1,
            $this->contarSessoes(
                $utilizador,
            ),
        );

        self::assertSame(
            0,
            RegistoAcessoUtilizador::query()
                ->count(),
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
        $responsavel =
            $this->criarSuperAdministrador();

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this
            ->servico
            ->suspender(
                new Utilizador,
                $responsavel,
                'Motivo válido.',
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
        $utilizador = Utilizador::factory()
            ->create();

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this
            ->servico
            ->suspender(
                $utilizador,
                new Utilizador,
                'Motivo válido.',
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

    /**
     * Conta as sessões persistidas de um utilizador.
     *
     * @param  Utilizador  $utilizador  Utilizador consultado.
     * @return int Número de sessões.
     *
     * @since 2.0.0
     */
    private function contarSessoes(
        Utilizador $utilizador,
    ): int {
        return DB::table(
            'sessoes',
        )
            ->where(
                'user_id',
                $utilizador->getKey(),
            )
            ->count();
    }
}
