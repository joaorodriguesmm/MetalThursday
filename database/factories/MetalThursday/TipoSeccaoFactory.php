<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\MetalThursday\TipoSeccao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cria dados de teste para tipos de secção.
 *
 * @extends Factory<TipoSeccao>
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
class TipoSeccaoFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<TipoSeccao>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model = TipoSeccao::class;

    /**
     * Define os atributos por omissão de um tipo de secção.
     *
     * @return array<string, mixed> Atributos do tipo de secção.
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
            fn (): array => [
                'tem_detalhes' => true,
            ],
        );
    }

    /**
     * Cria um tipo de secção sem detalhes adicionais.
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
            fn (): array => [
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
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comDados(
        string $nome,
        string $descricao,
    ): static {
        return $this->state(
            fn (): array => [
                'nome' => $nome,
                'descricao' => $descricao,
            ],
        );
    }
}
