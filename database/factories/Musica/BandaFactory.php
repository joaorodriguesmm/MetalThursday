<?php

declare(strict_types=1);

namespace Database\Factories\Musica;

use App\Models\Geografia\Pais;
use App\Models\Musica\Banda;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cria dados de teste para bandas.
 *
 * @extends Factory<Banda>
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
class BandaFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<Banda>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model = Banda::class;

    /**
     * Define os atributos por omissão de uma banda.
     *
     * @return array<string, mixed> Atributos da banda.
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

            'pais_id' => Pais::factory(),
        ];
    }

    /**
     * Define um nome conhecido para a banda.
     *
     * @param  string  $nome  Nome da banda.
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

    /**
     * Associa a banda a um país existente.
     *
     * @param  Pais  $pais  País de origem da banda.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function dePais(
        Pais $pais,
    ): static {
        return $this->for(
            $pais,
            'pais',
        );
    }
}
