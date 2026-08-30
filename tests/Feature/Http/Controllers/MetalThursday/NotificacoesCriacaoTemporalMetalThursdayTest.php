<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Notifications\NotificacaoMetalThursdayCriada;
use App\Notifications\NotificacaoUtilizadorNomeado;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a separação temporal entre nomeação e publicação.
 *
 * @since 2.0.0
 */
final class NotificacoesCriacaoTemporalMetalThursdayTest extends TestCase
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

        $this->withoutVite();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-27 12:00:00',
                'Europe/Lisbon',
            ),
        );
    }

    /**
     * Confirma que uma preparação futura comunica a nomeação seguinte sem
     * anunciar prematuramente a publicação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preparacao_futura_notifica_nomeado_sem_notificar_publicacao(): void
    {
        Notification::fake();

        $responsavel =
            Utilizador::factory()
                ->create();

        $proximoNomeado =
            Utilizador::factory()
                ->create();

        $destinatarioPublicacao =
            Utilizador::factory()
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
                ->comData(
                    CarbonImmutable::parse(
                        '2026-09-03',
                    ),
                )
                ->comResponsavel(
                    $responsavel,
                )
                ->create();

        $this->criarEdicao();

        $tipoSeccao =
            TipoSeccao::factory()
                ->semDetalhes()
                ->create();

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->postJson(
                route(
                    'metal-thursday.reservas.guardar',
                    $reserva,
                ),
                $this->obterDadosSubmissao(
                    $responsavel,
                    $proximoNomeado,
                    $tipoSeccao,
                    '2026-09-03',
                ),
            )
            ->assertCreated()
            ->assertJsonPath(
                'mensagem',
                'MetalThursday marcada como preparada com sucesso.',
            );

        $metalThursday =
            MetalThursday::query()
                ->where(
                    'data',
                    '2026-09-03',
                )
                ->firstOrFail();

        self::assertNull(
            $metalThursday->publicacao_notificada_em,
        );

        Notification::assertSentTo(
            $proximoNomeado,
            NotificacaoUtilizadorNomeado::class,
        );

        Notification::assertNotSentTo(
            $proximoNomeado,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertNotSentTo(
            $destinatarioPublicacao,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertNotSentTo(
            $responsavel,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertCount(
            1,
        );
    }

    /**
     * Confirma que uma MetalThursday finalizada no próprio dia é publicada e
     * notificada imediatamente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function finalizacao_no_proprio_dia_notifica_publicacao_imediatamente(): void
    {
        Notification::fake();

        $responsavel =
            Utilizador::factory()
                ->create();

        $proximoNomeado =
            Utilizador::factory()
                ->create();

        $destinatarioPublicacao =
            Utilizador::factory()
                ->create();

        $reserva =
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

        $this->criarEdicao();

        $tipoSeccao =
            TipoSeccao::factory()
                ->semDetalhes()
                ->create();

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->postJson(
                route(
                    'metal-thursday.reservas.guardar',
                    $reserva,
                ),
                $this->obterDadosSubmissao(
                    $responsavel,
                    $proximoNomeado,
                    $tipoSeccao,
                    '2026-08-27',
                ),
            )
            ->assertCreated()
            ->assertJsonPath(
                'mensagem',
                'MetalThursday publicada com sucesso.',
            );

        $metalThursday =
            MetalThursday::query()
                ->where(
                    'data',
                    '2026-08-27',
                )
                ->firstOrFail();

        self::assertNotNull(
            $metalThursday->publicacao_notificada_em,
        );

        Notification::assertSentTo(
            $proximoNomeado,
            NotificacaoUtilizadorNomeado::class,
        );

        Notification::assertSentTo(
            $destinatarioPublicacao,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertNotSentTo(
            $responsavel,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertNotSentTo(
            $proximoNomeado,
            NotificacaoMetalThursdayCriada::class,
        );

        Notification::assertCount(
            2,
        );
    }

    /**
     * Cria a edição utilizada nos cenários temporais.
     *
     * @return Edicao Edição criada.
     *
     * @since 2.0.0
     */
    private function criarEdicao(): Edicao
    {
        return Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-08-01',
                ),
                CarbonImmutable::parse(
                    '2026-09-30',
                ),
            )
            ->create();
    }

    /**
     * Obtém os dados necessários à finalização da reserva.
     *
     * @param  Utilizador  $responsavel  Responsável pela reserva.
     * @param  Utilizador  $proximoNomeado  Próximo utilizador nomeado.
     * @param  TipoSeccao  $tipoSeccao  Tipo da secção criada.
     * @param  string  $data  Data submetida no formato AAAA-MM-DD.
     * @return array<string, mixed> Dados da submissão.
     *
     * @since 2.0.0
     */
    private function obterDadosSubmissao(
        Utilizador $responsavel,
        Utilizador $proximoNomeado,
        TipoSeccao $tipoSeccao,
        string $data,
    ): array {
        return [
            'data' => $data,

            'nome' => null,

            'autor_id' => $responsavel->getKey(),

            'proximo_nomeado_id' => $proximoNomeado->getKey(),

            'seccoes' => [
                [
                    'id' => null,

                    'tipo_seccao_id' => $tipoSeccao->getKey(),

                    'descricao' => 'Secção temporal de teste.',
                ],
            ],
        ];
    }
}
