<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a apresentação da ação genérica de criação de MetalThursday.
 *
 * @since 2.0.0
 */
final class AcoesLateraisMetalThursdayTest extends TestCase
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
     * Confirma que um utilizador comum com reserva publica através da respetiva
     * slot e não recebe o atalho genérico de criação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_com_reserva_nao_ve_acao_generica_criacao(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comResponsavel(
                $utilizador,
            )
            ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->get(
                route(
                    'inicio',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Preparar MetalThursday',
            )
            ->assertDontSee(
                'Criar MetalThursday',
            );
    }

    /**
     * Confirma que um utilizador comum sem reserva também não recebe o atalho
     * genérico de criação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_sem_reserva_nao_ve_acao_generica_criacao(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->get(
                route(
                    'inicio',
                ),
            )
            ->assertOk()
            ->assertDontSee(
                'Criar MetalThursday',
            );
    }

    /**
     * Confirma que um administrador mantém o atalho genérico de criação mesmo
     * sem possuir uma reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_sem_reserva_ve_acao_generica_criacao(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->get(
                route(
                    'inicio',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Criar MetalThursday',
            );
    }

    /**
     * Confirma que o superadministrador mantém o atalho genérico de criação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function superadministrador_ve_acao_generica_criacao(): void
    {
        $superAdministrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->get(
                route(
                    'inicio',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Criar MetalThursday',
            );
    }
}
