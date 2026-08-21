<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integridade persistente das reservas de MetalThursday.
 *
 * @since 2.0.0
 */
final class ReservasMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma a estrutura base necessária para uma reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function possui_a_estrutura_base_das_reservas(): void
    {
        self::assertTrue(
            Schema::hasTable(
                'reservas_metal_thursday',
            ),
        );

        self::assertTrue(
            Schema::hasColumns(
                'reservas_metal_thursday',
                [
                    'id',
                    'data',
                    'responsavel_id',
                    'metal_thursday_id',
                    'created_at',
                    'updated_at',
                ],
            ),
        );
    }

    /**
     * Confirma que uma reserva pode ficar sem responsável e por cumprir.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_reserva_sem_responsavel_e_sem_metal_thursday(): void
    {
        $resultado = DB::table(
            'reservas_metal_thursday',
        )->insert([
            'data' => '2026-08-27',

            'responsavel_id' => null,

            'metal_thursday_id' => null,

            'created_at' => now(),

            'updated_at' => now(),
        ]);

        self::assertTrue(
            $resultado,
        );

        $this->assertDatabaseHas(
            'reservas_metal_thursday',
            [
                'data' => '2026-08-27',

                'responsavel_id' => null,

                'metal_thursday_id' => null,
            ],
        );
    }

    /**
     * Confirma que não podem existir duas reservas para a mesma data.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_duas_reservas_para_a_mesma_data(): void
    {
        DB::table(
            'reservas_metal_thursday',
        )->insert([
            'data' => '2026-08-27',

            'created_at' => now(),

            'updated_at' => now(),
        ]);

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'reservas_metal_thursday',
        )->insert([
            'data' => '2026-08-27',

            'created_at' => now(),

            'updated_at' => now(),
        ]);
    }

    /**
     * Confirma que o responsável de uma reserva tem de existir.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_reserva_com_responsavel_inexistente(): void
    {
        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'reservas_metal_thursday',
        )->insert([
            'data' => '2026-08-27',

            'responsavel_id' => 999999,

            'created_at' => now(),

            'updated_at' => now(),
        ]);
    }

    /**
     * Confirma que a MetalThursday associada a uma reserva tem de existir.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_reserva_com_metal_thursday_inexistente(): void
    {
        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'reservas_metal_thursday',
        )->insert([
            'data' => '2026-08-27',

            'metal_thursday_id' => 999999,

            'created_at' => now(),

            'updated_at' => now(),
        ]);
    }

    /**
     * Confirma que uma MetalThursday só pode cumprir uma reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_mesma_metal_thursday_em_duas_reservas(): void
    {
        $metalThursday = MetalThursday::factory()
            ->create();

        DB::table(
            'reservas_metal_thursday',
        )->insert([
            'data' => '2026-08-27',

            'metal_thursday_id' => $metalThursday->getKey(),

            'created_at' => now(),

            'updated_at' => now(),
        ]);

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'reservas_metal_thursday',
        )->insert([
            'data' => '2026-09-03',

            'metal_thursday_id' => $metalThursday->getKey(),

            'created_at' => now(),

            'updated_at' => now(),
        ]);
    }

    /**
     * Confirma que a eliminação do responsável preserva a reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preserva_reserva_quando_responsavel_e_eliminado(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        DB::table(
            'reservas_metal_thursday',
        )->insert([
            'data' => '2026-08-27',

            'responsavel_id' => $responsavel->getKey(),

            'created_at' => now(),

            'updated_at' => now(),
        ]);

        $responsavel->deleteOrFail();

        $this->assertDatabaseHas(
            'reservas_metal_thursday',
            [
                'data' => '2026-08-27',

                'responsavel_id' => null,
            ],
        );
    }
}
