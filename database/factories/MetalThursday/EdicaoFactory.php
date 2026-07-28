<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\MetalThursday\Edicao;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
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
 *
 * @version 2.0.0
 */
final class EdicaoFactory extends Factory
{
    /**
     * Comprimento máximo do nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_NOME =
        255;

    /**
     * Comprimento máximo da ligação da compilação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_LIGACAO =
        2048;

    /**
     * Modelo associado à factory.
     *
     * @var class-string<Edicao>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     *
     * @version 2.0.0
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
     * @param  string  $nome  Nome pretendido.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o nome está vazio ou ultrapassa
     *                                  o comprimento máximo permitido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comNome(
        string $nome,
    ): static {
        $nomeNormalizado = Str::squish(
            $nome,
        );

        if ($nomeNormalizado === '') {
            throw new InvalidArgumentException(
                'O nome da edição não pode estar vazio.',
            );
        }

        if (
            mb_strlen(
                $nomeNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_NOME
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O nome da edição não pode exceder %d caracteres.',
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
     * Cria uma edição atualmente em curso.
     *
     * A edição começa numa data anterior ao momento atual e não possui uma
     * data de fim definida.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function emCurso(): static
    {
        return $this->state(
            static fn (): array => [
                'data_inicio' => CarbonImmutable::now()
                    ->subMonth()
                    ->startOfDay(),

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
     * @version 2.0.0
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

        return $this->state(
            static fn (): array => [
                'data_inicio' => $dataInicioNormalizada,

                'data_fim' => $dataFimNormalizada,
            ],
        );
    }

    /**
     * Define a ligação da compilação da edição.
     *
     * @param  string  $ligacao  Ligação absoluta da compilação.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a ligação está vazia, não é
     *                                  válida ou ultrapassa o limite da coluna.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function comLigacaoCompilacao(
        string $ligacao,
    ): static {
        $ligacaoNormalizada = trim(
            $ligacao,
        );

        if ($ligacaoNormalizada === '') {
            throw new InvalidArgumentException(
                'A ligação da compilação não pode estar vazia.',
            );
        }

        if (
            mb_strlen(
                $ligacaoNormalizada,
            ) > self::COMPRIMENTO_MAXIMO_LIGACAO
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'A ligação da compilação não pode exceder %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_LIGACAO,
                ),
            );
        }

        if (
            filter_var(
                $ligacaoNormalizada,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            throw new InvalidArgumentException(
                'A ligação da compilação deve ser um URL absoluto válido.',
            );
        }

        $esquema = parse_url(
            $ligacaoNormalizada,
            PHP_URL_SCHEME,
        );

        if (
            ! is_string(
                $esquema,
            )
            || ! in_array(
                strtolower(
                    $esquema,
                ),
                [
                    'http',
                    'https',
                ],
                true,
            )
        ) {
            throw new InvalidArgumentException(
                'A ligação da compilação deve utilizar HTTP ou HTTPS.',
            );
        }

        return $this->state(
            static fn (): array => [
                'ligacao_compilacao' => $ligacaoNormalizada,
            ],
        );
    }
}
