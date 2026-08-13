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
 */
final class TipoSeccaoFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<TipoSeccao>
     *
     * @since 2.0.0
     */
    protected $model = TipoSeccao::class;

    /**
     * Define os atributos predefinidos de um tipo de secção.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos do tipo de secção.
     *
     * @since 2.0.0
     */
    public function definition(): array
    {
        $nome = Str::ucfirst(
            $this
                ->faker
                ->unique()
                ->words(
                    2,
                    true,
                ),
        );

        $sufixo = Str::lower(
            Str::random(
                6,
            ),
        );

        $baseIdentificador = rtrim(
            Str::limit(
                Str::slug(
                    $nome,
                    '_',
                ),
                TipoSeccao::COMPRIMENTO_MAXIMO_IDENTIFICADOR
                    - strlen($sufixo)
                    - 1,
                '',
            ),
            '_',
        );

        return [
            'identificador' => sprintf(
                '%s_%s',
                $baseIdentificador,
                $sufixo,
            ),

            'nome' => Str::limit(
                $nome,
                TipoSeccao::COMPRIMENTO_MAXIMO_NOME,
                '',
            ),

            'descricao' => $this
                ->faker
                ->sentence(),

            'exige_detalhes' => $this
                ->faker
                ->boolean(),

            'ordem' => $this
                ->faker
                ->unique()
                ->numberBetween(
                    TipoSeccao::ORDEM_MINIMA,
                    TipoSeccao::ORDEM_MAXIMA,
                ),
        ];
    }

    /**
     * Cria um tipo que exige informação musical detalhada.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     */
    public function comDetalhes(): static
    {
        return $this->state([
            'exige_detalhes' => true,
        ]);
    }

    /**
     * Cria um tipo que não exige informação musical detalhada.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     */
    public function semDetalhes(): static
    {
        return $this->state([
            'exige_detalhes' => false,
        ]);
    }

    /**
     * Define os dados principais do tipo de secção.
     *
     * A normalização e a validação são delegadas ao contrato definitivo do
     * modelo.
     *
     * @param  string  $identificador  Identificador técnico estável.
     * @param  string  $nome  Nome apresentado ao utilizador.
     * @param  string  $descricao  Descrição do tipo.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando os dados não são válidos.
     *
     * @since 2.0.0
     */
    public function comDados(
        string $identificador,
        string $nome,
        string $descricao,
    ): static {
        $tipoSeccao = new TipoSeccao;

        $tipoSeccao->identificador =
            $identificador;

        $tipoSeccao->nome =
            $nome;

        $tipoSeccao->descricao =
            $descricao;

        return $this->state([
            'identificador' => $tipoSeccao->identificador,

            'nome' => $tipoSeccao->nome,

            'descricao' => $tipoSeccao->descricao,
        ]);
    }

    /**
     * Define a ordem de apresentação do tipo de secção.
     *
     * A validação é delegada ao contrato definitivo do modelo.
     *
     * @param  int  $ordem  Ordem pretendida.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a ordem não é válida.
     *
     * @since 2.0.0
     */
    public function naOrdem(
        int $ordem,
    ): static {
        $tipoSeccao = new TipoSeccao;

        $tipoSeccao->ordem =
            $ordem;

        return $this->state([
            'ordem' => $tipoSeccao->ordem,
        ]);
    }
}
