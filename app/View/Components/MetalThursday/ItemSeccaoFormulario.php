<?php

declare(strict_types=1);

namespace App\View\Components\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara um item repetível do formulário de secções.
 *
 * O componente constrói os nomes e identificadores dos campos, recupera os
 * valores antigos do pedido, determina se o tipo selecionado exige detalhes
 * e disponibiliza os valores canónicos dos tipos de incorporação.
 *
 * @since 1.0.0
 */
final class ItemSeccaoFormulario extends Component
{
    /**
     * Índice utilizado nos nomes e identificadores dos campos.
     *
     * Pode ser um número ou um marcador temporário utilizado pelo modelo
     * HTML que o JavaScript substitui ao adicionar uma nova secção.
     *
     * @since 2.0.0
     */
    public readonly string $indice;

    /**
     * Tipos de secção disponíveis.
     *
     * @var Collection<int, TipoSeccao>
     *
     * @since 2.0.0
     */
    public readonly Collection $tiposSeccao;

    /**
     * Bandas disponíveis.
     *
     * @var Collection<int, Banda>
     *
     * @since 2.0.0
     */
    public readonly Collection $bandas;

    /**
     * Prefixo utilizado pelas chaves de validação.
     *
     * @since 2.0.0
     */
    private readonly string $prefixoCampo;

    /**
     * Nome base utilizado pelos campos HTML.
     *
     * @since 2.0.0
     */
    public readonly string $nomeBaseCampo;

    /**
     * Valores selecionados para os campos.
     *
     * @var array{
     *     identificador: string,
     *     tipoSeccao: string,
     *     banda: string,
     *     titulo: string,
     *     ligacao: string,
     *     tipoIncorporacao: string,
     *     ano: string,
     *     descricao: string
     * }
     *
     * @since 2.0.0
     */
    public readonly array $valores;

    /**
     * Identificadores HTML utilizados pelo item.
     *
     * @var array{
     *     tipoSeccao: string,
     *     banda: string,
     *     titulo: string,
     *     ligacao: string,
     *     tipoIncorporacao: string,
     *     ano: string,
     *     descricao: string,
     *     resultadosIncorporacao: string,
     *     estadoTesteIncorporacao: string,
     *     escolhaVideo: string,
     *     escolhaListaReproducao: string,
     *     escolhaLigacao: string
     * }
     *
     * @since 2.0.0
     */
    public readonly array $identificadores;

    /**
     * Chaves utilizadas para consultar os erros de validação.
     *
     * @var array{
     *     tipoSeccao: string,
     *     banda: string,
     *     titulo: string,
     *     ligacao: string,
     *     tipoIncorporacao: string,
     *     ano: string,
     *     descricao: string
     * }
     *
     * @since 2.0.0
     */
    public readonly array $chavesErro;

    /**
     * Valores canónicos dos tipos de incorporação.
     *
     * @var array{
     *     videoYouTube: string,
     *     listaReproducaoYouTube: string,
     *     ligacao: string
     * }
     *
     * @since 2.0.0
     */
    public readonly array $tiposIncorporacao;

    /**
     * Indica se o tipo de secção selecionado exige detalhes musicais.
     *
     * @since 2.0.0
     */
    public readonly bool $exigeDetalhes;

    /**
     * Ano mínimo permitido.
     *
     * @since 2.0.0
     */
    public readonly int $anoMinimo;

    /**
     * Ano máximo permitido.
     *
     * @since 2.0.0
     */
    public readonly int $anoMaximo;

    /**
     * Comprimento máximo permitido para o título.
     *
     * @since 2.0.0
     */
    public readonly int $comprimentoMaximoTitulo;

    /**
     * Comprimento máximo permitido para a ligação.
     *
     * @since 2.0.0
     */
    public readonly int $comprimentoMaximoLigacao;

