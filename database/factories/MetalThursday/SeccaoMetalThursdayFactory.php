<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
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
 * Cria dados de teste para secções de uma MetalThursday.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<SeccaoMetalThursday>
 *
 * @since 2.0.0
 *
 * @version 2.0.0
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
    private const ORDEM_MINIMA = 1;

    /**
     * Ordem máxima permitida pela coluna unsigned small integer.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ORDEM_MAXIMA = 65535;

    /**
     * Primeiro ano aceite para os dados musicais da secção.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private const ANO_MINIMO = 1900;

    /**
     * Comprimento máximo do título.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_TITULO = 255;

    /**
     * Comprimento máximo da ligação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_LIGACAO = 2048;

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
     * Define os atributos predefinidos de uma secção.
     *
     * Por predefinição, é criado um tipo de secção que não exige detalhes
     * adicionais e não é associada qualquer banda ou incorporação.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos da secção.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function definition(): array
    {
        return [
            'metal_thursday_id' => MetalThursday::factory(),

            'tipo_seccao_id' => TipoSeccao::factory()
                ->semDetalhes(),

            'ordem' => self::ORDEM_MINIMA,

            'titulo' => null,

            'descricao' => $this
                ->faker
                ->paragraph(),

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
     * @throws InvalidArgumentException Quando a MetalThursday não está
     *                                  persistida.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function paraMetalThursday(
        MetalThursday $metalThursday,
    ): static {
        $this->validarModeloPersistido(
            $metalThursday,
            'A MetalThursday associada à secção deve estar persistida.',
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
     * @version 2.0.0
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
     * @version 2.0.0
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
            $banda ?? Banda::factory(),
            'banda',
        );
    }

    /**
     * Define a posição da secção dentro da MetalThursday.
     *
     * @param  int  $ordem  Posição positiva da secção.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a posição não cabe na coluna.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function naOrdem(
        int $ordem,
    ): static {
        if (
            $ordem < self::ORDEM_MINIMA
            || $ordem > self::ORDEM_MAXIMA
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'A ordem da secção deve estar entre %d e %d.',
                    self::ORDEM_MINIMA,
                    self::ORDEM_MAXIMA,
                ),
            );
        }

        return $this->state(
            static fn (): array => [
                'ordem' => $ordem,
            ],
        );
    }

    /**
     * Define o conteúdo textual da secção.
     *
     * @param  string  $descricao  Descrição obrigatória da secção.
     * @param  string|null  $titulo  Título opcional da secção.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a descrição está vazia ou o
     *                                  título ultrapassa o limite da coluna.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function comConteudo(
        string $descricao,
        ?string $titulo = null,
    ): static {
        $descricaoNormalizada = Str::squish(
            $descricao,
        );

        $tituloNormalizado = $titulo !== null
            ? Str::squish(
                $titulo,
            )
            : null;

        if ($descricaoNormalizada === '') {
            throw new InvalidArgumentException(
                'A descrição da secção não pode estar vazia.',
            );
        }

        if (
            $tituloNormalizado !== null
            && mb_strlen(
                $tituloNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_TITULO
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O título da secção não pode exceder %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_TITULO,
                ),
            );
        }

        return $this->state(
            static fn (): array => [
                'titulo' => $tituloNormalizado !== ''
                    ? $tituloNormalizado
                    : null,

                'descricao' => $descricaoNormalizada,
            ],
        );
    }

    /**
     * Define uma ligação e o respetivo tipo de incorporação.
     *
     * @param  string  $ligacao  Ligação absoluta a persistir.
     * @param  TipoIncorporacao  $tipoIncorporacao  Tipo da incorporação.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a ligação não é válida ou
     *                                  ultrapassa o limite da coluna.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function comIncorporacao(
        string $ligacao,
        TipoIncorporacao $tipoIncorporacao,
    ): static {
        $ligacaoNormalizada = trim(
            $ligacao,
        );

        if ($ligacaoNormalizada === '') {
            throw new InvalidArgumentException(
                'A ligação da secção não pode estar vazia.',
            );
        }

        if (
            mb_strlen(
                $ligacaoNormalizada,
            ) > self::COMPRIMENTO_MAXIMO_LIGACAO
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'A ligação da secção não pode exceder %d caracteres.',
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
                'A ligação da secção deve ser um URL absoluto válido.',
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
                'A ligação da secção deve utilizar HTTP ou HTTPS.',
            );
        }

        return $this->state(
            static fn (): array => [
                'ligacao' => $ligacaoNormalizada,

                'tipo_incorporacao' => $tipoIncorporacao->value,
            ],
        );
    }

    /**
     * Cria uma secção com informação musical detalhada.
     *
     * É criado um tipo que exige detalhes. Quando nenhuma banda é fornecida,
     * é criada uma através da respetiva factory.
     *
     * @param  Banda|null  $banda  Banda pretendida.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a banda indicada não está
     *                                  persistida.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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

        $titulo = Str::ucfirst(
            $this
                ->faker
                ->words(
                    3,
                    true,
                ),
        );

        $descricao = $this
            ->faker
            ->paragraph();

        $ano = $this
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
                $banda ?? Banda::factory(),
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
