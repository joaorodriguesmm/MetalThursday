<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * Cria dados de teste para secções de uma MetalThursday.
 *
 * @extends Factory<SeccaoMetalThursday>
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
class SeccaoMetalThursdayFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<SeccaoMetalThursday>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model = SeccaoMetalThursday::class;

    /**
     * Define os atributos por omissão de uma secção.
     *
     * Por omissão, é criado um tipo de secção sem detalhes adicionais e sem
     * banda associada.
     *
     * @return array<string, mixed> Atributos da secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function definition(): array
    {
        return [
            'metal_thursday_id' => MetalThursday::factory(),

            'tipo_seccao_id' => TipoSeccao::factory()
                ->semDetalhes(),

            'ordem' => 1,
            'titulo' => null,
            'descricao' => null,
            'banda_id' => null,
            'ligacao' => null,
            'tipo_incorporacao' => null,
            'ano' => null,
        ];
    }

    /**
     * Associa a secção a uma MetalThursday existente.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday pretendida.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function paraMetalThursday(
        MetalThursday $metalThursday,
    ): static {
        return $this->for(
            $metalThursday,
            'metalThursday',
        );
    }

    /**
     * Associa um tipo existente à secção.
     *
     * @param  TipoSeccao  $tipoSeccao  Tipo pretendido.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function doTipo(
        TipoSeccao $tipoSeccao,
    ): static {
        return $this->for(
            $tipoSeccao,
            'tipoSeccao',
        );
    }

    /**
     * Associa uma banda à secção.
     *
     * Quando nenhuma banda é indicada, é criada uma através da factory
     * respetiva.
     *
     * @param  Banda|null  $banda  Banda pretendida.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comBanda(
        ?Banda $banda = null,
    ): static {
        return $this->for(
            $banda
                ?? Banda::factory(),
            'banda',
        );
    }

    /**
     * Define a posição da secção dentro da MetalThursday.
     *
     * @param  int  $ordem  Posição positiva da secção.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a posição não é positiva.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function naOrdem(
        int $ordem,
    ): static {
        if ($ordem < 1) {
            throw new InvalidArgumentException(
                'A ordem da secção deve ser um número inteiro positivo.',
            );
        }

        return $this->state(
            fn (): array => [
                'ordem' => $ordem,
            ],
        );
    }

    /**
     * Cria uma secção com informação detalhada.
     *
     * É criado um tipo que necessita de detalhes e, quando nenhuma banda for
     * fornecida, é criada uma através da respetiva factory.
     *
     * @param  Banda|null  $banda  Banda pretendida.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comDetalhes(
        ?Banda $banda = null,
    ): static {
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
                fn (): array => [
                    'titulo' => ucfirst(
                        $this
                            ->faker
                            ->words(
                                3,
                                true,
                            ),
                    ),

                    'descricao' => $this
                        ->faker
                        ->paragraph(),

                    'ano' => $this
                        ->faker
                        ->numberBetween(
                            1950,
                            (int) date('Y'),
                        ),
                ],
            );
    }
}
