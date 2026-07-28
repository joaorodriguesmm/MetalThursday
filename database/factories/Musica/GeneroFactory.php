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
 *
 * @version 2.0.0
 */
final class GeneroFactory extends Factory
{
    /**
     * Comprimento máximo do nome do género.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private const COMPRIMENTO_MAXIMO_NOME =
        100;

    /**
     * Modelo associado à factory.
     *
     * @var class-string<Genero>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     *
     * @version 2.0.0
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
                self::COMPRIMENTO_MAXIMO_NOME,
                '',
            ),
        ];
    }

    /**
     * Define um nome específico para o género musical.
     *
     * @param  string  $nome  Nome do género.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o nome está vazio ou ultrapassa
     *                                  o comprimento máximo permitido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function comNome(
        string $nome,
    ): static {
        $nomeNormalizado = Str::squish(
            $nome,
        );

        if ($nomeNormalizado === '') {
            throw new InvalidArgumentException(
                'O nome do género musical não pode estar vazio.',
            );
        }

        if (
            mb_strlen(
                $nomeNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_NOME
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O nome do género musical não pode exceder %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_NOME,
                ),
            );
        }

        return $this->state(
            static fn (): array => [
                'nome' => $nomeNormalizado,
            ],
        );
    }
}
