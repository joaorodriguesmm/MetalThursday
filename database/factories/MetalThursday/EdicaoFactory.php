<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\MetalThursday\Edicao;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * Cria dados de teste para edições do MetalThursday.
 *
 * @extends Factory<Edicao>
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
class EdicaoFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<Edicao>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model = Edicao::class;

    /**
     * Define os atributos por omissão de uma edição.
     *
     * @return array<string, mixed> Atributos da edição.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function definition(): array
    {
        $dataInicio = CarbonImmutable::instance(
            $this->faker->dateTimeBetween(
                '-5 years',
                '-3 months',
            ),
        )->startOfDay();

        $dataFim = $dataInicio->addMonths(
            $this->faker->numberBetween(
                1,
                12,
            ),
        );

        return [
            'nome' => sprintf(
                'Edição %d',
                $this->faker->unique()->numberBetween(
                    1,
                    100000,
                ),
            ),
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'ligacao_compilacao' => null,
        ];
    }

    /**
     * Cria uma edição ainda em curso.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function emCurso(): static
    {
        return $this->state(
            fn (): array => [
                'data_fim' => null,
            ],
        );
    }

    /**
     * Define o período temporal da edição.
     *
     * @param  CarbonInterface  $dataInicio  Data inicial da edição.
     * @param  CarbonInterface|null  $dataFim  Data final da edição.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a data final é anterior à
     *                                  data inicial.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comPeriodo(
        CarbonInterface $dataInicio,
        ?CarbonInterface $dataFim = null,
    ): static {
        if (
            $dataFim !== null
            && $dataFim->lessThan(
                $dataInicio,
            )
        ) {
            throw new InvalidArgumentException(
                'A data final da edição não pode ser anterior à data inicial.',
            );
        }

        return $this->state(
            fn (): array => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
            ],
        );
    }

    /**
     * Define a ligação da compilação da edição.
     *
     * @param  string  $ligacao  Ligação da compilação.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comLigacaoCompilacao(
        string $ligacao,
    ): static {
        return $this->state(
            fn (): array => [
                'ligacao_compilacao' => $ligacao,
            ],
        );
    }
}
