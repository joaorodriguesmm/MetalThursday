<?php

declare(strict_types=1);

namespace App\Http\Controllers\Musica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Musica\AtualizarArtistaRequest;
use App\Http\Requests\Musica\CriarArtistaRequest;
use App\Models\Geografia\OrigemGeografica;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gere a consulta, criação, atualização e eliminação de artistas.
 *
 * A persistência dos dados principais e a sincronização dos géneros são
 * executadas atomicamente. As operações sobre artistas existentes voltam a
 * obter e a bloquear o registo dentro da transação.
 *
 * @since 1.0.0
 */
final class ControladorArtista extends Controller
{
    use AuthorizesRequests;

    /**
     * Número de registos apresentados por página.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const REGISTOS_POR_PAGINA =
        20;

    /**
     * Comprimento máximo do termo de pesquisa.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_MAXIMO_PESQUISA =
        100;

    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO =
        3;

    /**
     * Apresenta a lista paginada de artistas.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return View Listagem de artistas.
     *
     * @since 1.0.0
     */
    public function indice(
        Request $pedido,
    ): View {
        $this->authorize(
            'viewAny',
            Artista::class,
        );

        $pesquisa =
            $this->normalizarPesquisa(
                $pedido->query(
                    'pesquisa',
                ),
            );

        $artistas =
            Artista::query()
                ->select([
                    'id',
                    'nome',
                    'origem_geografica_id',
                    'criado_por_id',
                ])
                ->with([
                    'origemGeografica:id,nome',
                    'generos:id,nome',
                ])
                ->when(
                    $pesquisa !== null,
                    static fn (
                        Builder $construtor,
                    ): Builder => $construtor->where(
                        'nome',
                        'like',
                        '%'.$pesquisa.'%',
                    ),
                )
                ->orderBy(
                    'nome',
                )
                ->orderBy(
                    'id',
                )
                ->paginate(
                    self::REGISTOS_POR_PAGINA,
                )
                ->withQueryString();

        return view(
            'musica.artistas.indice',
            [
                'artistas' => $artistas,

                'pesquisaAtual' => $pesquisa,
            ],
        );
    }

    /**
     * Apresenta o formulário de criação de um artista.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @return View Formulário de criação.
     *
     * @since 1.0.0
     */
    public function criar(
        Request $pedido,
    ): View {
        $this->authorize(
            'create',
            Artista::class,
        );

        return view(
            'musica.artistas.criar',
            $this->obterDadosFormulario(
                $pedido,
            ),
        );
    }

    /**
     * Guarda um novo artista e sincroniza os respetivos géneros.
     *
     * @param  CriarArtistaRequest  $pedido  Pedido validado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     */
    public function guardar(
        CriarArtistaRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            Artista::class,
        );

        /**
         * @var array{
         *     nome: string,
         *     origem_geografica_id: int,
         *     generos: list<int>
         * } $dados
         */
        $dados =
            $pedido->validated();

        $artista =
            DB::transaction(
                static function () use (
                    $dados,
                ): Artista {
                    $artista = new Artista([
                        'nome' => $dados['nome'],
                    ]);

                    $artista
                        ->origemGeografica()
                        ->associate(
                            $dados['origem_geografica_id'],
                        );

                    $artista->saveOrFail();

                    $artista
                        ->generos()
                        ->sync(
                            $dados['generos'],
                        );

                    return $artista;
                },
                self::TENTATIVAS_TRANSACAO,
            );

        if ($pedido->expectsJson()) {
            $artista->load([
                'origemGeografica:id,nome',
                'generos:id,nome',
            ]);

            return response()->json(
                [
                    'mensagem' => 'Artista criado com sucesso.',

                    'artista' => $this->serializarArtista(
                        $artista,
                    ),
                ],
                Response::HTTP_CREATED,
            );
        }

