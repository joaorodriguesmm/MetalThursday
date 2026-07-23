<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cria dados de teste para MetalThursdays.
 *
 * @extends Factory<MetalThursday>
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
class MetalThursdayFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<MetalThursday>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model = MetalThursday::class;

    /**
     * Define os atributos por omissão de uma MetalThursday.
     *
     * A data é calculada a partir de um número de dias único dentro da
     * execução da factory, reduzindo a possibilidade de colisões com a
     * restrição única existente na base de dados.
     *
     * @return array<string, mixed> Atributos da MetalThursday.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function definition(): array
    {
        $diasAnteriores = $this
            ->faker
            ->unique()
            ->numberBetween(
                0,
                10000,
            );

        return [
            'nome' => null,

            'data' => CarbonImmutable::today()
                ->subDays(
                    $diasAnteriores,
                ),

            'edicao_id' => Edicao::factory(),
            'autor_id' => null,
            'proximo_nomeado_id' => null,
        ];
    }

    /**
     * Define um nome para a MetalThursday.
     *
     * @param  string  $nome  Nome pretendido.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comNome(
        string $nome,
    ): static {
        return $this->state(
            fn (): array => [
                'nome' => $nome,
            ],
        );
    }

    /**
     * Associa um autor à MetalThursday.
     *
     * Quando nenhum utilizador é indicado, é criado um utilizador através da
     * factory respetiva.
     *
     * @param  Utilizador|null  $utilizador  Autor pretendido.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comAutor(
        ?Utilizador $utilizador = null,
    ): static {
        return $this->for(
            $utilizador
                ?? Utilizador::factory(),
            'autor',
        );
    }

    /**
     * Associa o próximo utilizador nomeado à MetalThursday.
     *
     * Quando nenhum utilizador é indicado, é criado um utilizador através da
     * factory respetiva.
     *
     * @param  Utilizador|null  $utilizador  Próximo nomeado.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comProximoNomeado(
        ?Utilizador $utilizador = null,
    ): static {
        return $this->for(
            $utilizador
                ?? Utilizador::factory(),
            'proximoNomeado',
        );
    }
}
