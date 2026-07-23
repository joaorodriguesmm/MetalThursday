<?php

declare(strict_types=1);

namespace Database\Factories\Musica;

use App\Models\Musica\Genero;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cria dados de teste para géneros musicais.
 *
 * @extends Factory<Genero>
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
class GeneroFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<Genero>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model = Genero::class;

    /**
     * Define os atributos por omissão de um género musical.
     *
     * @return array<string, mixed> Atributos do género.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function definition(): array
    {
        return [
            'nome' => ucfirst(
                $this
                    ->faker
                    ->unique()
                    ->words(
                        2,
                        true,
                    ),
            ),
        ];
    }

    /**
     * Define um nome específico para o género.
     *
     * @param  string  $nome  Nome do género.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comNome(
        string $nome,
    ): static {
        return $this->state(
            fn (): array => [
                'nome' => $nome,
            ],
        );
    }
}
