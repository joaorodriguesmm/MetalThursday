<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a publicação quando a slot seguinte já existe.
 *
 * Uma reserva seguinte previamente criada é autoritativa e não deve obrigar
 * a publicação anterior a efetuar uma nova nomeação.
 *
 * @since 2.0.0
 */
final class PublicacaoReservaComSlotSeguinteExistenteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara cada teste sem depender dos ficheiros produzidos pelo Vite.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Notification::fake();
    }

    /**
     * Confirma que uma slot seguinte sem responsável permite publicar sem
     * indicar um novo nomeado.
     *
     * A slot seguinte deve permanecer intacta e a MetalThursday criada deve
     * espelhar a ausência de responsável através de proximo_nomeado_id nulo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function publica_sem_nomeado_quando_slot_seguinte_ja_existe_sem_responsavel(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $dataPublicacao = CarbonImmutable::parse(
            '2026-09-24',
        );

        $reservaAtual = ReservaMetalThursday::factory()
            ->comData(
                $dataPublicacao,
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        $reservaSeguinte = ReservaMetalThursday::factory()
            ->comData(
                $dataPublicacao->addWeek(),
            )
            ->semResponsavel()
            ->create();

        Edicao::factory()
            ->comPeriodo(
                $dataPublicacao->startOfMonth(),
                $dataPublicacao->endOfMonth(),
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
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
                    $reservaAtual,
                ),
                [
                    'seccoes' => [
                        [
                            'id' => null,

                            'tipo_seccao_id' => $tipoSeccao->getKey(),

                            'descricao' => 'Publicação com slot seguinte já existente.',
                        ],
                    ],
                ],
            )
            ->assertCreated();

        $metalThursday = MetalThursday::query()
            ->where(
                'data',
                $dataPublicacao->toDateString(),
            )
            ->firstOrFail();

        self::assertSame(
            $responsavel->getKey(),
            $metalThursday->autor_id,
        );

        self::assertNull(
            $metalThursday->proximo_nomeado_id,
        );

        self::assertSame(
            $metalThursday->getKey(),
            $reservaAtual
                ->refresh()
                ->metal_thursday_id,
        );

        $reservaSeguinte->refresh();

        self::assertNull(
            $reservaSeguinte->responsavel_id,
        );

        self::assertNull(
            $reservaSeguinte->metal_thursday_id,
        );
    }

    /**
     * Confirma que uma slot seguinte já atribuída permite publicar sem indicar
     * novamente o próximo nomeado.
     *
     * A MetalThursday criada deve espelhar o responsável da reserva seguinte,
     * sem substituir nem recriar essa slot.
     *
     * @since 2.0.0
     */
    #[Test]
    public function publica_sem_nomeado_quando_slot_seguinte_ja_existe_com_responsavel(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $responsavelSeguinte = Utilizador::factory()
            ->create();

        $dataPublicacao = CarbonImmutable::parse(
            '2026-09-17',
        );

        $reservaAtual = ReservaMetalThursday::factory()
            ->comData(
                $dataPublicacao,
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        $reservaSeguinte = ReservaMetalThursday::factory()
            ->comData(
                $dataPublicacao->addWeek(),
            )
            ->comResponsavel(
                $responsavelSeguinte,
            )
            ->create();

        Edicao::factory()
            ->comPeriodo(
                $dataPublicacao->startOfMonth(),
                $dataPublicacao->endOfMonth(),
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
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
                    $reservaAtual,
                ),
                [
                    'seccoes' => [
                        [
                            'id' => null,

                            'tipo_seccao_id' => $tipoSeccao->getKey(),

                            'descricao' => 'Publicação com slot seguinte já atribuída.',
                        ],
                    ],
                ],
            )
            ->assertCreated();

        $metalThursday = MetalThursday::query()
            ->where(
                'data',
                $dataPublicacao->toDateString(),
            )
            ->firstOrFail();

        self::assertSame(
            $responsavel->getKey(),
            $metalThursday->autor_id,
        );

        self::assertSame(
            $responsavelSeguinte->getKey(),
            $metalThursday->proximo_nomeado_id,
        );

        self::assertSame(
            $metalThursday->getKey(),
            $reservaAtual
                ->refresh()
                ->metal_thursday_id,
        );

        $reservaSeguinte->refresh();

        self::assertSame(
            $responsavelSeguinte->getKey(),
            $reservaSeguinte->responsavel_id,
        );

        self::assertNull(
            $reservaSeguinte->metal_thursday_id,
        );

        self::assertSame(
            1,
            ReservaMetalThursday::query()
                ->where(
                    'data',
                    $dataPublicacao
                        ->addWeek()
                        ->toDateString(),
                )
                ->count(),
        );
    }
}
