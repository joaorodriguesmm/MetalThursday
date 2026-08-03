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
 * @version 2.2.0
 */
final class SeccaoMetalThursdayFactory extends Factory
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

            'ordem' => SeccaoMetalThursday::ORDEM_MINIMA,

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
     * @version 2.1.0
     */
    public function naOrdem(
        int $ordem,
    ): static {
        if (
            $ordem < SeccaoMetalThursday::ORDEM_MINIMA
            || $ordem > SeccaoMetalThursday::ORDEM_MAXIMA
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'A ordem da secção deve estar entre %d e %d.',
                    SeccaoMetalThursday::ORDEM_MINIMA,
                    SeccaoMetalThursday::ORDEM_MAXIMA,
                ),
            );
        }

        return $this->state([
            'ordem' => $ordem,
        ]);
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
     * @version 2.1.0
     */
    public function comConteudo(
        string $descricao,
        ?string $titulo = null,
    ): static {
        $descricaoNormalizada = trim(
            str_replace(
                [
                    "\r\n",
                    "\r",
                ],
                "\n",
                $descricao,
            ),
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
            mb_strlen(
                $descricaoNormalizada,
            ) > SeccaoMetalThursday::COMPRIMENTO_MAXIMO_DESCRICAO
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'A descrição da secção não pode exceder %d caracteres.',
                    SeccaoMetalThursday::COMPRIMENTO_MAXIMO_DESCRICAO,
                ),
            );
        }

        if (
            $tituloNormalizado !== null
            && mb_strlen(
                $tituloNormalizado,
            ) > SeccaoMetalThursday::COMPRIMENTO_MAXIMO_TITULO
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O título da secção não pode exceder %d caracteres.',
                    SeccaoMetalThursday::COMPRIMENTO_MAXIMO_TITULO,
                ),
            );
        }

        return $this->state([
            'titulo' => $tituloNormalizado !== ''
                ? $tituloNormalizado
                : null,

            'descricao' => $descricaoNormalizada,
        ]);
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
     * @version 2.1.0
     */
    public function comIncorporacao(
        string $ligacao,
        TipoIncorporacao $tipoIncorporacao,
    ): static {
        return $this->state([
            'ligacao' => $this->normalizarLigacao(
                $ligacao,
            ),

            'tipo_incorporacao' => $tipoIncorporacao->value,
        ]);
    }

    /**
     * Cria uma secção com informação musical detalhada.
     *
     * É criado um tipo que exige detalhes. Quando nenhuma banda é fornecida,
     * é criada uma através da respetiva factory.
     *
     * O estado preenche todos os campos exigidos pelo contrato das secções
     * detalhadas: título, banda, ligação, tipo de incorporação e ano.
     *
     * @param  Banda|null  $banda  Banda pretendida.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a banda indicada não está
     *                                  persistida.
     *
     * @since 2.0.0
     *
     * @version 2.1.0
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

        $anoMaximo = min(
            CarbonImmutable::now()->year,
            SeccaoMetalThursday::ANO_MAXIMO,
        );

        $ano = $this
            ->faker
            ->numberBetween(
                SeccaoMetalThursday::ANO_MINIMO,
                $anoMaximo,
            );

        $ligacao = sprintf(
            'https://example.com/musica/%s',
            Str::lower(
                Str::random(
                    20,
                ),
            ),
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
            ->state([
                'titulo' => $titulo,

                'descricao' => $descricao,

                'ano' => $ano,

                'ligacao' => $ligacao,

                'tipo_incorporacao' => TipoIncorporacao::Ligacao->value,
            ]);
    }

    /**
     * Normaliza e valida uma ligação HTTP ou HTTPS.
     *
     * A ligação deve ser absoluta, possuir um anfitrião, não incluir
     * credenciais, espaços, caracteres de controlo ou barras invertidas.
     *
     * @param  string  $ligacao  Ligação recebida.
     * @return string Ligação normalizada.
     *
     * @throws InvalidArgumentException Quando a ligação não é válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarLigacao(
        string $ligacao,
    ): string {
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
            ) > SeccaoMetalThursday::COMPRIMENTO_MAXIMO_LIGACAO
            || str_contains(
                $ligacaoNormalizada,
                '\\',
            )
            || preg_match(
                '/[\x00-\x20\x7F]/',
                $ligacaoNormalizada,
            ) === 1
            || filter_var(
                $ligacaoNormalizada,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            throw new InvalidArgumentException(
                'A ligação da secção deve ser um URL absoluto válido.',
            );
        }

        $componentes = parse_url(
            $ligacaoNormalizada,
        );

        if (
            ! is_array($componentes)
            || ! isset(
                $componentes['scheme'],
                $componentes['host'],
            )
            || isset(
                $componentes['user'],
            )
            || isset(
                $componentes['pass'],
            )
            || trim(
                (string) $componentes['host'],
            ) === ''
        ) {
            throw new InvalidArgumentException(
                'A ligação da secção deve possuir um anfitrião válido e não pode incluir credenciais.',
            );
        }

        $esquema = mb_strtolower(
            (string) $componentes['scheme'],
        );

        if (
            ! in_array(
                $esquema,
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

        return $ligacaoNormalizada;
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
