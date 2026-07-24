<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\MetalThursday\TipoSeccao;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para tipos de secção.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<TipoSeccao>
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class TipoSeccaoFactory extends Factory
{
    /**
     * Comprimento máximo do nome do tipo de secção.
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
     * @var class-string<TipoSeccao>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model =
        TipoSeccao::class;

    /**
     * Define os atributos predefinidos de um tipo de secção.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos do tipo de secção.
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

            'descricao' => $this
                ->faker
                ->sentence(),

            'tem_detalhes' => $this
                ->faker
                ->boolean(),
        ];
    }

    /**
     * Cria um tipo de secção que necessita de detalhes adicionais.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comDetalhes(): static
    {
        return $this->state(
            static fn (): array => [
                'tem_detalhes' => true,
            ],
        );
    }

    /**
     * Cria um tipo de secção que não necessita de detalhes adicionais.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function semDetalhes(): static
    {
        return $this->state(
            static fn (): array => [
                'tem_detalhes' => false,
            ],
        );
    }

    /**
     * Define os dados principais do tipo de secção.
     *
     * @param  string  $nome  Nome do tipo de secção.
     * @param  string  $descricao  Descrição do tipo de secção.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o nome ou a descrição estão
     *                                  vazios, ou quando o nome ultrapassa o
     *                                  comprimento máximo permitido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function comDados(
        string $nome,
        string $descricao,
    ): static {
        $nomeNormalizado =
            Str::squish(
                $nome,
            );

        $descricaoNormalizada =
            Str::squish(
                $descricao,
            );

        if ($nomeNormalizado === '') {
            throw new InvalidArgumentException(
                'O nome do tipo de secção não pode estar vazio.',
            );
        }

        if (
            mb_strlen(
                $nomeNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_NOME
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O nome do tipo de secção não pode exceder %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_NOME,
                ),
            );
        }

        if ($descricaoNormalizada === '') {
            throw new InvalidArgumentException(
                'A descrição do tipo de secção não pode estar vazia.',
            );
        }

        return $this->state(
            static fn (): array => [
                'nome' => $nomeNormalizado,

                'descricao' => $descricaoNormalizada,
            ],
        );
    }
}
