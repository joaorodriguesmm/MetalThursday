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
 * @version 2.1.0
 */
final class OrigemGeograficaFactory extends Factory
{
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
     * @version 2.1.0
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
            ) > OrigemGeografica::COMPRIMENTO_MAXIMO_NOME
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O nome da origem geográfica não pode exceder %d caracteres.',
                    OrigemGeografica::COMPRIMENTO_MAXIMO_NOME,
                ),
            );
        }

        $comprimentoCodigo = strlen(
            $codigoNormalizado,
        );

        if (
            $comprimentoCodigo < OrigemGeografica::COMPRIMENTO_MINIMO_CODIGO
            || $comprimentoCodigo > OrigemGeografica::COMPRIMENTO_MAXIMO_CODIGO
            || preg_match(
                '/\A[A-Z0-9]+(?:-[A-Z0-9]+)*\z/',
                $codigoNormalizado,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O código da origem geográfica deve conter entre %d e %d caracteres alfanuméricos, podendo incluir hífenes interiores.',
                    OrigemGeografica::COMPRIMENTO_MINIMO_CODIGO,
                    OrigemGeografica::COMPRIMENTO_MAXIMO_CODIGO,
                ),
            );
        }

        return $this->state([
            'nome' => $nomeNormalizado,

            'codigo' => $codigoNormalizado,
        ]);
    }
}
