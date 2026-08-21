<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a gestão da disponibilidade para nomeações através do perfil.
 *
 * @since 2.0.0
 */
final class DisponibilidadeNomeacaoPerfilTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara os testes sem depender dos ficheiros compilados pelo Vite.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Confirma que a opção de disponibilidade é apresentada no perfil.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apresenta_disponibilidade_para_nomeacao_no_perfil(): void
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
                    'perfil.editar',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Disponível para novas nomeações',
            )
            ->assertSee(
                'name="disponivel_para_nomeacao"',
                false,
            );
    }

    /**
     * Confirma que o utilizador pode alterar a disponibilidade sem cancelar
     * uma reserva já atribuída.
     *
     * @since 2.0.0
     */
    #[Test]
    public function altera_disponibilidade_sem_cancelar_reserva_pendente(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $reserva = ReservaMetalThursday::factory()
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
            ->patch(
                route(
                    'perfil.atualizar',
                ),
                [
                    'nome' => $utilizador->nome,

                    'email' => $utilizador->email,

                    'disponivel_para_nomeacao' => '0',
                ],
            )
            ->assertRedirectToRoute(
                'perfil.editar',
            )
            ->assertSessionHasNoErrors();

        $utilizador->refresh();

        self::assertFalse(
            $utilizador->disponivel_para_nomeacao,
        );

        $this->assertDatabaseHas(
            'reservas_metal_thursday',
            [
                'id' => $reserva->getKey(),

                'responsavel_id' => $utilizador->getKey(),

                'metal_thursday_id' => null,
            ],
        );

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->patch(
                route(
                    'perfil.atualizar',
                ),
                [
                    'nome' => $utilizador->nome,

                    'email' => $utilizador->email,

                    'disponivel_para_nomeacao' => '1',
                ],
            )
            ->assertRedirectToRoute(
                'perfil.editar',
            )
            ->assertSessionHasNoErrors();

        $utilizador->refresh();

        self::assertTrue(
            $utilizador->disponivel_para_nomeacao,
        );

        $this->assertDatabaseHas(
            'reservas_metal_thursday',
            [
                'id' => $reserva->getKey(),

                'responsavel_id' => $utilizador->getKey(),

                'metal_thursday_id' => null,
            ],
        );
    }
}
