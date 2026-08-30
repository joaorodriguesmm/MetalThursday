<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Models\MetalThursday\RascunhoMetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integridade persistente dos rascunhos de MetalThursday.
 *
 * @since 2.0.0
 */
final class RascunhosMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma a estrutura base necessária para um rascunho.
     *
     * Os dados autoritativos da reserva não são duplicados nesta tabela.
     *
     * @since 2.0.0
     */
    #[Test]
    public function possui_a_estrutura_base_dos_rascunhos(): void
    {
        self::assertTrue(
            Schema::hasTable(
                'rascunhos_metal_thursday',
            ),
        );

        self::assertTrue(
            Schema::hasColumns(
                'rascunhos_metal_thursday',
                [
                    'id',
                    'reserva_metal_thursday_id',
                    'dados',
                    'created_at',
                    'updated_at',
                ],
            ),
        );

        self::assertFalse(
            Schema::hasColumn(
                'rascunhos_metal_thursday',
                'data',
            ),
        );

        self::assertFalse(
            Schema::hasColumn(
                'rascunhos_metal_thursday',
                'autor_id',
            ),
        );

        self::assertFalse(
            Schema::hasColumn(
                'rascunhos_metal_thursday',
                'edicao_id',
            ),
        );
    }

    /**
     * Confirma que o conteúdo JSON é convertido para array e que ambas as
     * relações entre a reserva e o rascunho funcionam.
     *
     * @since 2.0.0
     */
    #[Test]
    public function persiste_dados_e_resolve_relacoes_com_a_reserva(): void
    {
        $reserva = ReservaMetalThursday::factory()
            ->create();

        $dados = [
            'nome' => 'Especial ainda incompleto',

            'proximo_nomeado_id' => null,

            'seccoes' => [
                [
                    'tipo_seccao_id' => null,

                    'titulo' => 'Secção ainda incompleta',

                    'descricao' => '',
                ],
            ],
        ];

        $rascunho = RascunhoMetalThursday::factory()
            ->comReserva(
                $reserva,
            )
            ->comDados(
                $dados,
            )
            ->create();

        $rascunhoAtualizado =
            $rascunho->fresh();

        self::assertInstanceOf(
            RascunhoMetalThursday::class,
            $rascunhoAtualizado,
        );

        self::assertSame(
            $dados,
            $rascunhoAtualizado->dados,
        );

        self::assertTrue(
            $rascunhoAtualizado
                ->reservaMetalThursday
                ->is(
                    $reserva,
                ),
        );

        $reservaAtualizada =
            $reserva->fresh();

        self::assertInstanceOf(
            ReservaMetalThursday::class,
            $reservaAtualizada,
        );

        self::assertInstanceOf(
            RascunhoMetalThursday::class,
            $reservaAtualizada->rascunho,
        );

        self::assertTrue(
            $reservaAtualizada
                ->rascunho
                ->is(
                    $rascunho,
                ),
        );
    }

    /**
     * Confirma que uma reserva não pode possuir mais de um rascunho.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_dois_rascunhos_para_a_mesma_reserva(): void
    {
        $reserva = ReservaMetalThursday::factory()
            ->create();

        RascunhoMetalThursday::factory()
            ->comReserva(
                $reserva,
            )
            ->create();

        $this->expectException(
            QueryException::class,
        );

        RascunhoMetalThursday::factory()
            ->comReserva(
                $reserva,
            )
            ->create();
    }

    /**
     * Confirma que um rascunho apenas pode apontar para uma reserva existente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_rascunho_com_reserva_inexistente(): void
    {
        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'rascunhos_metal_thursday',
        )->insert([
            'reserva_metal_thursday_id' => 999999,

            'dados' => json_encode(
                [
                    'nome' => null,

                    'proximo_nomeado_id' => null,

                    'seccoes' => [],
                ],
                JSON_THROW_ON_ERROR,
            ),

            'created_at' => now(),

            'updated_at' => now(),
        ]);
    }

    /**
     * Confirma que eliminar uma reserva elimina também o respetivo rascunho.
     *
     * O rascunho não constitui histórico autónomo depois de a reserva deixar
     * de existir.
     *
     * @since 2.0.0
     */
    #[Test]
    public function elimina_rascunho_quando_reserva_e_eliminada(): void
    {
        $reserva = ReservaMetalThursday::factory()
            ->create();

        $rascunho = RascunhoMetalThursday::factory()
            ->comReserva(
                $reserva,
            )
            ->create();

        $identificadorRascunho =
            $rascunho->getKey();

        $reserva->deleteOrFail();

        $this->assertDatabaseMissing(
            'rascunhos_metal_thursday',
            [
                'id' => $identificadorRascunho,
            ],
        );
    }

    /**
     * Confirma que a existência de um rascunho não cumpre a reserva.
     *
     * A reserva apenas deixa de estar pendente quando uma MetalThursday final
     * lhe é associada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rascunho_nao_cumpre_a_reserva(): void
    {
        $reserva = ReservaMetalThursday::factory()
            ->create();

        RascunhoMetalThursday::factory()
            ->comReserva(
                $reserva,
            )
            ->create();

        $reservaAtualizada =
            $reserva->fresh();

        self::assertInstanceOf(
            ReservaMetalThursday::class,
            $reservaAtualizada,
        );

        self::assertTrue(
            $reservaAtualizada->estaPendente(),
        );

        self::assertFalse(
            $reservaAtualizada->estaCumprida(),
        );

        self::assertNull(
            $reservaAtualizada->metal_thursday_id,
        );

        self::assertInstanceOf(
            RascunhoMetalThursday::class,
            $reservaAtualizada->rascunho,
        );
    }
}
