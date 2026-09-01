<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Musica;

use App\Http\Requests\Musica\CriarArtistaRequest;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a validação comum dos pedidos associados aos artistas.
 *
 * @since 2.0.0
 */
final class PedidoArtistaRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a origem geográfica pode ser omitida na criação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_omitir_origem_geografica_na_criacao(): void
    {
        $genero = Genero::factory()
            ->create();

        $pedido = new CriarArtistaRequest;

        $validador = Validator::make(
            [
                'nome' => 'Moonspell',

                'generos' => [
                    (int) $genero->getKey(),
                ],
            ],
            $pedido->rules(),
            $pedido->messages(),
            $pedido->attributes(),
        );

        self::assertFalse(
            $validador
                ->errors()
                ->has(
                    'origem_geografica_id',
                ),
        );
    }

    /**
     * Confirma que uma origem geográfica indicada tem de existir.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_origem_geografica_inexistente_na_criacao(): void
    {
        $genero = Genero::factory()
            ->create();

        $pedido = new CriarArtistaRequest;

        $validador = Validator::make(
            [
                'nome' => 'Moonspell',

                'origem_geografica_id' => PHP_INT_MAX,

                'generos' => [
                    (int) $genero->getKey(),
                ],
            ],
            $pedido->rules(),
            $pedido->messages(),
            $pedido->attributes(),
        );

        self::assertTrue(
            $validador
                ->errors()
                ->has(
                    'origem_geografica_id',
                ),
        );

        self::assertSame(
            'A origem geográfica selecionada não existe.',
            $validador
                ->errors()
                ->first(
                    'origem_geografica_id',
                ),
        );
    }

    /**
     * Confirma que uma origem geográfica nula é aceite na criação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_origem_geografica_nula_na_criacao(): void
    {
        $genero = Genero::factory()
            ->create();

        $pedido = new CriarArtistaRequest;

        $validador = Validator::make(
            [
                'nome' => 'Moonspell',

                'origem_geografica_id' => null,

                'generos' => [
                    (int) $genero->getKey(),
                ],
            ],
            $pedido->rules(),
            $pedido->messages(),
            $pedido->attributes(),
        );

        self::assertFalse(
            $validador
                ->errors()
                ->has(
                    'origem_geografica_id',
                ),
        );
    }
}
