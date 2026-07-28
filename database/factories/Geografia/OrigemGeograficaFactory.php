<?php

declare(strict_types=1);

namespace Database\Factories\Geografia;

use App\Models\Geografia\OrigemGeografica;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para origens geográficas.
 *
 * Uma origem geográfica pode representar um país, uma nação constituinte,
 * um território ou uma origem internacional agregada.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<OrigemGeografica>
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
final class OrigemGeograficaFactory extends Factory
{
    /**
     * Comprimento máximo do nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_NOME = 100;

    /**
     * Comprimento máximo do código.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_CODIGO = 8;

    /**
     * Modelo associado à factory.
     *
     * @var class-string<OrigemGeografica>
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected $model = OrigemGeografica::class;

    /**
     * Define os atributos predefinidos de uma origem geográfica.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos da origem geográfica.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function definition(): array
    {
        return [
            'nome' => $this
                ->faker
                ->unique()
                ->country(),

            'codigo' => Str::upper(
                $this
                    ->faker
                    ->unique()
                    ->countryCode(),
            ),
        ];
    }

    /**
     * Define uma origem geográfica conhecida.
     *
     * O nome é normalizado e o código é convertido para maiúsculas.
     *
     * @param  string  $nome  Nome da origem geográfica.
     * @param  string  $codigo  Código geográfico da aplicação.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando os dados não respeitam os
     *                                  contratos da base de dados.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function comDados(
        string $nome,
        string $codigo,
    ): static {
        $nomeNormalizado = Str::squish(
            $nome,
        );

        $codigoNormalizado = Str::upper(
            trim(
                $codigo,
            ),
        );

        if ($nomeNormalizado === '') {
            throw new InvalidArgumentException(
                'O nome da origem geográfica não pode estar vazio.',
            );
        }

        if (
            mb_strlen(
                $nomeNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_NOME
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O nome da origem geográfica não pode exceder %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_NOME,
                ),
            );
        }

        if (
            strlen(
                $codigoNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_CODIGO
            || preg_match(
                '/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/',
                $codigoNormalizado,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'O código da origem geográfica deve conter entre 2 e 8 caracteres alfanuméricos, podendo incluir hífenes interiores.',
            );
        }

        if (
            strlen(
                $codigoNormalizado,
            ) < 2
        ) {
            throw new InvalidArgumentException(
                'O código da origem geográfica deve conter pelo menos dois caracteres.',
            );
        }

        return $this->state(
            static fn (): array => [
                'nome' => $nomeNormalizado,

                'codigo' => $codigoNormalizado,
            ],
        );
    }
}
