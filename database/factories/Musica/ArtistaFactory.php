<?php

declare(strict_types=1);

namespace Database\Factories\Musica;

use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Artista;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para artistas.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<Artista>
 *
 * @since 2.0.0
 */
final class ArtistaFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<Artista>
     *
     * @since 2.0.0
     */
    protected $model = Artista::class;

    /**
     * Define os atributos predefinidos de um artista.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos do artista.
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
            'nome' => Str::ucfirst(
                $nome,
            ),

            'origem_geografica_id' => OrigemGeografica::factory(),
        ];
    }

    /**
     * Define um nome conhecido para o artista.
     *
     * A normalização e a validação são delegadas ao contrato definitivo do
     * modelo.
     *
     * @param  string  $nome  Nome do artista.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o nome não é válido.
     *
     * @since 2.0.0
     */
    public function comNome(
        string $nome,
    ): static {
        $artista = new Artista;

        $artista->nome =
            $nome;

        return $this->state([
            'nome' => $artista->nome,
        ]);
    }

    /**
     * Associa o artista a uma origem geográfica existente.
     *
     * @param  OrigemGeografica  $origemGeografica  Origem do artista.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a origem geográfica não está
     *                                  persistida.
     *
     * @since 2.0.0
     */
    public function deOrigemGeografica(
        OrigemGeografica $origemGeografica,
    ): static {
        if (
            ! $origemGeografica->exists
            || $origemGeografica->getKey() === null
        ) {
            throw new InvalidArgumentException(
                'A origem geográfica associada ao artista deve estar persistida.',
            );
        }

        return $this->for(
            $origemGeografica,
            'origemGeografica',
        );
    }
}
