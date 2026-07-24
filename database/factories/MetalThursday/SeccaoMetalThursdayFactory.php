<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para secções de um registo MetalThursday.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<SeccaoMetalThursday>
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class SeccaoMetalThursdayFactory extends Factory
{
    /**
     * Ordem mínima permitida para uma secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ORDEM_MINIMA =
        1;

    /**
     * Primeiro ano aceite para os dados musicais da secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ANO_MINIMO =
        1950;

    /**
     * Modelo associado à factory.
     *
     * @var class-string<SeccaoMetalThursday>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model =
        SeccaoMetalThursday::class;

    /**
     * Define os atributos predefinidos de uma secção.
     *
     * Por predefinição, é criado um tipo de secção que não necessita de
     * detalhes adicionais e não é associada qualquer banda.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos da secção.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function definition(): array
    {
        return [
            'metal_thursday_id' => MetalThursday::factory(),

            'tipo_seccao_id' => TipoSeccao::factory()
                ->semDetalhes(),

            'ordem' => self::ORDEM_MINIMA,

            'titulo' => null,

            'descricao' => null,

            'banda_id' => null,

            'ligacao' => null,

            'tipo_incorporacao' => null,

            'ano' => null,
        ];
    }

    /**
     * Associa a secção a um registo MetalThursday existente.
     *
     * @param  MetalThursday  $metalThursday  Registo MetalThursday pretendido.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o registo não está persistido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function paraMetalThursday(
        MetalThursday $metalThursday,
    ): static {
        $this->validarModeloPersistido(
            $metalThursday,
            'O registo MetalThursday associado à secção deve estar persistido.',
        );

        return $this->for(
            $metalThursday,
            'metalThursday',
        );
    }

    /**
     * Associa um tipo existente à secção.
     *
     * @param  TipoSeccao  $tipoSeccao  Tipo de secção pretendido.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o tipo não está persistido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function doTipo(
        TipoSeccao $tipoSeccao,
    ): static {
        $this->validarModeloPersistido(
            $tipoSeccao,
            'O tipo associado à secção deve estar persistido.',
        );

        return $this->for(
            $tipoSeccao,
            'tipoSeccao',
        );
    }

    /**
     * Associa uma banda à secção.
     *
     * Quando nenhuma banda é indicada, é criada uma através da respetiva
     * factory.
     *
     * @param  Banda|null  $banda  Banda pretendida.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a banda indicada não está
     *                                  persistida.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function comBanda(
        ?Banda $banda = null,
    ): static {
        if ($banda !== null) {
            $this->validarModeloPersistido(
                $banda,
                'A banda associada à secção deve estar persistida.',
            );
        }

        return $this->for(
            $banda
                ?? Banda::factory(),
            'banda',
        );
    }

    /**
     * Define a posição da secção dentro do registo MetalThursday.
     *
     * @param  int  $ordem  Posição positiva da secção.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a posição não é positiva.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function naOrdem(
        int $ordem,
    ): static {
        if ($ordem < self::ORDEM_MINIMA) {
            throw new InvalidArgumentException(
                'A ordem da secção deve ser um número inteiro positivo.',
            );
        }

        return $this->state(
            static fn (): array => [
                'ordem' => $ordem,
            ],
        );
    }

    /**
     * Cria uma secção com informação detalhada.
     *
     * É criado um tipo que necessita de detalhes. Quando nenhuma banda é
     * fornecida, é criada uma através da respetiva factory.
     *
     * @param  Banda|null  $banda  Banda pretendida.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a banda indicada não está
     *                                  persistida.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function comDetalhes(
        ?Banda $banda = null,
    ): static {
        if ($banda !== null) {
            $this->validarModeloPersistido(
                $banda,
                'A banda associada à secção detalhada deve estar persistida.',
            );
        }

        $titulo =
            Str::ucfirst(
                $this
                    ->faker
                    ->words(
                        3,
                        true,
                    ),
            );

        $descricao =
            $this
                ->faker
                ->paragraph();

        $ano =
            $this
                ->faker
                ->numberBetween(
                    self::ANO_MINIMO,
                    CarbonImmutable::now()->year,
                );

        return $this
            ->for(
                TipoSeccao::factory()
                    ->comDetalhes(),
                'tipoSeccao',
            )
            ->for(
                $banda
                    ?? Banda::factory(),
                'banda',
            )
            ->state(
                static fn (): array => [
                    'titulo' => $titulo,

                    'descricao' => $descricao,

                    'ano' => $ano,

                    'ligacao' => null,

                    'tipo_incorporacao' => null,
                ],
            );
    }

    /**
     * Valida que um modelo relacionado já se encontra persistido.
     *
     * @param  Model  $modelo  Modelo a validar.
     * @param  string  $mensagem  Mensagem utilizada em caso de erro.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarModeloPersistido(
        Model $modelo,
        string $mensagem,
    ): void {
        if (
            ! $modelo->exists
            || $modelo->getKey() === null
        ) {
            throw new InvalidArgumentException(
                $mensagem,
            );
        }
    }
}
