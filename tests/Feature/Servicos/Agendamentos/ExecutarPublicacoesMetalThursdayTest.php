<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Agendamentos;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoMetalThursdayCriada;
use App\Servicos\Agendamentos\ExecutarPublicacoesMetalThursday;
use App\Servicos\MetalThursday\ServicoNotificacaoPublicacaoMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa o executor das notificações temporais de publicação.
 *
 * @since 2.0.0
 */
final class ExecutarPublicacoesMetalThursdayTest extends TestCase
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
                '2026-08-27 00:00:00',
                'Europe/Lisbon',
            ),
        );
    }

    /**
     * Confirma que o executor processa apenas publicações já elegíveis e ainda
     * pendentes de notificação.
     *
     * Uma segunda execução não deve voltar a despachar as publicações já
     * tratadas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function processa_apenas_publicacoes_pendentes_sem_duplicar(): void
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

        $publicadaAnterior =
            $this->criarMetalThursday(
                $edicao,
                $criador,
                $nomeado,
                '2026-08-20',
            );

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

        $jaNotificada =
            $this->criarMetalThursday(
                $edicao,
                $criador,
                $nomeado,
                '2026-08-13',
            );

        $momentoNotificacaoAnterior =
            CarbonImmutable::parse(
                '2026-08-13 00:05:00',
                'Europe/Lisbon',
            );

        DB::table(
            'metal_thursdays',
        )
            ->where(
                'id',
                $jaNotificada->getKey(),
            )
            ->update([
                MetalThursday::COLUNA_PUBLICACAO_NOTIFICADA_EM => $momentoNotificacaoAnterior,
            ]);

        app(
            ExecutarPublicacoesMetalThursday::class,
        )(
            app(
                ServicoNotificacaoPublicacaoMetalThursday::class,
            ),
        );

        self::assertNotNull(
            $publicadaAnterior
                ->refresh()
                ->publicacao_notificada_em,
        );

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

        self::assertEquals(
            $momentoNotificacaoAnterior,
            $jaNotificada
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
            2,
        );

        app(
            ExecutarPublicacoesMetalThursday::class,
        )(
            app(
                ServicoNotificacaoPublicacaoMetalThursday::class,
            ),
        );

        Notification::assertCount(
            2,
        );
    }

    /**
     * Confirma que uma falha numa publicação não impede o processamento das
     * restantes publicações elegíveis do mesmo lote.
     *
     * A publicação que falha deve permanecer pendente para uma execução posterior,
     * enquanto a seguinte deve ficar normalmente marcada como processada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function falha_numa_publicacao_nao_impede_processamento_das_restantes(): void
    {
        $criador =
            Utilizador::factory()
                ->create();

        $nomeado =
            Utilizador::factory()
                ->create();

        Utilizador::factory()
            ->create();

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    CarbonImmutable::parse(
                        '2026-08-01',
                    ),
                    CarbonImmutable::parse(
                        '2026-08-31',
                    ),
                )
                ->create();

        $publicacaoComFalha =
            $this->criarMetalThursday(
                $edicao,
                $criador,
                $nomeado,
                '2026-08-20',
            );

        $publicacaoSeguinte =
            $this->criarMetalThursday(
                $edicao,
                $criador,
                $nomeado,
                '2026-08-27',
            );

        $numeroDespachos = 0;

        Notification::shouldReceive(
            'send',
        )
            ->twice()
            ->andReturnUsing(
                static function () use (
                    &$numeroDespachos,
                ): void {
                    $numeroDespachos++;

                    if ($numeroDespachos === 1) {
                        throw new RuntimeException(
                            'Falha simulada numa publicação.',
                        );
                    }
                },
            );

        app(
            ExecutarPublicacoesMetalThursday::class,
        )(
            app(
                ServicoNotificacaoPublicacaoMetalThursday::class,
            ),
        );

        self::assertNull(
            $publicacaoComFalha
                ->refresh()
                ->publicacao_notificada_em,
        );

        self::assertNotNull(
            $publicacaoSeguinte
                ->refresh()
                ->publicacao_notificada_em,
        );

        self::assertSame(
            2,
            $numeroDespachos,
        );

        self::assertTrue(
            MetalThursday::query()
                ->publicadasPorNotificar()
                ->whereKey(
                    $publicacaoComFalha->getKey(),
                )
                ->exists(),
        );

        self::assertFalse(
            MetalThursday::query()
                ->publicadasPorNotificar()
                ->whereKey(
                    $publicacaoSeguinte->getKey(),
                )
                ->exists(),
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
