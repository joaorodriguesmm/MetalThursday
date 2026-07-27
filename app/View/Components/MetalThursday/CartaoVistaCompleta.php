<?php

declare(strict_types=1);

namespace App\View\Components\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara um cartão completo de uma MetalThursday.
 *
 * O componente exige que as relações necessárias tenham sido previamente
 * carregadas pelo controlador, impedindo consultas implícitas durante a
 * apresentação do cartão e das respetivas secções.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class CartaoVistaCompleta extends Component
{
    /**
     * MetalThursday apresentada.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly MetalThursday $registoMetalThursday;

    /**
     * Identificador da MetalThursday.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly int $identificadorMetalThursday;

    /**
     * Título completo da MetalThursday.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $tituloMetalThursday;

    /**
     * Nome do autor.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $nomeAutor;

    /**
     * Nome do próximo utilizador nomeado.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $nomeProximoNomeado;

    /**
     * Dados das interações da MetalThursday.
     *
     * @var array{
     *     pontuacaoUtilizador: float,
     *     textoAvaliacao: string,
     *     ouvido: bool,
     *     textoAudicao: string,
     *     quantidadeComentarios: int,
     *     quantidadeAudicoes: int,
     *     quantidadeAvaliacoes: int,
     *     mediaAvaliacoes: string,
     *     descricaoAudicoes: HtmlString,
     *     descricaoAvaliacoes: HtmlString
     * }
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly array $interacoesMetalThursday;

    /**
     * Secções preparadas para apresentação.
     *
     * @var array<int, array{
     *     modelo: SeccaoMetalThursday,
     *     identificador: int,
     *     temDetalhes: bool,
     *     titulo: string|null,
     *     descricao: string|null,
     *     tituloApresentacao: string,
     *     nomeAvaliavel: string,
     *     temLigacao: bool,
     *     identificadorComentarios: string,
     *     interacoes: array{
     *         pontuacaoUtilizador: float,
     *         textoAvaliacao: string,
     *         ouvido: bool,
     *         textoAudicao: string,
     *         quantidadeComentarios: int,
     *         quantidadeAudicoes: int,
     *         quantidadeAvaliacoes: int,
     *         mediaAvaliacoes: string,
     *         descricaoAudicoes: HtmlString,
     *         descricaoAvaliacoes: HtmlString
     *     }|null
     * }>
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly array $seccoesPreparadas;

    /**
     * Identificador do contentor dos comentários da MetalThursday.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $identificadorComentariosMetalThursday;

    /**
     * Nome utilizado no formulário de avaliação da MetalThursday.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly string $nomeAvaliavelMetalThursday;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  MetalThursday  $registoMetalThursday  MetalThursday apresentada.
     *
     * @throws LogicException Quando o modelo não está persistido ou uma
     *                        relação obrigatória não está carregada.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function __construct(
        MetalThursday $registoMetalThursday,
    ) {
        $this->registoMetalThursday =
            $registoMetalThursday;

        $this->identificadorMetalThursday =
            $this->obterIdentificador(
                $registoMetalThursday,
                'MetalThursday',
            );

        $edicao =
            $this->obterEdicao(
                $registoMetalThursday,
            );

        $autor =
            $this->obterUtilizadorRelacionado(
                $registoMetalThursday,
                'autor',
            );

        $proximoNomeado =
            $this->obterUtilizadorRelacionado(
                $registoMetalThursday,
                'proximoNomeado',
            );

        $this->nomeAutor =
            $this->normalizarTexto(
                $autor?->nome,
            )
            ?? 'Utilizador removido';

        $this->nomeProximoNomeado =
            $this->normalizarTexto(
                $proximoNomeado?->nome,
            )
            ?? 'Não definido';

        $this->tituloMetalThursday =
            $this->criarTituloMetalThursday(
                $registoMetalThursday,
                $edicao,
            );

        $this->interacoesMetalThursday =
            $this->prepararInteracoes(
                $registoMetalThursday,
                'Ninguém marcou esta MetalThursday como ouvida.',
                'Esta MetalThursday ainda não tem avaliações.',
                'Avaliar MetalThursday',
                'Ouvida',
                'Marcar como ouvida',
            );

        $this->identificadorComentariosMetalThursday =
            "comentarios-metal-thursday-{$this->identificadorMetalThursday}";

        $this->nomeAvaliavelMetalThursday =
            "MetalThursday de {$this->nomeAutor}";

        $seccoes =
            $this->obterColecaoCarregada(
                $registoMetalThursday,
                'seccoes',
            );

        $this->obterColecaoCarregada(
            $registoMetalThursday,
            'comentarios',
        );

        $this->seccoesPreparadas =
            $this->prepararSeccoes(
                $seccoes,
            );
    }

    /**
     * Obtém a view do componente.
     *
     * @return View View do cartão completo.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function render(): View
    {
        return view(
            'components.metal-thursday.cartao-vista-completa',
        );
    }

    /**
     * Prepara as secções da MetalThursday.
     *
     * @param  Collection<int, Model>  $seccoes  Secções carregadas.
     * @return array<int, array<string, mixed>> Secções preparadas.
     *
     * @throws LogicException Quando uma secção ou relação possui um tipo
     *                        inesperado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function prepararSeccoes(
        Collection $seccoes,
    ): array {
        $seccoesPreparadas = [];

        foreach ($seccoes as $seccao) {
            if (! $seccao instanceof SeccaoMetalThursday) {
                throw new LogicException(
                    'A relação "seccoes" contém um modelo inesperado.',
                );
            }

            $identificador =
                $this->obterIdentificador(
                    $seccao,
                    'secção',
                );

            $tipoSeccao =
                $this->obterTipoSeccao(
                    $seccao,
                );

            $banda =
                $this->obterBanda(
                    $seccao,
                );

            $titulo =
                $this->normalizarTexto(
                    $seccao->titulo,
                );

            $descricao =
                $this->normalizarTexto(
                    $seccao->descricao,
                );

            $nomeBanda =
                $this->normalizarTexto(
                    $banda?->nome,
                )
                ?? 'Banda indisponível';

            $temDetalhes =
                (bool) $tipoSeccao->tem_detalhes;

            $this->obterColecaoCarregada(
                $seccao,
                'comentarios',
            );

            $interacoes = $temDetalhes
                ? $this->prepararInteracoes(
                    $seccao,
                    'Ninguém marcou esta secção como ouvida.',
                    'Esta secção ainda não tem avaliações.',
                    'Avaliar',
                    'Ouvido',
                    'Marcar como ouvido',
                )
                : null;

            $seccoesPreparadas[] = [
                'modelo' => $seccao,

                'identificador' => $identificador,

                'temDetalhes' => $temDetalhes,

                'titulo' => $titulo,

                'descricao' => $descricao,

                'tituloApresentacao' => $this->criarTituloSeccao(
                    $seccao,
                    $nomeBanda,
                    $titulo,
                ),

                'nomeAvaliavel' => $titulo !== null
                    ? "{$nomeBanda} — {$titulo}"
                    : $nomeBanda,

                'temLigacao' => $this->normalizarTexto(
                    $seccao->ligacao,
                ) !== null,

                'identificadorComentarios' => "comentarios-seccao-{$identificador}",

                'interacoes' => $interacoes,
            ];
        }

        return $seccoesPreparadas;
    }

    /**
     * Prepara os dados das interações de uma entidade.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $modelo  Entidade preparada.
     * @param  string  $mensagemSemAudicoes  Mensagem sem audições.
     * @param  string  $mensagemSemAvaliacoes  Mensagem sem avaliações.
     * @param  string  $textoSemAvaliacao  Texto do botão sem avaliação.
     * @param  string  $textoOuvido  Texto quando está ouvido.
     * @param  string  $textoNaoOuvido  Texto quando não está ouvido.
     * @return array<string, mixed> Dados das interações.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function prepararInteracoes(
        MetalThursday|SeccaoMetalThursday $modelo,
        string $mensagemSemAudicoes,
        string $mensagemSemAvaliacoes,
        string $textoSemAvaliacao,
        string $textoOuvido,
        string $textoNaoOuvido,
    ): array {
        $audicoes =
            $this->obterColecaoCarregada(
                $modelo,
                'audicoes',
            );

        $avaliacoes =
            $this->obterColecaoCarregada(
                $modelo,
                'avaliacoes',
            );

        $pontuacaoUtilizador =
            max(
                0.0,
                $this->normalizarDecimal(
                    $modelo->getAttribute(
                        'pontuacao_utilizador_autenticado',
                    ),
                ),
            );

        $ouvido =
            (bool) $modelo->getAttribute(
                'ouvido_pelo_utilizador_autenticado',
            );

        $quantidadeComentarios =
            $this->obterContagem(
                $modelo,
                'comentarios_count',
                'comentarios',
            );

        $quantidadeAudicoes =
            $this->obterContagem(
                $modelo,
                'audicoes_count',
                'audicoes',
            );

        $quantidadeAvaliacoes =
            $this->obterContagem(
                $modelo,
                'avaliacoes_count',
                'avaliacoes',
            );

        $mediaAvaliacoes =
            max(
                0.0,
                $this->obterMediaAvaliacoes(
                    $modelo,
                    $avaliacoes,
                ),
            );

        return [
            'pontuacaoUtilizador' => $pontuacaoUtilizador,

            'textoAvaliacao' => $pontuacaoUtilizador > 0
                ? 'A tua avaliação: '
                .$this->formatarPontuacao(
                    $pontuacaoUtilizador,
                )
                : $textoSemAvaliacao,

            'ouvido' => $ouvido,

            'textoAudicao' => $ouvido
                ? $textoOuvido
                : $textoNaoOuvido,

            'quantidadeComentarios' => $quantidadeComentarios,

            'quantidadeAudicoes' => $quantidadeAudicoes,

            'quantidadeAvaliacoes' => $quantidadeAvaliacoes,

            'mediaAvaliacoes' => $this->formatarPontuacao(
                $mediaAvaliacoes,
            ),

            'descricaoAudicoes' => $this->criarDescricaoAudicoes(
                $audicoes,
                $mensagemSemAudicoes,
            ),

            'descricaoAvaliacoes' => $this->criarDescricaoAvaliacoes(
                $avaliacoes,
                $mensagemSemAvaliacoes,
            ),
        ];
    }

    /**
     * Cria o título completo da MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday apresentada.
     * @param  Edicao|null  $edicao  Edição relacionada.
     * @return string Título preparado.
     *
     * @throws LogicException Quando a data possui um tipo inesperado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function criarTituloMetalThursday(
        MetalThursday $metalThursday,
        ?Edicao $edicao,
    ): string {
        $partes = [
            $this->normalizarTexto(
                $edicao?->nome,
            )
                ?? 'Edição indisponível',
        ];

        $numeroSemana =
            $this->normalizarInteiroPositivo(
                $metalThursday->getAttribute(
                    'numero_semana_na_edicao',
                ),
            );

        if ($numeroSemana !== null) {
            $partes[] =
                "Semana {$numeroSemana}";
        }

        $nome =
            $this->normalizarTexto(
                $metalThursday->nome,
            );

        if ($nome !== null) {
            $partes[] =
                $nome;
        }

        $titulo =
            implode(
                ' — ',
                $partes,
            );

        $data =
            $metalThursday->data;

        if ($data === null) {
            return $titulo;
        }

        if (! $data instanceof CarbonInterface) {
            throw new LogicException(
                'A data da MetalThursday possui um tipo inesperado.',
            );
        }

        return sprintf(
            '%s (%s)',
            $titulo,
            $data->format(
                'd/m/Y',
            ),
        );
    }

    /**
     * Cria o título de apresentação de uma secção.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção apresentada.
     * @param  string  $nomeBanda  Nome da banda.
     * @param  string|null  $titulo  Título opcional.
     * @return string Título preparado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function criarTituloSeccao(
        SeccaoMetalThursday $seccao,
        string $nomeBanda,
        ?string $titulo,
    ): string {
        $tituloApresentacao =
            $titulo !== null
            ? "{$nomeBanda} — {$titulo}"
            : $nomeBanda;

        $ano =
            $this->normalizarInteiroPositivo(
                $seccao->ano,
            );

        return $ano !== null
            ? "{$tituloApresentacao} ({$ano})"
            : $tituloApresentacao;
    }

    /**
     * Cria a descrição das audições.
     *
     * @param  Collection<int, Model>  $audicoes  Audições carregadas.
     * @param  string  $mensagemVazia  Mensagem apresentada sem audições.
     * @return HtmlString Descrição segura.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function criarDescricaoAudicoes(
        Collection $audicoes,
        string $mensagemVazia,
    ): HtmlString {
        $linhas = [];

        foreach ($audicoes as $audicao) {
            $utilizador =
                $this->obterUtilizadorRelacionado(
                    $audicao,
                    'utilizador',
                );

            $linhas[] = e(
                $this->normalizarTexto(
                    $utilizador?->nome,
                )
                    ?? 'Utilizador removido',
            );
        }

        return new HtmlString(
            $linhas !== []
                ? implode(
                    '<br>',
                    $linhas,
                )
                : e(
                    $mensagemVazia,
                ),
        );
    }

    /**
     * Cria a descrição das avaliações.
     *
     * @param  Collection<int, Model>  $avaliacoes  Avaliações carregadas.
     * @param  string  $mensagemVazia  Mensagem apresentada sem avaliações.
     * @return HtmlString Descrição segura.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function criarDescricaoAvaliacoes(
        Collection $avaliacoes,
        string $mensagemVazia,
    ): HtmlString {
        $linhas = [];

        foreach ($avaliacoes as $avaliacao) {
            $utilizador =
                $this->obterUtilizadorRelacionado(
                    $avaliacao,
                    'utilizador',
                );

            $nome =
                $this->normalizarTexto(
                    $utilizador?->nome,
                )
                ?? 'Utilizador removido';

            $pontuacao =
                $this->normalizarDecimal(
                    $avaliacao->getAttribute(
                        'pontuacao',
                    ),
                );

            $linhas[] =
                e(
                    $nome,
                )
                .': '
                .e(
                    $this->formatarPontuacao(
                        $pontuacao,
                    ),
                );
        }

        return new HtmlString(
            $linhas !== []
                ? implode(
                    '<br>',
                    $linhas,
                )
                : e(
                    $mensagemVazia,
                ),
        );
    }

    /**
     * Obtém a edição previamente carregada.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday consultada.
     * @return Edicao|null Edição relacionada.
     *
     * @throws LogicException Quando a relação possui um tipo inesperado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterEdicao(
        MetalThursday $metalThursday,
    ): ?Edicao {
        if (! $metalThursday->relationLoaded('edicao')) {
            throw new LogicException(
                'A relação "edicao" deve estar carregada.',
            );
        }

        $edicao =
            $metalThursday->getRelation(
                'edicao',
            );

        if (
            $edicao !== null
            && ! $edicao instanceof Edicao
        ) {
            throw new LogicException(
                'A relação "edicao" possui um tipo inesperado.',
            );
        }

        return $edicao;
    }

    /**
     * Obtém o tipo de secção previamente carregado.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção consultada.
     * @return TipoSeccao Tipo de secção.
     *
     * @throws LogicException Quando a relação não está carregada ou é inválida.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterTipoSeccao(
        SeccaoMetalThursday $seccao,
    ): TipoSeccao {
        if (! $seccao->relationLoaded('tipoSeccao')) {
            throw new LogicException(
                'A relação "tipoSeccao" deve estar carregada.',
            );
        }

        $tipoSeccao =
            $seccao->getRelation(
                'tipoSeccao',
            );

        if (! $tipoSeccao instanceof TipoSeccao) {
            throw new LogicException(
                'A relação "tipoSeccao" possui um tipo inesperado.',
            );
        }

        return $tipoSeccao;
    }

    /**
     * Obtém a banda previamente carregada.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção consultada.
     * @return Banda|null Banda relacionada.
     *
     * @throws LogicException Quando a relação possui um tipo inesperado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterBanda(
        SeccaoMetalThursday $seccao,
    ): ?Banda {
        if (! $seccao->relationLoaded('banda')) {
            throw new LogicException(
                'A relação "banda" deve estar carregada.',
            );
        }

        $banda =
            $seccao->getRelation(
                'banda',
            );

        if (
            $banda !== null
            && ! $banda instanceof Banda
        ) {
            throw new LogicException(
                'A relação "banda" possui um tipo inesperado.',
            );
        }

        return $banda;
    }

    /**
     * Obtém um utilizador através de uma relação carregada.
     *
     * @param  Model  $modelo  Modelo consultado.
     * @param  string  $relacao  Nome da relação.
     * @return Utilizador|null Utilizador relacionado.
     *
     * @throws LogicException Quando a relação não está carregada ou possui
     *                        um tipo inesperado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterUtilizadorRelacionado(
        Model $modelo,
        string $relacao,
    ): ?Utilizador {
        if (! $modelo->relationLoaded($relacao)) {
            throw new LogicException(
                sprintf(
                    'A relação "%s" deve estar carregada.',
                    $relacao,
                ),
            );
        }

        $utilizador =
            $modelo->getRelation(
                $relacao,
            );

        if (
            $utilizador !== null
            && ! $utilizador instanceof Utilizador
        ) {
            throw new LogicException(
                sprintf(
                    'A relação "%s" possui um tipo inesperado.',
                    $relacao,
                ),
            );
        }

        return $utilizador;
    }

    /**
     * Obtém uma coleção relacionada previamente carregada.
     *
     * @param  Model  $modelo  Modelo consultado.
     * @param  string  $relacao  Nome da relação.
     * @return Collection<int, Model> Coleção relacionada.
     *
     * @throws LogicException Quando a relação não está carregada ou não
     *                        contém uma coleção Eloquent.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterColecaoCarregada(
        Model $modelo,
        string $relacao,
    ): Collection {
        if (! $modelo->relationLoaded($relacao)) {
            throw new LogicException(
                sprintf(
                    'A relação "%s" deve estar carregada.',
                    $relacao,
                ),
            );
        }

        $colecao =
            $modelo->getRelation(
                $relacao,
            );

        if (! $colecao instanceof Collection) {
            throw new LogicException(
                sprintf(
                    'A relação "%s" possui um tipo inesperado.',
                    $relacao,
                ),
            );
        }

        return $colecao;
    }

    /**
     * Obtém uma contagem carregada ou calcula-a em memória.
     *
     * @param  Model  $modelo  Modelo consultado.
     * @param  string  $atributoContagem  Atributo da contagem.
     * @param  string  $relacao  Relação correspondente.
     * @return int Contagem normalizada.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterContagem(
        Model $modelo,
        string $atributoContagem,
        string $relacao,
    ): int {
        $valor =
            $modelo->getAttribute(
                $atributoContagem,
            );

        if (is_numeric($valor)) {
            return max(
                0,
                (int) $valor,
            );
        }

        return $this
            ->obterColecaoCarregada(
                $modelo,
                $relacao,
            )
            ->count();
    }

    /**
     * Obtém a média das avaliações.
     *
     * @param  Model  $modelo  Modelo consultado.
     * @param  Collection<int, Model>  $avaliacoes  Avaliações carregadas.
     * @return float Média normalizada.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterMediaAvaliacoes(
        Model $modelo,
        Collection $avaliacoes,
    ): float {
        $mediaCarregada =
            $modelo->getAttribute(
                'avaliacoes_avg_pontuacao',
            );

        if (is_numeric($mediaCarregada)) {
            return (float) $mediaCarregada;
        }

        $media =
            $avaliacoes->avg(
                'pontuacao',
            );

        return is_numeric($media)
            ? (float) $media
            : 0.0;
    }

    /**
     * Obtém o identificador persistido de um modelo.
     *
     * @param  Model  $modelo  Modelo consultado.
     * @param  string  $descricao  Descrição usada no erro.
     * @return int Identificador.
     *
     * @throws LogicException Quando o modelo não está persistido.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificador(
        Model $modelo,
        string $descricao,
    ): int {
        $identificador =
            $modelo->getKey();

        if (
            ! $modelo->exists
            || ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new LogicException(
                sprintf(
                    'A %s deve estar persistida.',
                    $descricao,
                ),
            );
        }

        return (int) $identificador;
    }

    /**
     * Normaliza um texto opcional.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Texto normalizado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarTexto(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $texto =
            trim(
                $valor,
            );

        return $texto !== ''
            ? $texto
            : null;
    }

    /**
     * Normaliza um número inteiro positivo.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return int|null Número normalizado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarInteiroPositivo(
        mixed $valor,
    ): ?int {
        if (
            ! is_numeric($valor)
            || (int) $valor < 1
        ) {
            return null;
        }

        return (int) $valor;
    }

    /**
     * Normaliza um número decimal.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return float Número normalizado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarDecimal(
        mixed $valor,
    ): float {
        return is_numeric($valor)
            ? (float) $valor
            : 0.0;
    }

    /**
     * Formata uma pontuação.
     *
     * @param  float  $pontuacao  Pontuação recebida.
     * @return string Pontuação formatada.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function formatarPontuacao(
        float $pontuacao,
    ): string {
        return number_format(
            $pontuacao,
            1,
            ',',
            ' ',
        );
    }
}
