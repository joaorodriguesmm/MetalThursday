<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a publicação administrativa quando já existe a reserva seguinte.
 *
 * @since 2.0.0
 */
final class PublicacaoAdministrativaComSlotSeguinteExistenteTest extends TestCase
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
    }

    /**
     * Confirma que a criação administrativa numa data que não seja
     * quinta-feira considera como slot seguinte a próxima quinta-feira.
     *
     * Quando essa reserva já existe, não deve ser exigido um novo nomeado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_publica_em_data_nao_quinta_sem_nomeado_quando_proxima_quinta_ja_tem_reserva(): void
    {
        Notification::fake();

        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $autor = Utilizador::factory()
            ->create();

        $this->actingAs(
            $administrador,
            'sessao',
        );

        Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();

        $reservaSeguinte = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-22',
                ),
            )
            ->semResponsavel()
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->postJson(
            route(
                'metal-thursday.guardar',
            ),
            [
                'data' => '2026-01-19',

                'nome' => null,

                'autor_id' => $autor->getKey(),

                'seccoes' => [
                    [
                        'id' => null,

                        'tipo_seccao_id' => $tipoSeccao->getKey(),

                        'descricao' => 'Secção de teste.',
                    ],
                ],
            ],
        )
            ->assertCreated();

        $this->assertDatabaseHas(
            'metal_thursdays',
            [
                'data' => '2026-01-19',

                'autor_id' => $autor->getKey(),

                'proximo_nomeado_id' => null,
            ],
        );

        $this->assertDatabaseHas(
            'reservas_metal_thursday',
            [
                'id' => $reservaSeguinte->getKey(),

                'data' => '2026-01-22',

                'responsavel_id' => null,

                'metal_thursday_id' => null,
            ],
        );

        $this->assertDatabaseCount(
            'reservas_metal_thursday',
            1,
        );
    }
}
