<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Notificacoes;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoLembreteAtrasoMetalThursday;
use App\Notifications\NotificacaoLembreteTarefaMetalThursday;
use App\Servicos\Notificacoes\NotificadorLembretesMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Notifications\Dispatcher as DespachanteNotificacoes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a seleção dos destinatários dos lembretes de MetalThursday.
 *
 * @since 2.0.0
 */
final class NotificadorLembretesMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que uma reserva pendente do próprio dia é enviada ao respetivo
     * responsável com as permissões de e-mail já carregadas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function notifica_responsavel_ativo_com_tarefa_pendente_no_proprio_dia(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                $this->dataReferencia(),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

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
                static function (
                    mixed $destinatario,
                    mixed $notificacao,
                ) use (
                    $responsavel,
                ): void {
                    self::assertInstanceOf(
                        Utilizador::class,
                        $destinatario,
                    );

                    self::assertSame(
                        $responsavel->getKey(),
                        $destinatario->getKey(),
                    );

                    self::assertTrue(
                        $destinatario->relationLoaded(
                            'permissoesEmail',
                        ),
                    );

                    self::assertInstanceOf(
                        NotificacaoLembreteTarefaMetalThursday::class,
                        $notificacao,
                    );
                },
            );

        $servico =
            new NotificadorLembretesMetalThursday(
                $despachante,
            );

        $servico->notificarTarefasDoDia(
            $this->dataReferencia()
                ->setTime(
                    12,
                    30,
                ),
        );
    }

    /**
     * Confirma que uma reserva já cumprida no próprio dia não gera lembrete.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_notifica_reserva_ja_cumprida(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comData(
                $this->dataReferencia(),
            )
            ->comAutor(
                $responsavel,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comMetalThursday(
                $metalThursday,
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        $this->executarSemEsperarEnvio();
    }

    /**
     * Confirma que reservas anteriores ou futuras não são confundidas com a
     * tarefa do próprio dia.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_notifica_reservas_de_outros_dias(): void
    {
        $responsavelAnterior = Utilizador::factory()
            ->create();

        $responsavelSeguinte = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                $this->dataReferencia()
                    ->subWeek(),
            )
            ->comResponsavel(
                $responsavelAnterior,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                $this->dataReferencia()
                    ->addWeek(),
            )
            ->comResponsavel(
                $responsavelSeguinte,
            )
            ->create();

        $this->executarSemEsperarEnvio();
    }

    /**
     * Confirma que uma reserva sem responsável não gera qualquer lembrete.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_notifica_reserva_sem_responsavel(): void
    {
        ReservaMetalThursday::factory()
            ->comData(
                $this->dataReferencia(),
            )
            ->semResponsavel()
            ->create();

        $this->executarSemEsperarEnvio();
    }

    /**
     * Confirma que um responsável atualmente suspenso não recebe o lembrete.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_notifica_responsavel_suspenso(): void
    {
        $superAdministrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $responsavel = Utilizador::factory()
            ->suspensoPor(
                $superAdministrador,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                $this->dataReferencia(),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        $this->executarSemEsperarEnvio();
    }

    /**
     * Confirma que cada responsável recebe apenas a respetiva tarefa pendente em
     * atraso mais antiga.
     *
     * @since 2.0.0
     */
    #[Test]
    public function notifica_apenas_atraso_mais_antigo_de_cada_responsavel(): void
    {
        $responsavelComDoisAtrasos = Utilizador::factory()
            ->create();

        $outroResponsavel = Utilizador::factory()
            ->create();

        $reservaMaisAntiga = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-13',
                    'Europe/Lisbon',
                ),
            )
            ->comResponsavel(
                $responsavelComDoisAtrasos,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-20',
                    'Europe/Lisbon',
                ),
            )
            ->comResponsavel(
                $responsavelComDoisAtrasos,
            )
            ->create();

        $reservaOutroResponsavel = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-06',
                    'Europe/Lisbon',
                ),
            )
            ->comResponsavel(
                $outroResponsavel,
            )
            ->create();

        $identificadoresEsperados = [
            $responsavelComDoisAtrasos->getKey() => $reservaMaisAntiga->getKey(),

            $outroResponsavel->getKey() => $reservaOutroResponsavel->getKey(),
        ];

        $despachante =
            Mockery::mock(
                DespachanteNotificacoes::class,
            );

        $despachante
            ->shouldReceive(
                'send',
            )
            ->twice()
            ->andReturnUsing(
                static function (
                    mixed $destinatario,
                    mixed $notificacao,
                ) use (
                    &$identificadoresEsperados,
                ): void {
                    self::assertInstanceOf(
                        Utilizador::class,
                        $destinatario,
                    );

                    self::assertTrue(
                        $destinatario->relationLoaded(
                            'permissoesEmail',
                        ),
                    );

                    self::assertInstanceOf(
                        NotificacaoLembreteAtrasoMetalThursday::class,
                        $notificacao,
                    );

                    $identificadorDestinatario =
                        (int) $destinatario->getKey();

                    self::assertArrayHasKey(
                        $identificadorDestinatario,
                        $identificadoresEsperados,
                    );

                    $mensagem =
                        $notificacao->toMail(
                            $destinatario,
                        );

                    self::assertSame(
                        route(
                            'metal-thursday.reservas.preparar',
                            [
                                'reservaMetalThursday' => $identificadoresEsperados[$identificadorDestinatario],
                            ],
                        ),
                        $mensagem->actionUrl,
                    );

                    unset(
                        $identificadoresEsperados[$identificadorDestinatario],
                    );
                },
            );

        $servico =
            new NotificadorLembretesMetalThursday(
                $despachante,
            );

        $servico->notificarAtrasos(
            $this->dataReferencia()
                ->setTime(
                    8,
                    0,
                ),
        );

        self::assertSame(
            [],
            $identificadoresEsperados,
        );
    }

    /**
     * Confirma que o envio de atrasos ignora reservas cumpridas, reservas sem
     * responsável, o próprio dia, datas futuras e responsáveis suspensos.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_notifica_reservas_que_nao_representam_atraso_elegivel(): void
    {
        $responsavelAtivo = Utilizador::factory()
            ->create();

        $autor = Utilizador::factory()
            ->create();

        $superAdministrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $responsavelSuspenso = Utilizador::factory()
            ->suspensoPor(
                $superAdministrador,
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-07-30',
                    'Europe/Lisbon',
                ),
            )
            ->comAutor(
                $autor,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comMetalThursday(
                $metalThursday,
            )
            ->comResponsavel(
                $responsavelAtivo,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-06',
                    'Europe/Lisbon',
                ),
            )
            ->semResponsavel()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-13',
                    'Europe/Lisbon',
                ),
            )
            ->comResponsavel(
                $responsavelSuspenso,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                $this->dataReferencia(),
            )
            ->comResponsavel(
                $responsavelAtivo,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                $this->dataReferencia()
                    ->addWeek(),
            )
            ->comResponsavel(
                $responsavelAtivo,
            )
            ->create();

        $despachante =
            Mockery::mock(
                DespachanteNotificacoes::class,
            );

        $despachante
            ->shouldNotReceive(
                'send',
            );

        $servico =
            new NotificadorLembretesMetalThursday(
                $despachante,
            );

        $servico->notificarAtrasos(
            $this->dataReferencia()
                ->setTime(
                    8,
                    0,
                ),
        );
    }

    /**
     * Executa o serviço garantindo que nenhuma notificação é enviada.
     *
     * @since 2.0.0
     */
    private function executarSemEsperarEnvio(): void
    {
        $despachante =
            Mockery::mock(
                DespachanteNotificacoes::class,
            );

        $despachante
            ->shouldNotReceive(
                'send',
            );

        $servico =
            new NotificadorLembretesMetalThursday(
                $despachante,
            );

        $servico->notificarTarefasDoDia(
            $this->dataReferencia()
                ->setTime(
                    12,
                    30,
                ),
        );
    }

    /**
     * Obtém a quinta-feira utilizada como referência nos testes.
     *
     * @return CarbonImmutable Data de referência.
     *
     * @since 2.0.0
     */
    private function dataReferencia(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            '2026-08-27 00:00:00',
            'Europe/Lisbon',
        );
    }
}
