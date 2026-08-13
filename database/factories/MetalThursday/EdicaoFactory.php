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
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<Edicao>
 *
 * @since 2.0.0
 */
final class EdicaoFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<Edicao>
     *
     * @since 2.0.0
     */
    protected $model =
        Edicao::class;

    /**
     * Define os atributos predefinidos de uma edição.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos da edição.
     *
     * @since 2.0.0
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
                $this
                    ->faker
                    ->unique()
                    ->numberBetween(
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
     * Define o nome da edição.
     *
     * A normalização e a validação são delegadas ao contrato definitivo do
     * modelo.
     *
     * @param  string  $nome  Nome pretendido.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o nome não é válido.
     *
     * @since 2.0.0
     */
    public function comNome(
        string $nome,
    ): static {
        $edicao = new Edicao;

        $edicao->nome =
            $nome;

        return $this->state([
            'nome' => $edicao->nome,
        ]);
    }

    /**
     * Cria uma edição atualmente em curso.
     *
     * A edição começa numa data anterior ao momento atual e não possui uma
     * data de fim definida.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     */
    public function emCurso(): static
    {
        return $this->state([
            'data_inicio' => CarbonImmutable::now()
                ->subMonth()
                ->startOfDay(),

            'data_fim' => null,
        ]);
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
     */
    public function comPeriodo(
        CarbonInterface $dataInicio,
        ?CarbonInterface $dataFim = null,
    ): static {
        $dataInicioNormalizada = CarbonImmutable::instance(
            $dataInicio,
        )->startOfDay();

        $dataFimNormalizada = $dataFim !== null
            ? CarbonImmutable::instance(
                $dataFim,
            )->startOfDay()
            : null;

        if (
            $dataFimNormalizada !== null
            && $dataFimNormalizada->lessThan(
                $dataInicioNormalizada,
            )
        ) {
            throw new InvalidArgumentException(
                'A data final da edição não pode ser anterior à data inicial.',
            );
        }

        return $this->state([
            'data_inicio' => $dataInicioNormalizada,

            'data_fim' => $dataFimNormalizada,
        ]);
    }

    /**
     * Define a ligação da compilação da edição.
     *
     * A normalização e a validação são delegadas ao contrato definitivo do
     * modelo. Uma ligação vazia continua a ser rejeitada por este estado.
     *
     * @param  string  $ligacao  Ligação absoluta da compilação.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a ligação não é válida.
     *
     * @since 2.0.0
     */
    public function comLigacaoCompilacao(
        string $ligacao,
    ): static {
        $edicao = new Edicao;

        $edicao->ligacao_compilacao =
            $ligacao;

        $ligacaoNormalizada =
            $edicao->ligacao_compilacao;

        if (
            ! is_string($ligacaoNormalizada)
            || $ligacaoNormalizada === ''
        ) {
            throw new InvalidArgumentException(
                'A ligação da compilação não pode estar vazia.',
            );
        }

        return $this->state([
            'ligacao_compilacao' => $ligacaoNormalizada,
        ]);
    }
}
