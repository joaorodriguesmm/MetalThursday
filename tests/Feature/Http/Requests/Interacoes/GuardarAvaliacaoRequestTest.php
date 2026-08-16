<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Http\Requests\Interacoes\GuardarAvaliacaoRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a autorização do pedido utilizado para guardar avaliações.
 *
 * @since 2.0.0
 */
final class GuardarAvaliacaoRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o pedido utiliza exclusivamente o guard `sessao`.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pedido_utiliza_exclusivamente_guarda_sessao(): void
    {
        $utilizador =
            new Utilizador;

        $pedidoComSessao =
            new GuardarAvaliacaoRequest;

        $pedidoComSessao->setUserResolver(
            static fn (
                ?string $guarda = null,
            ): ?Utilizador => $guarda === 'sessao'
                ? $utilizador
                : null,
        );

        self::assertTrue(
            $pedidoComSessao->authorize(),
        );

        $pedidoApenasPredefinido =
            new GuardarAvaliacaoRequest;

        $pedidoApenasPredefinido->setUserResolver(
            static fn (
                ?string $guarda = null,
            ): ?Utilizador => $guarda === null
                ? $utilizador
                : null,
        );

        self::assertFalse(
            $pedidoApenasPredefinido->authorize(),
        );
    }

    /**
     * Confirma que um utilizador autenticado alcança a validação da
     * pontuação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_autenticado_recebe_erros_de_validacao(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'avaliacoes.guardar',
                    [
                        'tipoAvaliavel' => TipoEntidadeInteracao::MetalThursday->value,

                        'identificadorAvaliavel' => $metalThursday->getKey(),
                    ],
                ),
                [
                    'pontuacao' => [],
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'pontuacao',
            ]);
    }
}
