<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoMetalThursdayCriada;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as KernelConsola;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integração do agendamento das notificações de publicação.
 *
 * @since 2.0.0
 */
final class AgendamentoPublicacoesMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o agendamento da meia-noite processa uma publicação cuja
     * data chegou sem antecipar uma MetalThursday futura.
     *
     * O teste valida também a frequência, o fuso horário e a proteção contra
     * sobreposição configurados em `routes/console.php`.
     *
     * @since 2.0.0
     */
    #[Test]
    public function agendamento_da_meia_noite_notifica_apenas_publicacoes_elegiveis(): void
    {
        Notification::fake();

        $criador =
            Utilizador::factory()
                ->create();

        $nomeado =
            Utilizador::factory()
                ->create();

        $destinatario =
            Utilizador::factory()
                ->create();

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    CarbonImmutable::parse(
                        '2026-08-01',
                    ),
                    CarbonImmutable::parse(
                        '2026-09-30',
                    ),
                )
                ->create();

        $publicadaHoje =
            $this->criarMetalThursday(
                $edicao,
                $criador,
                $nomeado,
                '2026-08-27',
            );

        $preparada =
            $this->criarMetalThursday(
                $edicao,
                $criador,
                $nomeado,
                '2026-09-03',
            );

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-27 00:00:00',
                'Europe/Lisbon',
            ),
        );

        try {
            $this->executarAgendamentoPublicacoes();
        } finally {
            CarbonImmutable::setTestNow();
        }

        self::assertNotNull(
            $publicadaHoje
                ->refresh()
                ->publicacao_notificada_em,
        );

        self::assertNull(
            $preparada
                ->refresh()
                ->publicacao_notificada_em,
        );

        Notification::assertSentTo(
            $destinatario,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertNotSentTo(
            $criador,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertNotSentTo(
            $nomeado,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertCount(
            1,
        );
    }

    /**
     * Executa diretamente o evento de publicação registado no scheduler.
     *
     * @since 2.0.0
     */
    private function executarAgendamentoPublicacoes(): void
    {
        app(
            KernelConsola::class,
        )->bootstrap();

        $evento =
            collect(
                app(
                    Schedule::class,
                )->events(),
            )->first(
                static fn (
                    mixed $evento,
                ): bool => $evento instanceof CallbackEvent
                    && $evento->description ===
                    'publicacoes-metal-thursday:notificar',
            );

        self::assertInstanceOf(
            CallbackEvent::class,
            $evento,
        );

        self::assertSame(
            '0 0 * * *',
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

    /**
     * Cria uma MetalThursday para o cenário temporal indicado.
     *
     * @param  Edicao  $edicao  Edição associada.
     * @param  Utilizador  $criador  Criador e autor.
     * @param  Utilizador  $nomeado  Próximo nomeado.
     * @param  string  $data  Data no formato AAAA-MM-DD.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        Edicao $edicao,
        Utilizador $criador,
        Utilizador $nomeado,
        string $data,
    ): MetalThursday {
        return MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    $data,
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $criador,
            )
            ->comProximoNomeado(
                $nomeado,
            )
            ->create([
                'criado_por_id' => $criador->getKey(),
            ]);
    }
}
