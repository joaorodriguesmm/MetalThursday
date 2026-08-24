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
 * Testa a autorização dos fluxos de criação de MetalThursdays.
 *
 * O fluxo genérico é administrativo. Utilizadores comuns, mesmo quando
 * possuem uma reserva pendente, publicam exclusivamente através da rota
 * explícita da respetiva reserva.
 *
 * @since 2.0.0
 */
final class AcessoCriacaoMetalThursdayTest extends TestCase
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
     * Confirma que um utilizador comum sem reserva não abre o formulário
     * administrativo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_sem_reserva_nao_abre_formulario_generico(): void
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
                    'metal-thursday.criar',
                ),
            )
            ->assertForbidden();
    }

    /**
     * Confirma que um utilizador comum sem reserva não submete pela rota
     * administrativa e é bloqueado antes da validação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_sem_reserva_nao_submete_criacao_generica(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'metal-thursday.guardar',
                ),
                [],
            )
            ->assertForbidden();
    }

    /**
     * Confirma que possuir uma reserva não devolve ao utilizador comum o
     * formulário genérico.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_com_reserva_nao_abre_formulario_generico(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
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
                    'metal-thursday.criar',
                ),
            )
            ->assertForbidden();
    }

    /**
     * Confirma que possuir uma reserva também não permite contornar a slot
     * através do POST genérico.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_com_reserva_nao_submete_criacao_generica(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
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
            ->postJson(
                route(
                    'metal-thursday.guardar',
                ),
                [],
            )
            ->assertForbidden();
    }

    /**
     * Confirma que um administrador pode abrir o formulário genérico sem
     * possuir reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_sem_reserva_abre_formulario_generico(): void
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
                    'metal-thursday.criar',
                ),
            )
            ->assertOk();
    }

    /**
     * Confirma que o POST genérico chega à validação quando o utilizador é
     * administrador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_pode_aceder_a_submissao_generica(): void
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
            ->postJson(
                route(
                    'metal-thursday.guardar',
                ),
                [],
            )
            ->assertUnprocessable();
    }

    /**
     * Confirma que o superadministrador mantém o acesso ao formulário
     * genérico.
     *
     * @since 2.0.0
     */
    #[Test]
    public function superadministrador_sem_reserva_abre_formulario_generico(): void
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
                    'metal-thursday.criar',
                ),
            )
            ->assertOk();
    }
}
