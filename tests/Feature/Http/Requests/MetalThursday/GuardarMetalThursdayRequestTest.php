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
     * O identificador de edição enviado pelo cliente não pertence ao contrato
     * do pedido. A edição é determinada internamente através da data e, por
     * isso, um valor forjado nesse campo não deve produzir um erro próprio.
     *
     * @since 2.0.0
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
                'data',
                'nome',
                'autor_id',
                'proximo_nomeado_id',
                'seccoes',
            ])
            ->assertJsonMissingValidationErrors([
                'edicao_id',
            ]);
    }

    /**
     * Obtém dados deliberadamente inválidos para o pedido.
     *
     * O valor inválido de `edicao_id` é enviado deliberadamente para confirmar
     * que o campo não é aceite como fonte da associação à edição.
     *
     * @return array<string, mixed> Dados inválidos.
     *
     * @since 2.0.0
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
