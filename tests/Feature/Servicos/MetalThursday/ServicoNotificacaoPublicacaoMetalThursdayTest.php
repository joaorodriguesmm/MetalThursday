<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoMetalThursdayCriada;
use App\Servicos\MetalThursday\ServicoNotificacaoPublicacaoMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa o processamento da notificação temporal de publicação.
 *
 * @since 2.0.0
 */
final class ServicoNotificacaoPublicacaoMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara uma referência temporal determinística.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-27 12:00:00',
                'Europe/Lisbon',
            ),
        );
    }

    /**
     * Confirma que uma publicação pendente é notificada apenas uma vez.
     *
     * @since 2.0.0
     */
    #[Test]
    public function processa_publicacao_pendente_apenas_uma_vez(): void
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

        $metalThursday =
            $this->criarMetalThursday(
                '2026-08-27',
                $criador,
                $nomeado,
            );

        $servico =
            app(
                ServicoNotificacaoPublicacaoMetalThursday::class,
            );

        self::assertTrue(
            $servico->processar(
                $metalThursday,
            ),
        );

        $metalThursday->refresh();

        self::assertNotNull(
            $metalThursday->publicacao_notificada_em,
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

        $momentoPrimeiroProcessamento =
            $metalThursday->publicacao_notificada_em;

        self::assertFalse(
            $servico->processar(
                $metalThursday,
            ),
        );

        $metalThursday->refresh();

        self::assertEquals(
            $momentoPrimeiroProcessamento,
            $metalThursday->publicacao_notificada_em,
        );

        Notification::assertCount(
            1,
        );
    }

    /**
     * Confirma que uma MetalThursday futura não é processada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_processa_metal_thursday_ainda_preparada(): void
    {
        Notification::fake();

        $criador =
            Utilizador::factory()
                ->create();

        $nomeado =
            Utilizador::factory()
                ->create();

        Utilizador::factory()
            ->create();

        $metalThursday =
            $this->criarMetalThursday(
                '2026-09-03',
                $criador,
                $nomeado,
            );

        $resultado =
            app(
                ServicoNotificacaoPublicacaoMetalThursday::class,
            )->processar(
                $metalThursday,
            );

        self::assertFalse(
            $resultado,
        );

        self::assertNull(
            $metalThursday
                ->refresh()
                ->publicacao_notificada_em,
        );

        Notification::assertNothingSent();
    }

    /**
     * Confirma que uma publicação anteriormente marcada não volta a ser
     * notificada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_reprocessa_publicacao_ja_notificada(): void
    {
        Notification::fake();

        $criador =
            Utilizador::factory()
                ->create();

        $nomeado =
            Utilizador::factory()
                ->create();

        Utilizador::factory()
            ->create();

        $metalThursday =
            $this->criarMetalThursday(
                '2026-08-20',
                $criador,
                $nomeado,
            );

        DB::table(
            'metal_thursdays',
        )
            ->where(
                'id',
                $metalThursday->getKey(),
            )
            ->update([
                MetalThursday::COLUNA_PUBLICACAO_NOTIFICADA_EM => CarbonImmutable::parse(
                    '2026-08-20 00:05:00',
                    'Europe/Lisbon',
                ),
            ]);

        $resultado =
            app(
                ServicoNotificacaoPublicacaoMetalThursday::class,
            )->processar(
                $metalThursday,
            );

        self::assertFalse(
            $resultado,
        );

        Notification::assertNothingSent();
    }

    /**
     * Confirma que uma publicação sem destinatários elegíveis também fica
     * marcada como processada, evitando novas tentativas infinitas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function marca_publicacao_sem_destinatarios_como_processada(): void
    {
        Notification::fake();

        $criador =
            Utilizador::factory()
                ->create();

        $nomeado =
            Utilizador::factory()
                ->create();

        $metalThursday =
            $this->criarMetalThursday(
                '2026-08-27',
                $criador,
                $nomeado,
            );

        self::assertTrue(
            app(
                ServicoNotificacaoPublicacaoMetalThursday::class,
            )->processar(
                $metalThursday,
            ),
        );

        self::assertNotNull(
            $metalThursday
                ->refresh()
                ->publicacao_notificada_em,
        );

        Notification::assertNothingSent();
    }

    /**
     * Confirma que uma falha durante o despacho da notificação não marca a
     * publicação como processada, permitindo nova tentativa posterior.
     *
     * @since 2.0.0
     */
    #[Test]
    public function falha_no_despacho_preserva_publicacao_por_notificar(): void
    {
        $criador =
            Utilizador::factory()
                ->create();

        $nomeado =
            Utilizador::factory()
                ->create();

        Utilizador::factory()
            ->create();

        $metalThursday =
            $this->criarMetalThursday(
                '2026-08-27',
                $criador,
                $nomeado,
            );

        Notification::shouldReceive(
            'send',
        )
            ->once()
            ->andThrow(
                new RuntimeException(
                    'Falha simulada no despacho da publicação.',
                ),
            );

        try {
            app(
                ServicoNotificacaoPublicacaoMetalThursday::class,
            )->processar(
                $metalThursday,
            );

            self::fail(
                'Era esperada uma falha durante o despacho da notificação.',
            );
        } catch (RuntimeException $excecao) {
            self::assertSame(
                'Falha simulada no despacho da publicação.',
                $excecao->getMessage(),
            );
        }

        self::assertNull(
            $metalThursday
                ->refresh()
                ->publicacao_notificada_em,
        );

        self::assertTrue(
            MetalThursday::query()
                ->publicadasPorNotificar()
                ->whereKey(
                    $metalThursday->getKey(),
                )
                ->exists(),
        );
    }

    /**
     * Cria uma MetalThursday com os intervenientes indicados.
     *
     * @param  string  $data  Data no formato AAAA-MM-DD.
     * @param  Utilizador  $criador  Criador e autor.
     * @param  Utilizador  $nomeado  Próximo nomeado.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        string $data,
        Utilizador $criador,
        Utilizador $nomeado,
    ): MetalThursday {
        $dataMetalThursday =
            CarbonImmutable::parse(
                $data,
            );

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    $dataMetalThursday->startOfMonth(),
                    $dataMetalThursday->endOfMonth(),
                )
                ->create();

        return MetalThursday::factory()
            ->comData(
                $dataMetalThursday,
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
