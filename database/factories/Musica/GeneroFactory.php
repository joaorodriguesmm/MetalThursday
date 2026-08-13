<?php

declare(strict_types=1);

namespace Database\Factories\Musica;

use App\Models\Musica\Genero;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para géneros musicais.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<Genero>
 *
 * @since 2.0.0
 */
final class GeneroFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<Genero>
     *
     * @since 2.0.0
     */
    protected $model =
        Genero::class;

    /**
     * Define os atributos predefinidos de um género musical.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos do género.
     *
     * @since 2.0.0
     */
    public function definition(): array
    {
        $nome = $this
            ->faker
            ->unique()
            ->words(
                2,
                true,
            );

        return [
            'nome' => Str::limit(
                Str::ucfirst(
                    $nome,
                ),
                Genero::COMPRIMENTO_MAXIMO_NOME,
                '',
            ),
        ];
    }

    /**
     * Define um nome específico para o género musical.
     *
     * A normalização e a validação são delegadas ao contrato definitivo do
     * modelo.
     *
     * @param  string  $nome  Nome do género.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o nome não é válido.
     *
     * @since 2.0.0
     */
    public function comNome(
        string $nome,
    ): static {
        $genero = new Genero;

        $genero->nome =
            $nome;

        return $this->state([
            'nome' => $genero->nome,
        ]);
    }
}
