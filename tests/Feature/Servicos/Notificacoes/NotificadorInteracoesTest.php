<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Notificacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use App\Servicos\Notificacoes\NotificadorInteracoes;
use Illuminate\Contracts\Notifications\Dispatcher as DespachanteNotificacoes;
use Illuminate\Database\Eloquent\Collection as ColecaoEloquent;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o carregamento dos destinatários das notificações de interações.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class NotificadorInteracoesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que as permissões são carregadas por lote e que a determinação
     * dos canais não executa consultas adicionais por destinatário.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function carrega_permissoes_sem_consultas_por_destinatario(): void
    {
        $causador = Utilizador::factory()
            ->create();

        $destinatarioComEmail = Utilizador::factory()
            ->create();

        $destinatarioSemEmail = Utilizador::factory()
            ->create();

        $permissaoTodas = PermissaoEmail::factory()
            ->comIdentificador(
                'todas',
            )
            ->create();

        $destinatarioComEmail
            ->permissoesEmail()
            ->attach(
                $permissaoTodas->getKey(),
            );

        $metalThursday = MetalThursday::factory()
            ->comAutor(
                $causador,
            )
            ->comProximoNomeado(
                $causador,
            )
            ->create();

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] =
                    mb_strtolower(
                        $consulta->sql,
                    );
            },
        );

        $despachante =
            Mockery::mock(
                DespachanteNotificacoes::class,
            );

        $despachante
            ->shouldReceive(
                'send',
            )
            ->once()
            ->andReturnUsing(
                function (
                    mixed $destinatarios,
                    mixed $notificacao,
                ) use (
                    &$consultas,
                    $destinatarioComEmail,
                    $destinatarioSemEmail,
                ): void {
                    self::assertInstanceOf(
                        ColecaoEloquent::class,
                        $destinatarios,
                    );

                    self::assertInstanceOf(
                        NotificacaoInteracaoUtilizador::class,
                        $notificacao,
                    );

                    self::assertCount(
                        2,
                        $destinatarios,
                    );

                    foreach ($destinatarios as $destinatario) {
                        self::assertInstanceOf(
                            Utilizador::class,
                            $destinatario,
                        );

                        self::assertTrue(
                            $destinatario->relationLoaded(
                                'permissoesEmail',
                            ),
                        );
                    }

                    $numeroConsultasAntesDosCanais =
                        count(
                            $consultas,
                        );

                    $destinatariosPorIdentificador =
                        $destinatarios->keyBy(
                            static fn (
                                Utilizador $utilizador,
                            ): int => (int) $utilizador->getKey(),
                        );

                    $utilizadorComEmail =
                        $destinatariosPorIdentificador->get(
                            $destinatarioComEmail->getKey(),
                        );

                    $utilizadorSemEmail =
                        $destinatariosPorIdentificador->get(
                            $destinatarioSemEmail->getKey(),
                        );

                    self::assertInstanceOf(
                        Utilizador::class,
                        $utilizadorComEmail,
                    );

                    self::assertInstanceOf(
                        Utilizador::class,
                        $utilizadorSemEmail,
                    );

                    self::assertSame(
                        [
                            'database',
                            'mail',
                        ],
                        $notificacao->via(
                            $utilizadorComEmail,
                        ),
                    );

                    self::assertSame(
                        [
                            'database',
                        ],
                        $notificacao->via(
                            $utilizadorSemEmail,
                        ),
                    );

                    self::assertCount(
                        $numeroConsultasAntesDosCanais,
                        $consultas,
                    );
                },
            );

        $servico =
            new NotificadorInteracoes(
                $despachante,
            );

        $servico->notificarOutrosUtilizadores(
            sujeito: $metalThursday,
            causador: $causador,
            acao: 'comentou',
        );

        $consultasPermissoes =
            array_values(
                array_filter(
                    $consultas,
                    static fn (
                        string $consulta,
                    ): bool => str_contains(
                        $consulta,
                        'permissoes_email',
                    ),
                ),
            );

        self::assertCount(
            1,
            $consultasPermissoes,
        );
    }
}
