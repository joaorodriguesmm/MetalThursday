<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoLembreteTarefaMetalThursday;
use App\Servicos\Agendamentos\ExecutarLembreteTarefasMetalThursday;
use App\Servicos\Notificacoes\NotificadorLembretesMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as KernelConsola;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa a integração do agendamento diário dos lembretes de MetalThursday.
 *
 * @since 2.0.0
 */
final class AgendamentoLembretesMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o agendamento diário envia o lembrete ao responsável por
     * uma reserva pendente do próprio dia.
     *
     * @since 2.0.0
     */
    #[Test]
    public function agendamento_diario_notifica_tarefa_pendente_do_proprio_dia(): void
    {
        Notification::fake();

        $responsavel = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-27 08:00:00',
                'Europe/Lisbon',
            ),
        );

        try {
            $this->executarAgendamentoLembreteTarefas();
        } finally {
            CarbonImmutable::setTestNow();
        }

        Notification::assertSentTo(
            $responsavel,
            NotificacaoLembreteTarefaMetalThursday::class,
        );
    }

    /**
     * Confirma que o agendamento diário não envia um lembrete quando apenas
     * existem reservas de outros dias.
     *
     * @since 2.0.0
     */
    #[Test]
    public function agendamento_diario_nao_notifica_reservas_de_outros_dias(): void
    {
        Notification::fake();

        $responsavelAnterior = Utilizador::factory()
            ->create();

        $responsavelSeguinte = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-20',
                ),
            )
            ->comResponsavel(
                $responsavelAnterior,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-09-03',
                ),
            )
            ->comResponsavel(
                $responsavelSeguinte,
            )
            ->create();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-27 08:00:00',
                'Europe/Lisbon',
            ),
        );

        try {
            $this->executarAgendamentoLembreteTarefas();
        } finally {
            CarbonImmutable::setTestNow();
        }

        Notification::assertNothingSent();
    }

    /**
     * Confirma que uma falha no despacho do lembrete de tarefas
     * não expõe mensagens sensíveis nos logs.
     *
     * A falha deve permanecer isolada na tarefa agendada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_expoe_mensagem_sensivel_ao_falhar_lembrete_de_tarefas(): void
    {
        $segredoFicticio =
            'AUDITORIA_SEGREDO_FICTICIO_B19_17_LEMBRETES_TAREFAS';

        $responsavel = Utilizador::factory()
            ->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                    'Europe/Lisbon',
                ),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        /*
     * Simula a falha no despacho inicial da notificação.
     * Não são enviados e-mails nem executadas filas reais.
     */
        $this->mock(
            Dispatcher::class,
        )
            ->shouldReceive(
                'send',
            )
            ->once()
            ->withArgs(
                static function (
                    mixed $destinatario,
                    mixed $notificacao,
                ) use (
                    $responsavel,
                ): bool {
                    return $destinatario instanceof Utilizador
                        && $destinatario->is($responsavel)
                        && $notificacao instanceof NotificacaoLembreteTarefaMetalThursday;
                },
            )
            ->andThrow(
                new RuntimeException(
                    'Falha simulada no lembrete de tarefas: '.$segredoFicticio,
                ),
            );

        /*
     * Intercepta os registos para não escrever o segredo
     * fictício nos ficheiros de log reais.
     */
        Log::spy();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-27 08:00:00',
                'Europe/Lisbon',
            ),
        );

        try {
            app(
                ExecutarLembreteTarefasMetalThursday::class,
            )(
                app(
                    NotificadorLembretesMetalThursday::class,
                ),
            );
        } finally {
            CarbonImmutable::setTestNow();
        }

        /*
     * A falha não pode alterar a reserva pendente.
     */
        self::assertNull(
            $reserva
                ->refresh()
                ->metal_thursday_id,
        );

        $mensagemRegistada = null;
        $contextoRegistado = null;

        Log::shouldHaveReceived(
            'error',
        )
            ->once()
            ->withArgs(
                static function (
                    string $mensagem,
                    array $contexto,
                ) use (
                    &$mensagemRegistada,
                    &$contextoRegistado,
                ): bool {
                    $mensagemRegistada = $mensagem;
                    $contextoRegistado = $contexto;

                    return true;
                },
            );

        self::assertIsString(
            $mensagemRegistada,
        );

        self::assertIsArray(
            $contextoRegistado,
        );

        self::assertStringNotContainsString(
            $segredoFicticio,
            $mensagemRegistada,
        );

        self::assertArrayNotHasKey(
            'exception',
            $contextoRegistado,
        );
    }

    /**
     * Executa diretamente o evento diário registado em `routes/console.php`.
     *
     * @since 2.0.0
     */
    private function executarAgendamentoLembreteTarefas(): void
    {
        app(
            KernelConsola::class,
        )->bootstrap();

        $evento = collect(
            app(
                Schedule::class,
            )->events(),
        )->first(
            static fn (mixed $evento): bool => $evento instanceof CallbackEvent
                && $evento->description ===
                'lembretes-metal-thursday:tarefas-do-dia',
        );

        self::assertInstanceOf(
            CallbackEvent::class,
            $evento,
        );

        self::assertSame(
            '0 8 * * *',
            $evento->expression,
        );

        self::assertSame(
            (string) config(
                'app.timezone',
            ),
            $evento->timezone,
        );

        self::assertTrue(
            $evento->withoutOverlapping,
        );

        $evento->run(
            $this->app,
        );
    }
}