    /**
     * Comprimento máximo permitido para a descrição.
     *
     * @since 2.0.0
     */
    public readonly int $comprimentoMaximoDescricao;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  int|string  $indice  Índice do item.
     * @param  Collection<int, TipoSeccao>  $tiposSeccao  Tipos disponíveis.
     * @param  Collection<int, Banda>  $bandas  Bandas disponíveis.
     * @param  SeccaoMetalThursday|array<string, mixed>|null  $seccao  Secção
     *                                                                 existente
     *                                                                 ou dados
     *                                                                 iniciais.
     *
     * @throws LogicException Quando o índice ou as coleções são inválidos.
     *
     * @since 1.0.0
     */
    public function __construct(
        Request $pedido,
        int|string $indice,
        Collection $tiposSeccao,
        Collection $bandas,
        SeccaoMetalThursday|array|null $seccao = null,
    ) {
        $this->indice = $this->normalizarIndice(
            $indice,
        );

        $this->validarTiposSeccao(
            $tiposSeccao,
        );

        $this->validarBandas(
            $bandas,
        );

        $this->tiposSeccao = $tiposSeccao;
        $this->bandas = $bandas;

        $this->prefixoCampo =
            "seccoes.{$this->indice}";

        $this->nomeBaseCampo =
            "seccoes[{$this->indice}]";

        $this->identificadores = [
            'tipoSeccao' => "seccoes-{$this->indice}-tipo-seccao",

            'banda' => "seccoes-{$this->indice}-banda",

            'titulo' => "seccoes-{$this->indice}-titulo",

            'ligacao' => "seccoes-{$this->indice}-ligacao",

            'tipoIncorporacao' => "seccoes-{$this->indice}-tipo-incorporacao",

            'ano' => "seccoes-{$this->indice}-ano",

            'descricao' => "seccoes-{$this->indice}-descricao",

            'resultadosIncorporacao' => "resultados-incorporacao-{$this->indice}",

            'estadoTesteIncorporacao' => "estado-teste-incorporacao-{$this->indice}",

            'escolhaVideo' => "escolha-video-{$this->indice}",

            'escolhaListaReproducao' => "escolha-lista-reproducao-{$this->indice}",

            'escolhaLigacao' => "escolha-ligacao-{$this->indice}",
        ];

        $this->chavesErro = [
            'tipoSeccao' => "{$this->prefixoCampo}.tipo_seccao_id",

            'banda' => "{$this->prefixoCampo}.banda_id",

            'titulo' => "{$this->prefixoCampo}.titulo",

            'ligacao' => "{$this->prefixoCampo}.ligacao",

            'tipoIncorporacao' => "{$this->prefixoCampo}.tipo_incorporacao",

            'ano' => "{$this->prefixoCampo}.ano",

            'descricao' => "{$this->prefixoCampo}.descricao",
        ];

        $tipoIncorporacao =
            $this->normalizarTipoIncorporacao(
                $this->obterValorCampo(
                    $pedido,
                    $seccao,
                    'tipo_incorporacao',
                    TipoIncorporacao::Ligacao->value,
                ),
            );

        $this->valores = [
            'identificador' => $this->normalizarTexto(
                $this->obterValorCampo(
                    $pedido,
                    $seccao,
                    'id',
                ),
            ),

            'tipoSeccao' => $this->normalizarTexto(
                $this->obterValorCampo(
                    $pedido,
                    $seccao,
                    'tipo_seccao_id',
                ),
            ),

            'banda' => $this->normalizarTexto(
                $this->obterValorCampo(
                    $pedido,
                    $seccao,
                    'banda_id',
                ),
            ),

            'titulo' => $this->normalizarTexto(
                $this->obterValorCampo(
                    $pedido,
                    $seccao,
                    'titulo',
                    '',
                ),
            ),

            'ligacao' => $this->normalizarTexto(
                $this->obterValorCampo(
                    $pedido,
                    $seccao,
                    'ligacao',
                    '',
                ),
            ),

            'tipoIncorporacao' => $tipoIncorporacao,

            'ano' => $this->normalizarTexto(
                $this->obterValorCampo(
                    $pedido,
                    $seccao,
                    'ano',
                    '',
                ),
            ),

            'descricao' => $this->normalizarTexto(
                $this->obterValorCampo(
                    $pedido,
                    $seccao,
                    'descricao',
                    '',
                ),
            ),
        ];

        $this->tiposIncorporacao = [
            'videoYouTube' => TipoIncorporacao::VideoYouTube->value,

            'listaReproducaoYouTube' => TipoIncorporacao::ListaReproducaoYouTube->value,

            'ligacao' => TipoIncorporacao::Ligacao->value,
        ];

        $this->exigeDetalhes =
            $this->tipoExigeDetalhes(
                $this->valores['tipoSeccao'],
                $tiposSeccao,
            );

        $this->anoMinimo =
            SeccaoMetalThursday::ANO_MINIMO;

        $this->anoMaximo =
            SeccaoMetalThursday::ANO_MAXIMO;

        $this->comprimentoMaximoTitulo =
            SeccaoMetalThursday::COMPRIMENTO_MAXIMO_TITULO;

        $this->comprimentoMaximoLigacao =
            SeccaoMetalThursday::COMPRIMENTO_MAXIMO_LIGACAO;

        $this->comprimentoMaximoDescricao =
            SeccaoMetalThursday::COMPRIMENTO_MAXIMO_DESCRICAO;
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista do item de secção.
     *
     * @since 1.0.0
     */
    public function render(): View
    {
        return view(
            'components.metal-thursday.item-seccao-formulario',
        );
    }

    /**
     * Obtém um valor antigo ou o valor existente na secção.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  SeccaoMetalThursday|array<string, mixed>|null  $seccao  Secção
     *                                                                 ou dados
     *                                                                 iniciais.
     * @param  string  $campo  Campo consultado.
     * @param  mixed  $valorPredefinido  Valor utilizado por omissão.
     * @return mixed Valor encontrado.
     *
     * @since 2.0.0
     */
    private function obterValorCampo(
        Request $pedido,
        SeccaoMetalThursday|array|null $seccao,
        string $campo,
        mixed $valorPredefinido = null,
    ): mixed {
        $valorModelo =
            data_get(
                $seccao,
                $campo,
                $valorPredefinido,
            );

        return $pedido->old(
            "{$this->prefixoCampo}.{$campo}",
            $valorModelo,
        );
    }

    /**
     * Normaliza o índice do item.
     *
     * São permitidos números e marcadores compostos por letras, números,
     * hífenes e sublinhados.
     *
     * @param  int|string  $indice  Índice recebido.
     * @return string Índice normalizado.
     *
     * @throws LogicException Quando o índice não é válido.
     *
     * @since 2.0.0
     */
    private function normalizarIndice(
        int|string $indice,
    ): string {
        $indiceNormalizado =
            trim(
                (string) $indice,
            );

        if (
            $indiceNormalizado === ''
            || preg_match(
                '/^[A-Za-z0-9_-]+$/',
                $indiceNormalizado,
            ) !== 1
        ) {
            throw new LogicException(
                'O índice da secção possui um formato inválido.',
            );
        }

        return $indiceNormalizado;
    }

    /**
     * Normaliza um valor para utilização num campo HTML.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string Texto normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarTexto(
        mixed $valor,
    ): string {
        if (
            ! is_string($valor)
            && ! is_int($valor)
            && ! is_float($valor)
        ) {
            return '';
        }

        if (
            is_float($valor)
            && ! is_finite($valor)
        ) {
            return '';
        }

        return trim(
            (string) $valor,
        );
    }

    /**
     * Normaliza o tipo de incorporação.
     *
     * Aceita diretamente a enumeração devolvida pelo cast do modelo ou um
     * valor textual recebido através dos dados antigos do pedido.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string Valor canónico.
     *
     * @since 2.0.0
     */
    private function normalizarTipoIncorporacao(
        mixed $valor,
    ): string {
        if ($valor instanceof TipoIncorporacao) {
            return $valor->value;
        }

        return (
            TipoIncorporacao::tentarCriar(
                $valor,
            )
            ?? TipoIncorporacao::Ligacao
        )->value;
    }

    /**
     * Determina se o tipo selecionado exige detalhes musicais.
     *
     * @param  string  $identificadorTipo  Identificador selecionado.
     * @param  Collection<int, TipoSeccao>  $tiposSeccao  Tipos disponíveis.
     * @return bool Verdadeiro quando o tipo exige detalhes musicais.
     *
     * @since 2.0.0
     */
    private function tipoExigeDetalhes(
        string $identificadorTipo,
        Collection $tiposSeccao,
    ): bool {
        if ($identificadorTipo === '') {
            return false;
        }

        foreach ($tiposSeccao as $tipoSeccao) {
            if (
                (string) $tipoSeccao->getKey()
                !== $identificadorTipo
            ) {
                continue;
            }

            return (bool) $tipoSeccao->exige_detalhes;
        }

        return false;
    }

    /**
     * Valida a coleção de tipos de secção.
     *
     * @param  Collection<int, TipoSeccao>  $tiposSeccao  Tipos recebidos.
     *
     * @throws LogicException Quando existe um modelo inesperado.
     *
     * @since 2.0.0
     */
    private function validarTiposSeccao(
        Collection $tiposSeccao,
    ): void {
        foreach ($tiposSeccao as $tipoSeccao) {
            if (! $tipoSeccao instanceof TipoSeccao) {
                throw new LogicException(
                    'A coleção de tipos de secção contém um modelo inesperado.',
                );
            }
        }
    }

    /**
     * Valida a coleção de bandas.
     *
     * @param  Collection<int, Banda>  $bandas  Bandas recebidas.
     *
     * @throws LogicException Quando existe um modelo inesperado.
     *
     * @since 2.0.0
     */
    private function validarBandas(
        Collection $bandas,
    ): void {
        foreach ($bandas as $banda) {
            if (! $banda instanceof Banda) {
                throw new LogicException(
                    'A coleção de bandas contém um modelo inesperado.',
                );
            }
        }
    }
}
