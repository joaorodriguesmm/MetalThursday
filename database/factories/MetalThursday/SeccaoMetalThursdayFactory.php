<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
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
 */
final class SeccaoMetalThursdayFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<SeccaoMetalThursday>
     *
     * @since 2.0.0
     */
    protected $model = SeccaoMetalThursday::class;

    /**
     * Define os atributos predefinidos de uma secção.
     *
     * Por predefinição, é criado um tipo de secção que não exige detalhes
     * adicionais e não é associado qualquer artista ou incorporação.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos da secção.
     *
     * @since 2.0.0
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

            'artista_id' => null,

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
     * Associa um artista à secção.
     *
     * Quando nenhum artista é indicado, é criado um através da respetiva
     * factory.
     *
     * @param  Artista|null  $artista  Artista pretendido.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o artista indicado não está
     *                                  persistido.
     *
     * @since 2.0.0
     */
    public function comArtista(
        ?Artista $artista = null,
    ): static {
        if ($artista !== null) {
            $this->validarModeloPersistido(
                $artista,
                'O artista associado à secção deve estar persistido.',
            );
        }

        return $this->for(
            $artista ?? Artista::factory(),
            'artista',
        );
    }

    /**
     * Define a posição da secção dentro da MetalThursday.
     *
     * A validação é delegada ao contrato definitivo do modelo.
     *
     * @param  int  $ordem  Posição da secção.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a posição não é válida.
     *
     * @since 2.0.0
     */
    public function naOrdem(
        int $ordem,
    ): static {
        $seccao = new SeccaoMetalThursday;

        $seccao->ordem =
            $ordem;

        return $this->state([
            'ordem' => $seccao->ordem,
        ]);
    }

    /**
     * Define o conteúdo textual da secção.
     *
     * A normalização e a validação são delegadas ao contrato definitivo do
     * modelo.
     *
     * @param  string  $descricao  Descrição obrigatória da secção.
     * @param  string|null  $titulo  Título opcional da secção.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o conteúdo não é válido.
     *
     * @since 2.0.0
     */
    public function comConteudo(
        string $descricao,
        ?string $titulo = null,
    ): static {
        $seccao = new SeccaoMetalThursday;

        $seccao->descricao =
            $descricao;

        $seccao->titulo =
            $titulo;

        return $this->state([
            'titulo' => $seccao->titulo,

            'descricao' => $seccao->descricao,
        ]);
    }

    /**
     * Define uma ligação e o respetivo tipo de incorporação.
     *
     * A normalização e a validação da ligação são delegadas ao contrato
     * definitivo do modelo. Uma ligação vazia continua a ser rejeitada por este
     * estado.
     *
     * @param  string  $ligacao  Ligação absoluta a persistir.
     * @param  TipoIncorporacao  $tipoIncorporacao  Tipo da incorporação.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a ligação não é válida.
     *
     * @since 2.0.0
     */
    public function comIncorporacao(
        string $ligacao,
        TipoIncorporacao $tipoIncorporacao,
    ): static {
        $seccao = new SeccaoMetalThursday;

        $seccao->ligacao =
            $ligacao;

        $ligacaoNormalizada =
            $seccao->ligacao;

        if (
            ! is_string($ligacaoNormalizada)
            || $ligacaoNormalizada === ''
        ) {
            throw new InvalidArgumentException(
                'A ligação da secção não pode estar vazia.',
            );
        }

        return $this->state([
            'ligacao' => $ligacaoNormalizada,

            'tipo_incorporacao' => $tipoIncorporacao->value,
        ]);
    }

    /**
     * Cria uma secção com informação musical detalhada.
     *
     * É criado um tipo que exige detalhes. Quando nenhum artista é fornecido,
     * é criado um através da respetiva factory.
     *
     * O estado preenche todos os campos exigidos pelo contrato das secções
     * detalhadas: título, artista, ligação, tipo de incorporação e ano.
     *
     * @param  Artista|null  $artista  Artista pretendido.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o artista indicado não está
     *                                  persistido.
     *
     * @since 2.0.0
     */
    public function comDetalhes(
        ?Artista $artista = null,
    ): static {
        if ($artista !== null) {
            $this->validarModeloPersistido(
                $artista,
                'O artista associado à secção detalhada deve estar persistido.',
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
                $artista ?? Artista::factory(),
                'artista',
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
     * Valida que um modelo relacionado já se encontra persistido.
     *
     * @param  Model  $modelo  Modelo a validar.
     * @param  string  $mensagem  Mensagem utilizada em caso de erro.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido.
     *
     * @since 2.0.0
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
