<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a autorização do pedido de gravação de MetalThursdays.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class GuardarMetalThursdayRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a política é aplicada antes das consultas de validação.
     *
     * Mesmo com dados inválidos, um utilizador sem permissão para atualizar o
     * registo deve receber uma resposta de proibição, e não uma resposta de
     * validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_utilizador_sem_autorizacao_antes_da_validacao(): void
    {
        $criador = Utilizador::factory()
            ->create();

        $outroUtilizador = Utilizador::factory()
            ->create();

        $this->actingAs(
            $criador,
            'sessao',
        );

        $metalThursday = MetalThursday::factory()
            ->comAutor(
                $criador,
            )
            ->comProximoNomeado(
                $criador,
            )
            ->create();

        $resposta = $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->patchJson(
                route(
                    'metal-thursday.atualizar',
                    $metalThursday,
                ),
                $this->dadosInvalidos(),
            );

        $resposta->assertForbidden();

        $this->assertDatabaseHas(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que um utilizador autorizado alcança a validação dos dados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_autorizado_recebe_erros_de_validacao(): void
    {
        $criador = Utilizador::factory()
            ->create();

        $this->actingAs(
            $criador,
            'sessao',
        );

        $metalThursday = MetalThursday::factory()
            ->comAutor(
                $criador,
            )
            ->comProximoNomeado(
                $criador,
            )
            ->create();

        $resposta = $this->patchJson(
            route(
                'metal-thursday.atualizar',
                $metalThursday,
            ),
            $this->dadosInvalidos(),
        );

        $resposta
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'edicao_id',
                'data',
                'nome',
                'autor_id',
                'proximo_nomeado_id',
                'seccoes',
            ]);
    }

    /**
     * Obtém dados deliberadamente inválidos para o pedido.
     *
     * @return array<string, mixed> Dados inválidos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function dadosInvalidos(): array
    {
        return [
            'edicao_id' => 'invalida',

            'data' => 'invalida',

            'nome' => [],

            'autor_id' => 'invalido',

            'proximo_nomeado_id' => 'invalido',

            'seccoes' => 'invalido',
        ];
    }
}
