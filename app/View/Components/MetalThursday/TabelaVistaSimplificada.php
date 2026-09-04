<?php

declare(strict_types=1);

namespace App\View\Components\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\Geografia\OrigemGeografica;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use DateTimeInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara a tabela simplificada das secções de MetalThursday.
 *
 * O componente transforma os modelos paginados em linhas prontas para
 * apresentação, sem executar consultas adicionais durante a renderização.
 *
 * @since 1.0.0
 */
final class TabelaVistaSimplificada extends Component
{
    /**
     * Secções paginadas apresentadas na tabela.
     *
     * @var LengthAwarePaginator<int, SeccaoMetalThursday>
     *
     * @since 2.0.0
     */
    public readonly LengthAwarePaginator $seccoesSimplificadas;

    /**
     * Linhas preparadas para apresentação.
     *
     * @var array<int, array{
     *     identificador: int,
     *     dataIso: string|null,
     *     dataApresentacao: string,
     *     nomeAutor: string,
     *     nomeArtista: string,
     *     nomeOrigemGeografica: string,
     *     titulo: string,
     *     nomeTipoSeccao: string|null,
     *     ano: string,
     *     nomesGeneros: string,
     *     ligacao: string|null,
     *     avaliacao: array{
     *         media: string,
     *         quantidade: int,
     *         descricao: HtmlString,
     *         descricaoAcessivel: string
     *     },
     *     audicoes: array{
     *         quantidade: int,
     *         descricao: HtmlString,
     *         descricaoAcessivel: string
     *     }
     * }>
     *
     * @since 2.0.0
     */
    public readonly array $linhas;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  LengthAwarePaginator<int, SeccaoMetalThursday>
     *         $seccoesSimplificadas Secções paginadas.
     *
     * @throws LogicException Quando os modelos ou as relações necessárias
     *                        não possuem o tipo esperado.
     *
     * @since 1.0.0
     */
    public function __construct(
        LengthAwarePaginator $seccoesSimplificadas,
    ) {
        $this->seccoesSimplificadas =
            $seccoesSimplificadas;

        $this->linhas =
            $this->prepararLinhas(
                $seccoesSimplificadas,
            );
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista da tabela simplificada.
     *
     * @since 1.0.0
     */
    public function render(): View
    {
        return view(
            'components.metal-thursday.tabela-vista-simplificada',
        );
    }

    /**
     * Prepara todas as linhas da tabela.
     *
     * @param  LengthAwarePaginator<int, SeccaoMetalThursday>
     *         $seccoes Secções paginadas.
     * @return array<int, array<string, mixed>> Linhas preparadas.
     *
     * @throws LogicException Quando o paginador contém um modelo inesperado.
     *
     * @since 2.0.0
     */
    private function prepararLinhas(
        LengthAwarePaginator $seccoes,
    ): array {
        $linhas = [];

        foreach ($seccoes->items() as $seccao) {
            if (! $seccao instanceof SeccaoMetalThursday) {
                throw new LogicException(
                    'A paginação simplificada contém um modelo inesperado.',
                );
            }

            $linhas[] =
                $this->prepararLinha(
                    $seccao,
                );
        }

        return $linhas;
    }

    /**
     * Prepara uma linha da tabela.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção apresentada.
     * @return array<string, mixed> Linha preparada.
     *
     * @since 2.0.0
     */
    private function prepararLinha(
        SeccaoMetalThursday $seccao,
    ): array {
        $identificador =
            $this->obterIdentificador(
                $seccao,
            );

        /** @var MetalThursday $metalThursday */
        $metalThursday =
            $this->obterModeloRelacionado(
                $seccao,
                'metalThursday',
                MetalThursday::class,
                true,
            );

        /** @var Utilizador|null $autor */
        $autor =
            $this->obterModeloRelacionado(
                $metalThursday,
                'autor',
                Utilizador::class,
            );

        /** @var Artista|null $artista */
        $artista =
            $this->obterModeloRelacionado(
                $seccao,
                'artista',
                Artista::class,
            );

        /** @var TipoSeccao $tipoSeccao */
        $tipoSeccao =
            $this->obterModeloRelacionado(
                $seccao,
                'tipoSeccao',
                TipoSeccao::class,
                true,
            );

        /** @var OrigemGeografica|null $origemGeografica */
        $origemGeografica = null;
        $generos = new Collection;

        if ($artista instanceof Artista) {
            $origemGeografica =
                $this->obterModeloRelacionado(
                    $artista,
                    'origemGeografica',
                    OrigemGeografica::class,
                );

            $generos =
                $this->obterColecaoRelacionada(
                    $artista,
                    'generos',
                );
        }

        $avaliacoes =
            $this->obterColecaoRelacionada(
                $seccao,
                'avaliacoes',
            );

        $audicoes =
            $this->obterColecaoRelacionada(
                $seccao,
                'audicoes',
            );

        $data =
            $this->prepararData(
                $metalThursday->data,
            );

        $quantidadeAvaliacoes =
            $this->obterContagem(
                $seccao,
                'avaliacoes_count',
                $avaliacoes,
            );

        $mediaAvaliacoes =
            $this->obterMediaAvaliacoes(
                $seccao,
                $avaliacoes,
            );

        $quantidadeAudicoes =
            $this->obterContagem(
                $seccao,
                'audicoes_count',
                $audicoes,
            );

        return [
            'identificador' => $identificador,

            'dataIso' => $data['iso'],

            'dataApresentacao' => $data['apresentacao'],

            'nomeAutor' => $this->normalizarTexto(
                $autor?->nome,
            )
                ?? 'Utilizador removido',

            'nomeArtista' => $this->normalizarTexto(
                $artista?->nome,
            )
                ?? 'Artista indisponível',

            'nomeOrigemGeografica' => $this->normalizarTexto(
                $origemGeografica?->nome,
            )
                ?? 'Origem não indicada',

            'titulo' => $this->normalizarTexto(
                $seccao->titulo,
            )
                ?? 'Título indisponível',

            'nomeTipoSeccao' => $this->normalizarTexto(
                $tipoSeccao->nome,
            ),

            'ano' => $this->normalizarAno(
                $seccao->ano,
            ),

            'nomesGeneros' => $this->prepararNomesGeneros(
                $generos,
            ),

            'ligacao' => $this->normalizarTexto(
                $seccao->ligacao,
            ),

            'avaliacao' => [
                'media' => $this->formatarPontuacao(
                    $mediaAvaliacoes,
                ),

                'quantidade' => $quantidadeAvaliacoes,

                'descricao' => $this->criarDescricaoAvaliacoes(
                    $avaliacoes,
                ),

                'descricaoAcessivel' => $this->criarDescricaoAcessivelAvaliacoes(
                    $mediaAvaliacoes,
                    $quantidadeAvaliacoes,
                ),
            ],

            'audicoes' => [
                'quantidade' => $quantidadeAudicoes,

                'descricao' => $this->criarDescricaoAudicoes(
                    $audicoes,
                ),

                'descricaoAcessivel' => $this->criarDescricaoAcessivelAudicoes(
                    $quantidadeAudicoes,
                ),
            ],
        ];
    }

    /**
     * Prepara uma data para apresentação.
     *
     * @param  mixed  $data  Data recebida.
     * @return array{
     *     iso: string|null,
     *     apresentacao: string
     * } Data preparada.
     *
     * @throws LogicException Quando a data possui um tipo inesperado.
     *
     * @since 2.0.0
     */
    private function prepararData(
        mixed $data,
    ): array {
        if ($data === null) {
            return [
                'iso' => null,

                'apresentacao' => 'Data indisponível',
            ];
        }

        if (! $data instanceof DateTimeInterface) {
            throw new LogicException(
                'A data da MetalThursday possui um tipo inesperado.',
            );
        }

        return [
            'iso' => $data->format(
                'Y-m-d',
            ),

            'apresentacao' => $data->format(
                'd/m/Y',
            ),
        ];
    }

    /**
     * Prepara os nomes dos géneros de um artista.
     *
     * @param  Collection<int, Model>  $generos  Géneros carregados.
     * @return string Nomes separados por vírgulas ou travessão.
     *
     * @throws LogicException Quando existe um modelo inesperado.
     *
     * @since 2.0.0
     */
    private function prepararNomesGeneros(
        Collection $generos,
    ): string {
        $nomes = [];

        foreach ($generos as $genero) {
            if (! $genero instanceof Genero) {
                throw new LogicException(
                    'A relação "generos" contém um modelo inesperado.',
                );
            }

            $nome =
                $this->normalizarTexto(
                    $genero->nome,
                );

            if ($nome !== null) {
                $nomes[] =
                    $nome;
            }
        }

        return $nomes !== []
            ? implode(
                ', ',
                $nomes,
            )
            : '—';
    }

    /**
     * Cria a descrição HTML das avaliações.
     *
     * @param  Collection<int, Model>  $avaliacoes  Avaliações carregadas.
     * @return HtmlString Descrição segura.
     *
     * @since 2.0.0
     */
    private function criarDescricaoAvaliacoes(
        Collection $avaliacoes,
    ): HtmlString {
        $linhas = [];

        foreach ($avaliacoes as $avaliacao) {
            /** @var Utilizador|null $utilizador */
            $utilizador =
                $this->obterModeloRelacionado(
                    $avaliacao,
                    'utilizador',
                    Utilizador::class,
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
                    'Esta secção ainda não tem avaliações.',
                ),
        );
    }

    /**
     * Cria a descrição HTML das audições.
     *
     * @param  Collection<int, Model>  $audicoes  Audições carregadas.
     * @return HtmlString Descrição segura.
     *
     * @since 2.0.0
     */
    private function criarDescricaoAudicoes(
        Collection $audicoes,
    ): HtmlString {
        $linhas = [];

        foreach ($audicoes as $audicao) {
            /** @var Utilizador|null $utilizador */
            $utilizador =
                $this->obterModeloRelacionado(
                    $audicao,
                    'utilizador',
                    Utilizador::class,
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
                    'Ninguém marcou esta secção como ouvida.',
                ),
        );
    }

    /**
     * Obtém a média das avaliações.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção consultada.
     * @param  Collection<int, Model>  $avaliacoes  Avaliações carregadas.
     * @return float Média normalizada.
     *
     * @since 2.0.0
     */
    private function obterMediaAvaliacoes(
        SeccaoMetalThursday $seccao,
        Collection $avaliacoes,
    ): float {
        $mediaCarregada =
            $seccao->getAttribute(
                'avaliacoes_avg_pontuacao',
            );

        if (is_numeric($mediaCarregada)) {
            return max(
                0.0,
                (float) $mediaCarregada,
            );
        }

        $media =
            $avaliacoes->avg(
                'pontuacao',
            );

        return is_numeric($media)
            ? max(
                0.0,
                (float) $media,
            )
            : 0.0;
    }

    /**
     * Obtém uma contagem carregada ou calcula-a em memória.
     *
     * @param  Model  $modelo  Modelo consultado.
     * @param  string  $atributo  Nome do atributo de contagem.
     * @param  Collection<int, Model>  $colecao  Relação correspondente.
     * @return int Contagem normalizada.
     *
     * @since 2.0.0
     */
    private function obterContagem(
        Model $modelo,
        string $atributo,
        Collection $colecao,
    ): int {
        $valor =
            $modelo->getAttribute(
                $atributo,
            );

        return is_numeric($valor)
            ? max(
                0,
                (int) $valor,
            )
            : $colecao->count();
    }

    /**
     * Obtém um modelo relacionado previamente carregado.
     *
     * @template TModelo of Model
     *
     * @param  Model  $modelo  Modelo consultado.
     * @param  string  $relacao  Nome da relação.
     * @param  class-string<TModelo>  $classeEsperada  Classe esperada.
     * @param  bool  $obrigatoria  Indica se a relação não pode ser nula.
     * @return TModelo|null Modelo relacionado.
     *
     * @throws LogicException Quando a relação não está carregada, é nula
     *                        quando obrigatória ou possui tipo inesperado.
     *
     * @since 2.0.0
     */
    private function obterModeloRelacionado(
        Model $modelo,
        string $relacao,
        string $classeEsperada,
        bool $obrigatoria = false,
    ): ?Model {
        if (! $modelo->relationLoaded($relacao)) {
            throw new LogicException(
                sprintf(
                    'A relação "%s" deve estar carregada.',
                    $relacao,
                ),
            );
        }

        $modeloRelacionado =
            $modelo->getRelation(
                $relacao,
            );

        if ($modeloRelacionado === null) {
            if ($obrigatoria) {
                throw new LogicException(
                    sprintf(
                        'A relação "%s" não pode ser nula.',
                        $relacao,
                    ),
                );
            }

            return null;
        }

        if (! $modeloRelacionado instanceof $classeEsperada) {
            throw new LogicException(
                sprintf(
                    'A relação "%s" possui um tipo inesperado.',
                    $relacao,
                ),
            );
        }

        return $modeloRelacionado;
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
     * @since 2.0.0
     */
    private function obterColecaoRelacionada(
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
     * Obtém o identificador persistido da secção.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção consultada.
     * @return int Identificador da secção.
     *
     * @throws LogicException Quando a secção não está persistida.
     *
     * @since 2.0.0
     */
    private function obterIdentificador(
        SeccaoMetalThursday $seccao,
    ): int {
        $identificador =
            $seccao->getKey();

        if (
            ! $seccao->exists
            || ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new LogicException(
                'A secção deve estar persistida.',
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
     * @since 2.0.0
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
     * Normaliza o ano para apresentação.
     *
     * @param  mixed  $ano  Ano recebido.
     * @return string Ano normalizado ou travessão.
     *
     * @since 2.0.0
     */
    private function normalizarAno(
        mixed $ano,
    ): string {
        if (
            ! is_numeric($ano)
            || (int) $ano < 1
        ) {
            return '—';
        }

        return (string) (int) $ano;
    }

    /**
     * Normaliza um valor decimal.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return float Valor normalizado.
     *
     * @since 2.0.0
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
     * @since 2.0.0
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

    /**
     * Cria a descrição acessível das avaliações.
     *
     * @param  float  $media  Média das avaliações.
     * @param  int  $quantidade  Quantidade de avaliações.
     * @return string Descrição acessível.
     *
     * @since 2.0.0
     */
    private function criarDescricaoAcessivelAvaliacoes(
        float $media,
        int $quantidade,
    ): string {
        return sprintf(
            'Média %s, com %d %s',
            $this->formatarPontuacao(
                $media,
            ),
            $quantidade,
            $quantidade === 1
                ? 'avaliação'
                : 'avaliações',
        );
    }

    /**
     * Cria a descrição acessível das audições.
     *
     * @param  int  $quantidade  Quantidade de audições.
     * @return string Descrição acessível.
     *
     * @since 2.0.0
     */
    private function criarDescricaoAcessivelAudicoes(
        int $quantidade,
    ): string {
        return sprintf(
            '%d %s',
            $quantidade,
            $quantidade === 1
                ? 'audição'
                : 'audições',
        );
    }
}
