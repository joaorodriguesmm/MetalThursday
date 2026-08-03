<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Autenticacao;

use App\Models\Autenticacao\Utilizador;
use App\Servicos\Autenticacao\ServicoSessoesUtilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa o encerramento das sessões persistidas dos utilizadores.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoSessoesUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Serviço testado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private ServicoSessoesUtilizador $servico;

    /**
     * Prepara o serviço antes de cada teste.
     *
     * O nome permanece em inglês por corresponder ao ciclo de vida do
     * PHPUnit.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->servico =
            new ServicoSessoesUtilizador;
    }

    /**
     * Confirma que são eliminadas apenas as sessões do utilizador indicado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function encerra_todas_as_sessoes_do_utilizador(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $outroUtilizador = Utilizador::factory()
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

        $eliminadas =
            $this
                ->servico
                ->encerrarTodas(
                    $utilizador,
                );

        self::assertSame(
            2,
            $eliminadas,
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
    }

    /**
     * Confirma que a inexistência de sessões devolve zero.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function devolve_zero_quando_o_utilizador_nao_possui_sessoes(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        self::assertSame(
            0,
            $this
                ->servico
                ->encerrarTodas(
                    $utilizador,
                ),
        );
    }

    /**
     * Confirma que a eliminação participa numa transação exterior.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function reverte_o_encerramento_quando_a_transacao_exterior_falha(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this->criarSessao(
            $utilizador,
            'sessao-preservada',
        );

        try {
            DB::transaction(
                function () use (
                    $utilizador,
                ): never {
                    $this
                        ->servico
                        ->encerrarTodas(
                            $utilizador,
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
     * Confirma que um utilizador não persistido é rejeitado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_um_utilizador_nao_persistido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this
            ->servico
            ->encerrarTodas(
                new Utilizador,
            );
    }

    /**
     * Cria uma sessão técnica associada a um utilizador.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
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
