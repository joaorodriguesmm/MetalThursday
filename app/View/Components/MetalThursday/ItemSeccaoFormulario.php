<?php

declare(strict_types=1);

namespace App\View\Components\MetalThursday;

use App\Enumeracoes\TipoLancamento;
use App\Models\MetalThursday\LigacaoSeccaoMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Models\Musica\FaixaLancamento;
use App\Models\Musica\Lancamento;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara um item repetível do formulário de secções.
 *
 * O componente constrói os nomes e identificadores dos campos, recupera os
 * valores antigos do pedido e determina se o tipo selecionado exige detalhes.
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
     * Artistas disponíveis.
     *
     * @var Collection<int, Artista>
     *
     * @since 2.0.0
     */
    public readonly Collection $artistas;

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
     *     artista: string,
     *     lancamento: string,
     *     titulo: string,
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
     *     artista: string,
     *     titulo: string,
     *     ano: string,
     *     descricao: string
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
     *     artista: string,
     *     titulo: string,
     *     ano: string,
     *     descricao: string
     * }
     *
     * @since 2.0.0
     */
    public readonly array $chavesErro;

    /**
     * Tipos de lançamento disponíveis no editor estruturado.
     *
     * @var list<array{valor: string, etiqueta: string}>
     *
     * @since 2.0.0
     */
    public readonly array $tiposLancamento;

    /**
     * Dados editáveis do lançamento associado à secção.
     *
     * @var array{
     *     titulo: string,
     *     tipo: string,
     *     ano_original: string,
     *     faixas: list<array{
     *         id: string,
     *         musica_id: string,
     *         titulo: string,
     *         posicao: string,
     *         ordem: string
     *     }>
     * }
     *
     * @since 2.0.0
     */
    public readonly array $dadosLancamento;

    /**
     * Ligações públicas associadas à secção.
     *
     * @var list<array{url: string, etiqueta: string, incorporar: bool}>
     *
     * @since 2.0.0
     */
    public readonly array $ligacoes;

    /**
     * Número máximo de ligações permitido numa secção.
     *
     * @since 2.0.0
     */
    public readonly int $numeroMaximoLigacoes;

    /**
     * Comprimento máximo permitido para a etiqueta de uma ligação.
     *
     * @since 2.0.0
     */
    public readonly int $comprimentoMaximoEtiquetaLigacao;

    /**
     * Comprimento máximo do título do lançamento.
     *
     * @since 2.0.0
     */
    public readonly int $comprimentoMaximoTituloLancamento;

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
     * @param  Collection<int, Artista>  $artistas  Artistas disponíveis.
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
        Collection $artistas,
        SeccaoMetalThursday|array|null $seccao = null,
    ) {
        $this->indice = $this->normalizarIndice(
            $indice,
        );

        $this->validarTiposSeccao(
            $tiposSeccao,
        );

        $this->validarArtistas(
            $artistas,
        );

        $this->tiposSeccao = $tiposSeccao;
        $this->artistas = $artistas;

        $this->prefixoCampo =
            "seccoes.{$this->indice}";

        $this->nomeBaseCampo =
            "seccoes[{$this->indice}]";

        $this->identificadores = [
            'tipoSeccao' => "seccoes-{$this->indice}-tipo-seccao",

            'artista' => "seccoes-{$this->indice}-artista",

            'titulo' => "seccoes-{$this->indice}-titulo",

            'ano' => "seccoes-{$this->indice}-ano",

            'descricao' => "seccoes-{$this->indice}-descricao",
        ];

        $this->chavesErro = [
            'tipoSeccao' => "{$this->prefixoCampo}.tipo_seccao_id",

            'artista' => "{$this->prefixoCampo}.artista_id",

            'titulo' => "{$this->prefixoCampo}.titulo",

            'ano' => "{$this->prefixoCampo}.ano",

            'descricao' => "{$this->prefixoCampo}.descricao",
        ];

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

            'artista' => $this->normalizarTexto(
                $this->obterValorCampo(
                    $pedido,
                    $seccao,
                    'artista_id',
                ),
            ),

            'lancamento' => $this->normalizarTexto(
                $this->obterValorCampo(
                    $pedido,
                    $seccao,
                    'lancamento_id',
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

        $this->tiposLancamento = array_map(
            static fn (
                TipoLancamento $tipo,
            ): array => [
                'valor' => $tipo->value,
                'etiqueta' => $tipo->etiqueta(),
            ],
            TipoLancamento::cases(),
        );

        $this->dadosLancamento = $this->obterDadosLancamento(
            $pedido,
            $seccao,
        );

        $this->ligacoes = $this->obterLigacoesFormulario(
            $pedido,
            $seccao,
        );

        $this->numeroMaximoLigacoes =
            LigacaoSeccaoMetalThursday::NUMERO_MAXIMO_POR_SECCAO;

        $this->comprimentoMaximoEtiquetaLigacao =
            LigacaoSeccaoMetalThursday::COMPRIMENTO_MAXIMO_ETIQUETA;

        $this->comprimentoMaximoTituloLancamento =
            Lancamento::COMPRIMENTO_MAXIMO_TITULO;

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
            LigacaoSeccaoMetalThursday::COMPRIMENTO_MAXIMO_URL;

        $this->comprimentoMaximoDescricao =
            SeccaoMetalThursday::COMPRIMENTO_MAXIMO_DESCRICAO;
    }

    /**
     * Obtém os dados editáveis do lançamento associado à secção.
     *
     * Dados antigos de uma submissão inválida têm precedência. Num formulário
     * de edição, os dados persistidos do catálogo são carregados com a
     * tracklist atual.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  SeccaoMetalThursday|array<string, mixed>|null  $seccao  Secção atual.
     * @return array<string, mixed> Dados normalizados para o formulário.
     *
     * @since 2.0.0
     */
    private function obterDadosLancamento(
        Request $pedido,
        SeccaoMetalThursday|array|null $seccao,
    ): array {
        $dadosAntigos = $pedido->old(
            "{$this->prefixoCampo}.lancamento",
        );

        if (is_array($dadosAntigos)) {
            return $this->normalizarDadosLancamentoFormulario(
                $dadosAntigos,
            );
        }

        if (
            is_array($seccao)
            && is_array(
                $seccao['lancamento']
                    ?? null,
            )
        ) {
            return $this->normalizarDadosLancamentoFormulario(
                $seccao['lancamento'],
            );
        }

        if ($seccao instanceof SeccaoMetalThursday) {
            $lancamento = $seccao->lancamento;

            if ($lancamento instanceof Lancamento) {
                $lancamento->loadMissing(
                    'faixas.musica',
                );

                $faixas = [];

                foreach ($lancamento->faixas as $faixa) {
                    if (! $faixa instanceof FaixaLancamento) {
                        continue;
                    }

                    $tituloMusica = $faixa->musica?->titulo;

                    if (! is_string($tituloMusica)) {
                        continue;
                    }

                    $faixas[] = [
                        'id' => (string) $faixa->getKey(),
                        'musica_id' => (string) $faixa->musica_id,
                        'titulo' => $tituloMusica,
                        'posicao' => $this->normalizarTexto(
                            $faixa->posicao,
                        ),
                        'ordem' => $this->normalizarTexto(
                            $faixa->ordem,
                        ),
                    ];
                }

                return [
                    'titulo' => $lancamento->titulo,
                    'tipo' => $lancamento->tipo->value
                        ?? '',
                    'ano_original' => $this->normalizarTexto(
                        $lancamento->ano_original,
                    ),
                    'faixas' => $faixas,
                ];
            }
        }

        return [
            'titulo' => '',
            'tipo' => '',
            'ano_original' => '',
            'faixas' => [],
        ];
    }

    /**
     * Normaliza dados de lançamento provenientes de sessão ou rascunho.
     *
     * @param  array<string, mixed>  $dados  Dados recebidos.
     * @return array<string, mixed> Dados seguros para renderização.
     *
     * @since 2.0.0
     */
    private function normalizarDadosLancamentoFormulario(
        array $dados,
    ): array {
        $tipo = $dados['tipo']
            ?? null;

        if ($tipo instanceof TipoLancamento) {
            $tipo = $tipo->value;
        }

        $faixasRecebidas = $dados['faixas']
            ?? [];

        $faixas = [];

        if (is_array($faixasRecebidas)) {
            foreach (array_values($faixasRecebidas) as $indice => $faixa) {
                if (! is_array($faixa)) {
                    continue;
                }

                $faixas[] = [
                    'id' => $this->normalizarTexto(
                        $faixa['id']
                            ?? '',
                    ),
                    'musica_id' => $this->normalizarTexto(
                        $faixa['musica_id']
                            ?? '',
                    ),
                    'titulo' => $this->normalizarTexto(
                        $faixa['titulo']
                            ?? '',
                    ),
                    'posicao' => $this->normalizarTexto(
                        $faixa['posicao']
                            ?? '',
                    ),
                    'ordem' => $this->normalizarTexto(
                        $faixa['ordem']
                            ?? $indice + 1,
                    ),
                ];
            }
        }

        return [
            'titulo' => $this->normalizarTexto(
                $dados['titulo']
                    ?? '',
            ),
            'tipo' => $this->normalizarTexto(
                $tipo,
            ),
            'ano_original' => $this->normalizarTexto(
                $dados['ano_original']
                    ?? '',
            ),
            'faixas' => $faixas,
        ];
    }

    /**
     * Obtém as ligações apresentadas pelo formulário.
     *
     * Dados antigos de uma submissão inválida têm precedência. Um rascunho
     * pode fornecer diretamente a lista em formato de array e uma secção
     * persistida utiliza a relação ordenada. Na ausência da estrutura nova,
     * mantém-se uma ponte temporária para os campos legados.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  SeccaoMetalThursday|array<string, mixed>|null  $seccao  Secção atual.
     * @return list<array{url: string, etiqueta: string, incorporar: bool}> Ligações.
     *
     * @since 2.0.0
     */
    private function obterLigacoesFormulario(
        Request $pedido,
        SeccaoMetalThursday|array|null $seccao,
    ): array {
        $chaveLigacoes =
            "{$this->prefixoCampo}.ligacoes";

        if (
            $pedido->hasSession()
            && $pedido->session()->hasOldInput(
                $chaveLigacoes,
            )
        ) {
            return $this->normalizarLigacoesFormulario(
                $pedido->old(
                    $chaveLigacoes,
                ),
            );
        }

        if (
            is_array($seccao)
            && array_key_exists(
                'ligacoes',
                $seccao,
            )
        ) {
            return $this->normalizarLigacoesFormulario(
                $seccao['ligacoes'],
            );
        }

        if ($seccao instanceof SeccaoMetalThursday) {
            $seccao->loadMissing(
                'ligacoes',
            );

            $ligacoes = [];

            foreach ($seccao->ligacoes as $ligacao) {
                if (! $ligacao instanceof LigacaoSeccaoMetalThursday) {
                    continue;
                }

                $ligacoes[] = [
                    'url' => $ligacao->url,
                    'etiqueta' => $ligacao->etiqueta
                        ?? '',
                    'incorporar' => $ligacao->incorporar,
                ];
            }

            return $ligacoes;
        }

        return [];
    }

    /**
     * Normaliza ligações provenientes de dados submetidos ou de um rascunho.
     *
     * Linhas incompletas são preservadas para não destruir trabalho guardado
     * num rascunho antes da publicação final.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return list<array{url: string, etiqueta: string, incorporar: bool}> Ligações.
     *
     * @since 2.0.0
     */
    private function normalizarLigacoesFormulario(
        mixed $valor,
    ): array {
        if (! is_array($valor)) {
            return [];
        }

        $ligacoes = [];

        foreach (array_values($valor) as $ligacao) {
            if (! is_array($ligacao)) {
                continue;
            }

            $ligacoes[] = [
                'url' => $this->normalizarTexto(
                    $ligacao['url']
                        ?? '',
                ),
                'etiqueta' => $this->normalizarTexto(
                    $ligacao['etiqueta']
                        ?? '',
                ),
                'incorporar' => $this->normalizarBooleanoFormulario(
                    $ligacao['incorporar']
                        ?? false,
                ),
            ];
        }

        return $ligacoes;
    }

    /**
     * Normaliza um booleano recebido por um campo HTML.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return bool Valor normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarBooleanoFormulario(
        mixed $valor,
    ): bool {
        return $valor === true
            || $valor === 1
            || $valor === '1';
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
     * Valida a coleção de artistas.
     *
     * @param  Collection<int, Artista>  $artistas  Artistas recebidos.
     *
     * @throws LogicException Quando existe um modelo inesperado.
     *
     * @since 2.0.0
     */
    private function validarArtistas(
        Collection $artistas,
    ): void {
        foreach ($artistas as $artista) {
            if (! $artista instanceof Artista) {
                throw new LogicException(
                    'A coleção de artistas contém um modelo inesperado.',
                );
            }
        }
    }
}