        return to_route(
            'artistas.indice',
        )->with(
            'sucesso',
            'Artista criado com sucesso.',
        );
    }

    /**
     * Apresenta os detalhes de um artista.
     *
     * @param  Artista  $artista  Artista apresentado.
     * @return View Página de detalhes.
     *
     * @since 1.0.0
     */
    public function detalhes(
        Artista $artista,
    ): View {
        $this->authorize(
            'view',
            $artista,
        );

        $artista->loadMissing([
            'origemGeografica:id,nome',
            'generos:id,nome',
        ]);

        $modeloSeccao =
            new SeccaoMetalThursday;

        $modeloMetalThursday =
            new MetalThursday;

        $tabelaSeccoes =
            $modeloSeccao->getTable();

        $tabelaMetalThursdays =
            $modeloMetalThursday->getTable();

        $aliasOrdenacao =
            'metal_thursdays_ordenacao';

        $seccoes =
            SeccaoMetalThursday::query()
                ->select([
                    $tabelaSeccoes.'.id',
                    $tabelaSeccoes.'.metal_thursday_id',
                    $tabelaSeccoes.'.tipo_seccao_id',
                    $tabelaSeccoes.'.titulo',
                    $tabelaSeccoes.'.descricao',
                    $tabelaSeccoes.'.ligacao',
                    $tabelaSeccoes.'.ano',
                ])
                ->join(
                    $tabelaMetalThursdays
                        .' as '
                        .$aliasOrdenacao,
                    $aliasOrdenacao.'.id',
                    '=',
                    $tabelaSeccoes.'.metal_thursday_id',
                )
                ->where(
                    $tabelaSeccoes.'.artista_id',
                    $artista->getKey(),
                )
                ->whereHas(
                    'metalThursday',
                    static fn (
                        Builder $construtor,
                    ): Builder => $construtor->publicadas(),
                )
                ->with([
                    'metalThursday:id,autor_id,data,deleted_at',
                    'metalThursday.autor:id,nome',
                    'tipoSeccao:id,nome',
                ])
                ->orderByDesc(
                    $aliasOrdenacao.'.data',
                )
                ->orderByDesc(
                    $tabelaSeccoes.'.id',
                )
                ->paginate(
                    self::REGISTOS_POR_PAGINA,
                )
                ->withQueryString();

        return view(
            'musica.artistas.detalhes',
            [
                'artista' => $artista,

                'seccoes' => $seccoes,

                ...$this->obterDadosCabecalho(
                    $artista,
                ),
            ],
        );
    }

    /**
     * Apresenta o formulário de edição de um artista.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Artista  $artista  Artista editado.
     * @return View Formulário de edição.
     *
     * @since 1.0.0
     */
    public function editar(
        Request $pedido,
        Artista $artista,
    ): View {
        $this->authorize(
            'update',
            $artista,
        );

        return view(
            'musica.artistas.editar',
            $this->obterDadosFormulario(
                $pedido,
                $artista,
            ),
        );
    }

    /**
     * Atualiza um artista e sincroniza os respetivos géneros.
     *
     * O registo é novamente obtido e bloqueado dentro da transação. A
     * autorização é verificada antes de iniciar a operação.
     *
     * O artista só é persistido quando os atributos ou a relação de géneros
     * sofrem uma alteração efetiva. Desta forma, os dados de auditoria não são
     * atualizados por uma submissão idêntica, mas continuam a refletir uma
     * alteração que afecte apenas os géneros.
     *
     * @param  AtualizarArtistaRequest  $pedido  Pedido validado.
     * @param  Artista  $artista  Artista atualizado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     */
    public function atualizar(
        AtualizarArtistaRequest $pedido,
        Artista $artista,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $artista,
        );

        /**
         * @var array{
         *     nome: string,
         *     origem_geografica_id: int,
         *     generos: list<int>
         * } $dados
         */
        $dados =
            $pedido->validated();

        $artistaAtualizado =
            DB::transaction(
                function () use (
                    $artista,
                    $dados,
                ): Artista {
                    $artistaBloqueado =
                        Artista::query()
                            ->whereKey(
                                $artista->getKey(),
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $artistaBloqueado->nome =
                        $dados['nome'];

                    $artistaBloqueado
                        ->origemGeografica()
                        ->associate(
                            $dados['origem_geografica_id'],
                        );

                    $alteracoesGeneros =
                        $artistaBloqueado
                            ->generos()
                            ->sync(
                                $dados['generos'],
                            );

                    if (
                        $artistaBloqueado->isDirty()
                        || $alteracoesGeneros['attached'] !== []
                        || $alteracoesGeneros['detached'] !== []
                        || $alteracoesGeneros['updated'] !== []
                    ) {
                        $artistaBloqueado->saveOrFail();
                    }

                    return $artistaBloqueado;
                },
                self::TENTATIVAS_TRANSACAO,
            );

        if ($pedido->expectsJson()) {
            $artistaAtualizado->load([
                'origemGeografica:id,nome',
                'generos:id,nome',
            ]);

            return response()->json([
                'mensagem' => 'Artista atualizado com sucesso.',

                'artista' => $this->serializarArtista(
                    $artistaAtualizado,
                ),
            ]);
        }

        return to_route(
            'artistas.indice',
        )->with(
            'sucesso',
            'Artista atualizado com sucesso.',
        );
    }

    /**
     * Elimina logicamente um artista.
     *
     * A autorização é verificada antes de bloquear e eliminar o registo.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Artista  $artista  Artista eliminado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     */
    public function eliminar(
        Request $pedido,
        Artista $artista,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'delete',
            $artista,
        );

        DB::transaction(
            function () use (
                $artista,
            ): void {
                $artistaBloqueado =
                    Artista::query()
                        ->whereKey(
                            $artista->getKey(),
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $artistaBloqueado->deleteOrFail();
            },
            self::TENTATIVAS_TRANSACAO,
        );

        if ($pedido->expectsJson()) {
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT,
            );
        }

        return to_route(
            'artistas.indice',
        )->with(
            'sucesso',
            'Artista eliminado com sucesso.',
        );
    }

    /**
     * Obtém os dados utilizados pelo formulário de artistas.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Artista|null  $artista  Artista editado ou nulo durante a criação.
     * @return array{
     *     artista: Artista|null,
     *     origensGeograficas: Collection<int, OrigemGeografica>,
     *     generos: Collection<int, Genero>,
     *     emEdicao: bool,
     *     enderecoFormulario: string,
     *     nomeArtista: string,
     *     identificadorOrigemGeograficaSelecionada: string,
     *     identificadoresGenerosSelecionados: list<string>,
     *     textoBotaoSubmissao: string
     * } Dados preparados.
     *
     * @since 2.0.0
     */
    private function obterDadosFormulario(
        Request $pedido,
        ?Artista $artista = null,
    ): array {
        $emEdicao =
            $artista instanceof Artista;

        if ($emEdicao) {
            $artista->loadMissing(
                'generos:id,nome',
            );

            $enderecoFormulario =
                route(
                    'artistas.atualizar',
                    $artista,
                );

            $identificadoresGenerosModelo =
                $artista
                    ->generos
                    ->modelKeys();
        } else {
            $enderecoFormulario =
                route(
                    'artistas.guardar',
                );

            $identificadoresGenerosModelo =
                [];
        }

        return [
            'artista' => $artista,

            'origensGeograficas' => OrigemGeografica::query()
                ->select([
                    'id',
                    'nome',
                ])
                ->orderBy(
                    'nome',
                )
                ->orderBy(
                    'id',
                )
                ->get(),

            'generos' => Genero::query()
                ->select([
                    'id',
                    'nome',
                ])
                ->orderBy(
                    'nome',
                )
                ->orderBy(
                    'id',
                )
                ->get(),

            'emEdicao' => $emEdicao,

            'enderecoFormulario' => $enderecoFormulario,

            'nomeArtista' => $this->normalizarTextoFormulario(
                $pedido->old(
                    'nome',
                    $artista?->nome,
                ),
            ),

            'identificadorOrigemGeograficaSelecionada' => $this->normalizarIdentificadorFormulario(
                $pedido->old(
                    'origem_geografica_id',
                    $artista?->origem_geografica_id,
                ),
            ),

            'identificadoresGenerosSelecionados' => $this->normalizarListaIdentificadoresFormulario(
                $pedido->old(
                    'generos',
                    $identificadoresGenerosModelo,
                ),
            ),

            'textoBotaoSubmissao' => $emEdicao
                ? 'Guardar alterações'
                : 'Criar artista',
        ];
    }

    /**
     * Prepara os dados do cabeçalho da página de detalhes do artista.
     *
     * @param  Artista  $artista  Artista apresentado.
     * @return array{
     *     nomeOrigemGeograficaArtista: string,
     *     nomesGenerosArtista: string|null
     * } Dados preparados.
     *
     * @throws LogicException Quando a origem geográfica do artista não está
     *                        disponível.
     *
     * @since 2.0.0
     */
    private function obterDadosCabecalho(
        Artista $artista,
    ): array {
        $origemGeografica =
            $artista->origemGeografica;

        if (! $origemGeografica instanceof OrigemGeografica) {
            throw new LogicException(
                'O artista não possui uma origem geográfica válida.',
            );
        }

        $nomesGeneros = [];

        foreach ($artista->generos as $genero) {
            if (! $genero instanceof Genero) {
                throw new LogicException(
                    'O artista possui um género persistido inválido.',
                );
            }

            $nomesGeneros[] =
                $genero->nome;
        }

        return [
            'nomeOrigemGeograficaArtista' => $origemGeografica->nome,

            'nomesGenerosArtista' => $nomesGeneros !== []
                ? implode(
                    ', ',
                    $nomesGeneros,
                )
                : null,
        ];
    }

    /**
     * Converte um artista para o formato da resposta HTTP.
     *
     * @param  Artista  $artista  Artista convertido.
     * @return array{
     *     id: int,
     *     nome: string,
     *     origem_geografica_id: int,
     *     origem_geografica: array{id: int, nome: string},
     *     generos: list<array{id: int, nome: string}>
     * } Dados do artista.
     *
     * @throws LogicException Quando a origem geográfica do artista não está
     *                        disponível.
     *
     * @since 2.0.0
     */
    private function serializarArtista(
        Artista $artista,
    ): array {
        $origemGeografica =
            $artista->origemGeografica;

        if (! $origemGeografica instanceof OrigemGeografica) {
            throw new LogicException(
                'O artista não possui uma origem geográfica válida.',
            );
        }

        $generos = [];

        foreach ($artista->generos as $genero) {
            if (! $genero instanceof Genero) {
                throw new LogicException(
                    'O artista possui um género persistido inválido.',
                );
            }

            $generos[] = [
                'id' => (int) $genero->getKey(),

                'nome' => $genero->nome,
            ];
        }

        return [
            'id' => (int) $artista->getKey(),

            'nome' => $artista->nome,

            'origem_geografica_id' => (int) $origemGeografica->getKey(),

            'origem_geografica' => [
                'id' => (int) $origemGeografica->getKey(),

                'nome' => $origemGeografica->nome,
            ],

            'generos' => $generos,
        ];
    }

    /**
     * Normaliza o termo de pesquisa.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Termo normalizado ou nulo.
     *
     * @since 2.0.0
     */
    private function normalizarPesquisa(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $pesquisa =
            trim(
                $valor,
            );

        if ($pesquisa === '') {
            return null;
        }

        return mb_substr(
            $pesquisa,
            0,
            self::COMPRIMENTO_MAXIMO_PESQUISA,
        );
    }

    /**
     * Normaliza um texto utilizado num formulário.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string Texto normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarTextoFormulario(
        mixed $valor,
    ): string {
        if (
            ! is_string($valor)
            && ! is_int($valor)
            && ! is_float($valor)
        ) {
            return '';
        }

        return trim(
            (string) $valor,
        );
    }

    /**
     * Normaliza um identificador utilizado num formulário.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string Identificador normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificadorFormulario(
        mixed $valor,
    ): string {
        if (is_int($valor)) {
            return $valor > 0
                ? (string) $valor
                : '';
        }

        if (! is_string($valor)) {
            return '';
        }

        $identificador =
            trim(
                $valor,
            );

        if (
            $identificador === ''
            || ! ctype_digit($identificador)
            || (int) $identificador < 1
        ) {
            return '';
        }

        return (string) (int) $identificador;
    }

    /**
     * Normaliza uma lista de identificadores do formulário.
     *
     * Identificadores inválidos são ignorados apenas durante a reconstrução
     * visual do formulário. Valores repetidos são removidos.
     *
     * @param  mixed  $valores  Valores recebidos.
     * @return list<string> Identificadores normalizados.
     *
     * @since 2.0.0
     */
    private function normalizarListaIdentificadoresFormulario(
        mixed $valores,
    ): array {
        if (! is_array($valores)) {
            return [];
        }

        $identificadores = [];

        foreach ($valores as $valor) {
            $identificador =
                $this->normalizarIdentificadorFormulario(
                    $valor,
                );

            if ($identificador === '') {
                continue;
            }

            $identificadores[$identificador] =
                $identificador;
        }

        return array_values(
            $identificadores,
        );
    }
}
