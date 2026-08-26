<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoLembreteAtrasoMetalThursday;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as KernelConsola;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integração do agendamento diário dos lembretes de atrasos.
 *
 * @since 2.0.0
 */
final class AgendamentoLembretesAtrasosMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara o catálogo real das permissões de e-mail.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        app(
            PermissaoEmailSeeder::class,
        )->run();
    }

    /**
     * Confirma que o agendamento diário notifica uma reserva pendente anterior
     * ao próprio dia.
     *
     * @since 2.0.0
     */
    #[Test]
    public function agendamento_diario_notifica_reserva_em_atraso(): void
    {
        Notification::fake();

        $responsavel = Utilizador::factory()
            ->create();

        $this->atribuirPermissaoAtrasos(
            $responsavel,
        );

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-20',
                    'Europe/Lisbon',
                ),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-27 08:05:00',
                'Europe/Lisbon',
            ),
        );

        try {
            $this->executarAgendamentoLembreteAtrasos();
        } finally {
            CarbonImmutable::setTestNow();
        }

        Notification::assertSentTo(
            $responsavel,
            NotificacaoLembreteAtrasoMetalThursday::class,
        );
    }

    /**
     * Confirma que a reserva do próprio dia ainda não é tratada como atraso.
     *
     * @since 2.0.0
     */
    #[Test]
    public function agendamento_diario_nao_trata_proprio_dia_como_atraso(): void
    {
        Notification::fake();

        $responsavel = Utilizador::factory()
            ->create();

        $this->atribuirPermissaoAtrasos(
            $responsavel,
        );

        ReservaMetalThursday::factory()
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

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse(
                '2026-08-27 08:05:00',
                'Europe/Lisbon',
            ),
        );

        try {
            $this->executarAgendamentoLembreteAtrasos();
        } finally {
            CarbonImmutable::setTestNow();
        }

        Notification::assertNothingSent();
    }

    /**
     * Autoriza o utilizador a receber os lembretes diários de atrasos.
     *
     * @param  Utilizador  $utilizador  Utilizador a configurar.
     *
     * @since 2.0.0
     */
    private function atribuirPermissaoAtrasos(
        Utilizador $utilizador,
    ): void {
        $permissao = PermissaoEmail::query()
            ->where(
                'identificador',
                IdentificadorPermissaoEmail::LembreteDiarioAtrasos->value,
            )
            ->sole();

        $utilizador
            ->permissoesEmail()
            ->sync([
                $permissao->getKey(),
            ]);

        $utilizador->unsetRelation(
            'permissoesEmail',
        );
    }

    /**
     * Executa diretamente o evento diário de atrasos registado no scheduler.
     *
     * @since 2.0.0
     */
    private function executarAgendamentoLembreteAtrasos(): void
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
                'lembretes-metal-thursday:atrasos',
        );

        self::assertInstanceOf(
            CallbackEvent::class,
            $evento,
        );

        self::assertSame(
            '5 8 * * *',
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
