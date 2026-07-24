<?php

declare(strict_types=1);

namespace Database\Factories\Musica;

use App\Models\Geografia\Pais;
use App\Models\Musica\Banda;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para bandas.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<Banda>
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class BandaFactory extends Factory
{
    /**
     * Comprimento máximo do nome da banda.
     *
     * Corresponde ao limite definido na base de dados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_NOME =
        255;

    /**
     * Modelo associado à factory.
     *
     * @var class-string<Banda>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model =
        Banda::class;

    /**
     * Define os atributos predefinidos de uma banda.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos da banda.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function definition(): array
    {
        $nome =
            $this
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

            'pais_id' => Pais::factory(),
        ];
    }

    /**
     * Define um nome conhecido para a banda.
     *
     * O nome é normalizado, removendo espaços exteriores e espaços
     * consecutivos.
     *
     * @param  string  $nome  Nome da banda.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o nome está vazio ou ultrapassa
     *                                  o comprimento máximo permitido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function comNome(
        string $nome,
    ): static {
        $nomeNormalizado =
            Str::squish(
                $nome,
            );

        if ($nomeNormalizado === '') {
            throw new InvalidArgumentException(
                'O nome da banda não pode estar vazio.',
            );
        }

        if (
            mb_strlen(
                $nomeNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_NOME
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O nome da banda não pode exceder %d caracteres.',
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

    /**
     * Associa a banda a um país existente.
     *
     * @param  Pais  $pais  País de origem da banda.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o país não está persistido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function dePais(
        Pais $pais,
    ): static {
        if (
            ! $pais->exists
            || $pais->getKey() === null
        ) {
            throw new InvalidArgumentException(
                'O país associado à banda deve estar persistido.',
            );
        }

        return $this->for(
            $pais,
            'pais',
        );
    }
}
