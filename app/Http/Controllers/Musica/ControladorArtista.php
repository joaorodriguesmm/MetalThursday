<?php

declare(strict_types=1);

namespace App\Http\Controllers\Musica;

use App\Enumeracoes\EstadoAtividadeArtista;
use App\Http\Controllers\Controller;
use App\Http\Requests\Musica\AtualizarArtistaRequest;
use App\Http\Requests\Musica\CriarArtistaRequest;
use App\Models\Comum\Ligacao;
use App\Models\Geografia\OrigemGeografica;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gere a consulta, criação, atualização e eliminação de artistas.
 *
 * @since 1.0.0
 */
final class ControladorArtista extends Controller
{
    use AuthorizesRequests;

    /**
     * Número de registos apresentados por página.
     *
     * @since 2.0.0
     */
    private const REGISTOS_POR_PAGINA = 20;

    /**
     * Comprimento máximo do termo de pesquisa.
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_MAXIMO_PESQUISA = 100;

    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Apresenta a lista paginada de artistas.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @return View Listagem de artistas.
     *
     * @since 1.0.0
     */
    public function indice(Request $pedido): View
    {
        $this->authorize(
            'viewAny',
            Artista::class,
        );

        $pesquisa = $this->normalizarPesquisa(
            $pedido->query('pesquisa'),
        );

        $artistas = Artista::query()
            ->select([
                'id',
                'nome',
                'origem_geografica_id',
                'ano_inicio_atividade',
                'estado_atividade',
                'criado_por_id',
            ])
            ->with([
                'origemGeografica:id,nome',
                'generos:id,nome',
            ])
            ->when(
                $pesquisa !== null,
                static fn (Builder $construtor): Builder => $construtor->where(
                    'nome',
                    'like',
                    '%'.$pesquisa.'%',
                ),
            )
            ->orderBy('nome')
            ->orderBy('id')
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
    public function criar(Request $pedido): View
    {
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
     * Guarda um novo artista e todas as relações do respetivo perfil.
     *
     * A persistência dos dados principais, géneros e ligações é executada na
     * mesma transação. Quando existem artistas ativos com o mesmo nome, a
     * criação exige confirmação explícita antes de prosseguir.
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

        /** @var array<string, mixed> $dados */
        $dados = $pedido->validated();

        $artistasHomonimos = Artista::query()
            ->select([
                'id',
                'nome',
                'origem_geografica_id',
                'ano_inicio_atividade',
            ])
            ->with([
                'origemGeografica:id,nome',
            ])
            ->where(
                'nome',
                $dados['nome'],
            )
            ->orderBy('id')
            ->get();

        if (
            $artistasHomonimos->isNotEmpty()
            && ! array_key_exists(
                'confirmar_nome_repetido',
                $dados,
            )
        ) {
            $artistasHomonimosSerializados = [];

            foreach ($artistasHomonimos as $artistaHomonimo) {
                $artistasHomonimosSerializados[] = $this->serializarArtistaHomonimo(
                    $artistaHomonimo,
                );
            }

            $dadosConfirmacao = [
                'codigo' => 'confirmacao_nome_repetido_necessaria',
                'mensagem' => 'Já existem artistas com este nome. Confirma se pretendes criar um novo artista.',
                'artistas_homonimos' => $artistasHomonimosSerializados,
            ];

            if ($pedido->expectsJson()) {
                return response()->json(
                    $dadosConfirmacao,
                    Response::HTTP_CONFLICT,
                );
            }

            return back()
                ->withInput()
                ->with(
                    'confirmacao_nome_repetido',
                    $dadosConfirmacao,
                );
        }

        $ligacoes = $this->prepararLigacoesPersistencia(
            $dados['ligacoes'] ?? [],
        );

        $artista = DB::transaction(
            function () use (
                $dados,
                $ligacoes,
            ): Artista {
                $artista = new Artista;

                $this->aplicarDadosPerfil(
                    $artista,
                    $dados,
                );

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

                if ($ligacoes !== []) {
                    $artista
                        ->ligacoes()
                        ->createMany(
                            $ligacoes,
                        );
                }

                return $artista;
            },
            self::TENTATIVAS_TRANSACAO,
        );

        if ($pedido->expectsJson()) {
            $artista->load([
                'origemGeografica:id,nome',
                'generos:id,nome',
                'ligacoes:id,tipo_ligavel,ligavel_id,titulo,url,ordem',
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
     * Apresenta os detalhes de um artista e as respetivas aparições publicadas.
     *
     * @param  Artista  $artista  Artista apresentado.
     * @return View Página de detalhes do artista.
     *
     * @throws LogicException Quando uma relação carregada contém dados
     *                        persistidos inválidos.
     *
     * @since 1.0.0
     */
    public function detalhes(Artista $artista): View
    {
        $this->authorize(
            'view',
            $artista,
        );

        $artista->loadMissing([
            'origemGeografica:id,nome',
            'generos:id,nome',
            'ligacoes:id,tipo_ligavel,ligavel_id,titulo,url,ordem',
        ]);

        $seccoes = $this
            ->criarConsultaAparicoesPublicadas(
                $artista,
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
     * Lista as aparições publicadas de um artista para apresentação contextual.
     *
     * Uma MetalThursday pode ser excluída, permitindo que o formulário de edição
     * apresente apenas aparições anteriores e não a própria publicação editada.
     *
     * Um artista eliminado logicamente apenas pode ser consultado neste contexto
     * quando já se encontra associado à MetalThursday que está a ser editada.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  string  $identificadorArtista  Identificador do artista consultado.
     * @return JsonResponse Resposta com as aparições publicadas.
     *
     * @since 2.0.0
     */
    public function listarAparicoesMetalThursday(
        Request $pedido,
        string $identificadorArtista,
    ): JsonResponse {
        $dados = $pedido->validate([
            'metal_thursday_excluida' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists(
                    MetalThursday::class,
                    'id',
                )->whereNull(
                    'deleted_at',
                ),
            ],
        ]);

        $identificadorMetalThursdayExcluida =
            array_key_exists(
                'metal_thursday_excluida',
                $dados,
            )
            ? (int) $dados['metal_thursday_excluida']
            : null;

        $metalThursdayExcluida =
            $identificadorMetalThursdayExcluida !== null
            ? MetalThursday::query()
                ->findOrFail(
                    $identificadorMetalThursdayExcluida,
                )
            : null;

        $artista = Artista::withTrashed()
            ->findOrFail(
                $identificadorArtista,
            );

        if ($artista->trashed()) {
            if (! $metalThursdayExcluida instanceof MetalThursday) {
                abort(
                    Response::HTTP_NOT_FOUND,
                );
            }

            $this->authorize(
                'update',
                $metalThursdayExcluida,
            );

            $artistaPertenceAMetalThursday =
                SeccaoMetalThursday::query()
                    ->where(
                        'metal_thursday_id',
                        $metalThursdayExcluida->getKey(),
                    )
                    ->where(
                        'artista_id',
                        $artista->getKey(),
                    )
                    ->exists();

            if (! $artistaPertenceAMetalThursday) {
                abort(
                    Response::HTTP_NOT_FOUND,
                );
            }
        }

        $this->authorize(
            'view',
            $artista,
        );

        $aparicoes = $this
            ->criarConsultaAparicoesPublicadas(
                $artista,
                $metalThursdayExcluida,
            )
            ->get()
            ->map(
                static function (
                    SeccaoMetalThursday $seccao,
                ): array {
                    $metalThursday =
                        $seccao->metalThursday;

                    $tipoSeccao =
                        $seccao->tipoSeccao;

                    if (
                        ! $metalThursday instanceof MetalThursday
                        || $tipoSeccao === null
                    ) {
                        throw new LogicException(
                            'Uma aparição do artista possui relações inválidas.',
                        );
                    }

                    return [
                        'identificador' => (int) $seccao->getKey(),

                        'tipo' => $tipoSeccao->nome,

                        'titulo' => $seccao->titulo,

                        'ano' => $seccao->ano,

                        'autor' => $metalThursday->autor?->nome
                            ?? 'Utilizador removido',

                        'data' => $metalThursday->data->format(
                            'Y-m-d',
                        ),

                        'endereco_metal_thursday' => route(
                            'metal-thursday.detalhes',
                            $metalThursday,
                        ),

                        'ligacao' => $seccao->ligacao,
                    ];
                },
            )
            ->values()
            ->all();

        return response()->json([
            'aparicoes' => $aparicoes,
        ]);
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
     * Atualiza o perfil completo de um artista.
     *
     * O registo é novamente obtido e bloqueado dentro da transação. O artista
     * só é persistido quando existe uma alteração efetiva nos atributos,
     * géneros ou ligações.
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

        /** @var array<string, mixed> $dados */
        $dados = $pedido->validated();

        $ligacoes = $this->prepararLigacoesPersistencia(
            $dados['ligacoes'] ?? [],
        );

        $artistaAtualizado = DB::transaction(
            function () use (
                $artista,
                $dados,
                $ligacoes,
            ): Artista {
                $artistaBloqueado = Artista::query()
                    ->whereKey(
                        $artista->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->aplicarDadosPerfil(
                    $artistaBloqueado,
                    $dados,
                );

                $artistaBloqueado
                    ->origemGeografica()
                    ->associate(
                        $dados['origem_geografica_id'],
                    );

                $alteracoesGeneros = $artistaBloqueado
                    ->generos()
                    ->sync(
                        $dados['generos'],
                    );

                $ligacoesAlteradas = $this->sincronizarLigacoesSeNecessario(
                    $artistaBloqueado,
                    $ligacoes,
                );

                if (
                    $artistaBloqueado->isDirty()
                    || $alteracoesGeneros['attached'] !== []
                    || $alteracoesGeneros['detached'] !== []
                    || $alteracoesGeneros['updated'] !== []
                    || $ligacoesAlteradas
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
                'ligacoes:id,tipo_ligavel,ligavel_id,titulo,url,ordem',
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
     * O registo é novamente obtido e bloqueado dentro da transação antes da
     * eliminação lógica.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
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
            function () use ($artista): void {
                $artistaBloqueado = Artista::query()
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
     * Constrói a consulta das aparições publicadas de um artista.
     *
     * A consulta exclui MetalThursdays ainda preparadas para datas futuras e
     * mantém uma ordenação determinística da aparição mais recente para a mais
     * antiga.
     *
     * Opcionalmente, uma MetalThursday pode ser excluída da consulta. Esta
     * possibilidade será utilizada pelo contexto histórico do formulário de
     * edição para não apresentar a própria publicação como aparição anterior.
     *
     * @param  Artista  $artista  Artista consultado.
     * @param  MetalThursday|null  $metalThursdayExcluida  Publicação excluída.
     * @return Builder<SeccaoMetalThursday> Consulta preparada.
     *
     * @since 2.0.0
     */
    private function criarConsultaAparicoesPublicadas(
        Artista $artista,
        ?MetalThursday $metalThursdayExcluida = null,
    ): Builder {
        $modeloSeccao = new SeccaoMetalThursday;
        $modeloMetalThursday = new MetalThursday;

        $tabelaSeccoes =
            $modeloSeccao->getTable();

        $tabelaMetalThursdays =
            $modeloMetalThursday->getTable();

        $aliasOrdenacao =
            'metal_thursdays_ordenacao';

        return SeccaoMetalThursday::query()
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
                $tabelaMetalThursdays.' as '.$aliasOrdenacao,
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
            ->when(
                $metalThursdayExcluida instanceof MetalThursday,
                static fn (
                    Builder $construtor,
                ): Builder => $construtor->where(
                    $tabelaSeccoes.'.metal_thursday_id',
                    '!=',
                    $metalThursdayExcluida->getKey(),
                ),
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
            );
    }

    /**
     * Aplica ao artista os atributos escalares validados do respetivo perfil.
     *
     * As relações com a origem geográfica, os géneros e as ligações são geridas
     * separadamente pelo controlador.
     *
     * @param  Artista  $artista  Artista que recebe os dados.
     * @param  array<string, mixed>  $dados  Dados validados do pedido.
     *
     * @since 2.0.0
     */
    private function aplicarDadosPerfil(
        Artista $artista,
        array $dados,
    ): void {
        $artista->nome =
            $dados['nome'];

        $artista->ano_inicio_atividade =
            $dados['ano_inicio_atividade']
            ?? null;

        $artista->ano_fim_atividade =
            $dados['ano_fim_atividade']
            ?? null;

        $artista->estado_atividade =
            $dados['estado_atividade']
            ?? null;

        $artista->biografia =
            $dados['biografia']
            ?? null;

        $artista->imagem =
            $dados['imagem']
            ?? null;

        $artista->musicbrainz_id =
            $dados['musicbrainz_id']
            ?? null;

        $artista->discogs_id =
            $dados['discogs_id']
            ?? null;
    }

    /**
     * Converte as ligações validadas para o formato persistido.
     *
     * Entradas estruturalmente inválidas são ignoradas nesta fase defensiva,
     * uma vez que a validação HTTP já garante o contrato esperado.
     *
     * @param  mixed  $dadosLigacoes  Ligações validadas.
     * @return list<array{titulo: string, url: string, ordem: int}> Ligações.
     *
     * @since 2.0.0
     */
    private function prepararLigacoesPersistencia(mixed $dadosLigacoes): array
    {
        if (! is_array($dadosLigacoes)) {
            return [];
        }

        $ligacoes = [];

        foreach ($dadosLigacoes as $indice => $dadosLigacao) {
            if (! is_array($dadosLigacao)) {
                continue;
            }

            $titulo = $dadosLigacao['titulo'] ?? null;
            $url = $dadosLigacao['url'] ?? null;

            if (! is_string($titulo) || ! is_string($url)) {
                continue;
            }

            $ligacoes[] = [
                'titulo' => $titulo,
                'url' => $url,
                'ordem' => $indice + 1,
            ];
        }

        return $ligacoes;
    }

    /**
     * Sincroniza as ligações apenas quando o conteúdo realmente mudou.
     *
     * A coleção atual é comparada com o novo conjunto já normalizado. Quando
     * existe diferença, as ligações são recriadas dentro da mesma transação do
     * artista.
     *
     * @param  Artista  $artista  Artista cujas ligações são sincronizadas.
     * @param  list<array{titulo: string, url: string, ordem: int}>  $novasLigacoes  Ligações pretendidas.
     * @return bool Verdadeiro quando foi necessário alterar as ligações.
     *
     * @since 2.0.0
     */
    private function sincronizarLigacoesSeNecessario(
        Artista $artista,
        array $novasLigacoes,
    ): bool {
        $ligacoesAtuais = $artista
            ->ligacoes()
            ->get([
                'titulo',
                'url',
                'ordem',
            ])
            ->map(
                static fn (Ligacao $ligacao): array => [
                    'titulo' => $ligacao->titulo,
                    'url' => $ligacao->url,
                    'ordem' => $ligacao->ordem,
                ],
            )
            ->values()
            ->all();

        if ($ligacoesAtuais === $novasLigacoes) {
            return false;
        }

        $artista
            ->ligacoes()
            ->delete();

        if ($novasLigacoes !== []) {
            $artista
                ->ligacoes()
                ->createMany(
                    $novasLigacoes,
                );
        }

        return true;
    }

    /**
     * Obtém os dados utilizados pelo formulário de artistas.
     *
     * Durante a edição são carregados os géneros e as ligações atuais. Os
     * valores anteriores da sessão prevalecem sobre os dados persistidos para
     * permitir reconstruir corretamente o formulário após uma falha de
     * validação.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Artista|null  $artista  Artista editado ou nulo na criação.
     * @return array<string, mixed> Dados preparados para a vista.
     *
     * @since 2.0.0
     */
    private function obterDadosFormulario(
        Request $pedido,
        ?Artista $artista = null,
    ): array {
        $emEdicao = $artista instanceof Artista;

        if ($emEdicao) {
            $artista->loadMissing([
                'generos:id,nome',
                'ligacoes:id,tipo_ligavel,ligavel_id,titulo,url,ordem',
            ]);

            $enderecoFormulario = route(
                'artistas.atualizar',
                $artista,
            );

            $identificadoresGenerosModelo = $artista
                ->generos
                ->modelKeys();

            $ligacoesModelo = $artista
                ->ligacoes
                ->map(
                    static fn (Ligacao $ligacao): array => [
                        'titulo' => $ligacao->titulo,
                        'url' => $ligacao->url,
                    ],
                )
                ->all();
        } else {
            $enderecoFormulario = route(
                'artistas.guardar',
            );
            $identificadoresGenerosModelo = [];
            $ligacoesModelo = [];
        }

        $estadoModelo = $artista?->estado_atividade;

        return [
            'artista' => $artista,

            'origensGeograficas' => OrigemGeografica::query()
                ->select([
                    'id',
                    'nome',
                ])
                ->orderBy('nome')
                ->orderBy('id')
                ->get(),

            'generos' => Genero::query()
                ->select([
                    'id',
                    'nome',
                ])
                ->orderBy('nome')
                ->orderBy('id')
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

            'anoInicioAtividadeArtista' => $this->normalizarTextoFormulario(
                $pedido->old(
                    'ano_inicio_atividade',
                    $artista?->ano_inicio_atividade,
                ),
            ),

            'anoFimAtividadeArtista' => $this->normalizarTextoFormulario(
                $pedido->old(
                    'ano_fim_atividade',
                    $artista?->ano_fim_atividade,
                ),
            ),

            'estadoAtividadeArtista' => $this->normalizarTextoFormulario(
                $pedido->old(
                    'estado_atividade',
                    $estadoModelo instanceof EstadoAtividadeArtista
                        ? $estadoModelo->value
                        : null,
                ),
            ),

            'biografiaArtista' => $this->normalizarTextoFormularioSemCompactar(
                $pedido->old(
                    'biografia',
                    $artista?->biografia,
                ),
            ),

            'imagemArtista' => $this->normalizarTextoFormulario(
                $pedido->old(
                    'imagem',
                    $artista?->imagem,
                ),
            ),

            'identificadorMusicBrainzArtista' => $this->normalizarMbidFormulario(
                $pedido->old(
                    'musicbrainz_id',
                    $artista?->musicbrainz_id,
                ),
            ),

            'identificadorDiscogsArtista' => $this->normalizarIdentificadorFormulario(
                $pedido->old(
                    'discogs_id',
                    $artista?->discogs_id,
                ),
            ),

            'ligacoesFormulario' => $this->normalizarLigacoesFormulario(
                $pedido->old(
                    'ligacoes',
                    $ligacoesModelo,
                ),
            ),

            'identificadoresGenerosSelecionados' => $this->normalizarListaIdentificadoresFormulario(
                $pedido->old(
                    'generos',
                    $identificadoresGenerosModelo,
                ),
            ),

            'anoAtual' => (int) date('Y'),
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
     *     nomeOrigemGeograficaArtista: string|null,
     *     nomesGenerosArtista: string|null
     * } Dados preparados.
     *
     * @throws LogicException Quando a relação de géneros contém dados
     *                        persistidos inválidos.
     *
     * @since 2.0.0
     */
    private function obterDadosCabecalho(Artista $artista): array
    {
        $origemGeografica = $artista->origemGeografica;
        $nomesGeneros = [];

        foreach ($artista->generos as $genero) {
            if (! $genero instanceof Genero) {
                throw new LogicException(
                    'O artista possui um género persistido inválido.',
                );
            }

            $nomesGeneros[] = $genero->nome;
        }

        return [
            'nomeOrigemGeograficaArtista' => $origemGeografica instanceof OrigemGeografica
                ? $origemGeografica->nome
                : null,

            'nomesGenerosArtista' => $nomesGeneros !== []
                ? implode(', ', $nomesGeneros)
                : null,
        ];
    }

    /**
     * Converte um artista homónimo para o formato utilizado na confirmação.
     *
     * @param  Artista  $artista  Artista convertido.
     * @return array{
     *     id: int,
     *     nome: string,
     *     ano_inicio_atividade: int|null,
     *     origem_geografica: array{id: int, nome: string}|null
     * } Dados necessários para distinguir o homónimo.
     *
     * @since 2.0.0
     */
    private function serializarArtistaHomonimo(Artista $artista): array
    {
        $origemGeografica = $artista->origemGeografica;

        return [
            'id' => (int) $artista->getKey(),
            'nome' => $artista->nome,
            'ano_inicio_atividade' => $artista->ano_inicio_atividade,
            'origem_geografica' => $origemGeografica instanceof OrigemGeografica
                ? [
                    'id' => (int) $origemGeografica->getKey(),
                    'nome' => $origemGeografica->nome,
                ]
                : null,
        ];
    }

    /**
     * Converte um artista para o formato utilizado nas respostas HTTP.
     *
     * @param  Artista  $artista  Artista convertido.
     * @return array{
     *     id: int,
     *     nome: string,
     *     rotulo_selecao: string,
     *     origem_geografica_id: int|null,
     *     origem_geografica: array{
     *         id: int,
     *         nome: string
     *     }|null,
     *     ano_inicio_atividade: int|null,
     *     ano_fim_atividade: int|null,
     *     estado_atividade: string|null,
     *     estado_atividade_etiqueta: string|null,
     *     biografia: string|null,
     *     imagem: string|null,
     *     url_imagem: string|null,
     *     musicbrainz_id: string|null,
     *     url_musicbrainz: string|null,
     *     discogs_id: int|null,
     *     url_discogs: string|null,
     *     ligacoes: list<array{
     *         id: int,
     *         titulo: string,
     *         url: string,
     *         ordem: int
     *     }>,
     *     generos: list<array{
     *         id: int,
     *         nome: string
     *     }>
     * } Dados serializados.
     *
     * @throws LogicException Quando alguma relação persistida contém dados
     *                        inválidos.
     *
     * @since 2.0.0
     */
    private function serializarArtista(
        Artista $artista,
    ): array {
        $origemGeografica =
            $artista->origemGeografica;

        $generos = [];
        $ligacoes = [];

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

        foreach ($artista->ligacoes as $ligacao) {
            if (! $ligacao instanceof Ligacao) {
                throw new LogicException(
                    'O artista possui uma ligação persistida inválida.',
                );
            }

            $ligacoes[] = [
                'id' => (int) $ligacao->getKey(),

                'titulo' => $ligacao->titulo,

                'url' => $ligacao->url,

                'ordem' => $ligacao->ordem,
            ];
        }

        $estado =
            $artista->estado_atividade;

        return [
            'id' => (int) $artista->getKey(),

            'nome' => $artista->nome,

            'rotulo_selecao' => $artista->obterRotuloSelecao(),

            'origem_geografica_id' => $origemGeografica instanceof OrigemGeografica
                ? (int) $origemGeografica->getKey()
                : null,

            'origem_geografica' => $origemGeografica instanceof OrigemGeografica
                ? [
                    'id' => (int) $origemGeografica->getKey(),

                    'nome' => $origemGeografica->nome,
                ]
                : null,

            'ano_inicio_atividade' => $artista->ano_inicio_atividade,

            'ano_fim_atividade' => $artista->ano_fim_atividade,

            'estado_atividade' => $estado instanceof EstadoAtividadeArtista
                ? $estado->value
                : null,

            'estado_atividade_etiqueta' => $estado instanceof EstadoAtividadeArtista
                ? $estado->etiqueta()
                : null,

            'biografia' => $artista->biografia,

            'imagem' => $artista->imagem,

            'url_imagem' => $artista->url_imagem,

            'musicbrainz_id' => $artista->musicbrainz_id,

            'url_musicbrainz' => $artista->url_musicbrainz,

            'discogs_id' => $artista->discogs_id,

            'url_discogs' => $artista->url_discogs,

            'ligacoes' => $ligacoes,

            'generos' => $generos,
        ];
    }

    /**
     * Normaliza um identificador MusicBrainz reconstruído no formulário.
     *
     * Identificadores ausentes ou com formato UUID inválido são representados por
     * uma sequência vazia para não serem reapresentados como associações válidas.
     *
     * @param  mixed  $valor  Valor recebido do modelo ou dos dados anteriores.
     * @return string Identificador MusicBrainz normalizado ou sequência vazia.
     *
     * @since 2.0.0
     */
    private function normalizarMbidFormulario(
        mixed $valor,
    ): string {
        if (! is_string($valor)) {
            return '';
        }

        $identificador =
            mb_strtolower(
                trim(
                    $valor,
                ),
            );

        if (
            preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
                $identificador,
            ) !== 1
        ) {
            return '';
        }

        return $identificador;
    }

    /**
     * Normaliza o termo utilizado na pesquisa de artistas.
     *
     * Sequências vazias são convertidas para nulo e o comprimento máximo é
     * aplicado antes de o valor ser utilizado na consulta.
     *
     * @param  mixed  $valor  Valor recebido na query string.
     * @return string|null Termo normalizado ou nulo.
     *
     * @since 2.0.0
     */
    private function normalizarPesquisa(mixed $valor): ?string
    {
        if (! is_string($valor)) {
            return null;
        }

        $pesquisa = trim($valor);

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
     * Normaliza um valor textual curto reconstruído no formulário.
     *
     * @param  mixed  $valor  Valor recebido do modelo ou da sessão.
     * @return string Texto normalizado ou sequência vazia.
     *
     * @since 2.0.0
     */
    private function normalizarTextoFormulario(mixed $valor): string
    {
        if (
            ! is_string($valor)
            && ! is_int($valor)
            && ! is_float($valor)
        ) {
            return '';
        }

        return trim((string) $valor);
    }

    /**
     * Normaliza texto longo sem alterar a respetiva estrutura interior.
     *
     * @param  mixed  $valor  Valor recebido do modelo ou da sessão.
     * @return string Texto normalizado ou sequência vazia.
     *
     * @since 2.0.0
     */
    private function normalizarTextoFormularioSemCompactar(mixed $valor): string
    {
        return is_string($valor)
            ? trim($valor)
            : '';
    }

    /**
     * Normaliza um identificador numérico reconstruído no formulário.
     *
     * Valores inválidos ou não positivos são representados por uma sequência
     * vazia.
     *
     * @param  mixed  $valor  Valor recebido do modelo ou da sessão.
     * @return string Identificador normalizado ou sequência vazia.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificadorFormulario(mixed $valor): string
    {
        if (is_int($valor)) {
            return $valor > 0
                ? (string) $valor
                : '';
        }

        if (! is_string($valor)) {
            return '';
        }

        $identificador = trim($valor);

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
     * Normaliza uma lista de identificadores reconstruída no formulário.
     *
     * Identificadores inválidos são ignorados e valores repetidos são
     * removidos, preservando a ordem da primeira ocorrência válida.
     *
     * @param  mixed  $valores  Valores recebidos do modelo ou da sessão.
     * @return list<string> Identificadores normalizados.
     *
     * @since 2.0.0
     */
    private function normalizarListaIdentificadoresFormulario(mixed $valores): array
    {
        if (! is_array($valores)) {
            return [];
        }

        $identificadores = [];

        foreach ($valores as $valor) {
            $identificador = $this->normalizarIdentificadorFormulario(
                $valor,
            );

            if ($identificador !== '') {
                $identificadores[$identificador] = $identificador;
            }
        }

        return array_values(
            $identificadores,
        );
    }

    /**
     * Normaliza as ligações reconstruídas no formulário.
     *
     * É sempre devolvida pelo menos uma linha vazia para que a interface possa
     * apresentar imediatamente um conjunto de campos editável.
     *
     * @param  mixed  $valores  Ligações recebidas do modelo ou da sessão.
     * @return non-empty-list<array{titulo: string, url: string}> Ligações.
     *
     * @since 2.0.0
     */
    private function normalizarLigacoesFormulario(mixed $valores): array
    {
        $ligacoes = [];

        if (is_array($valores)) {
            foreach ($valores as $valor) {
                if (! is_array($valor)) {
                    continue;
                }

                $ligacoes[] = [
                    'titulo' => $this->normalizarTextoFormulario(
                        $valor['titulo'] ?? '',
                    ),
                    'url' => $this->normalizarTextoFormulario(
                        $valor['url'] ?? '',
                    ),
                ];
            }
        }

        if ($ligacoes === []) {
            $ligacoes[] = [
                'titulo' => '',
                'url' => '',
            ];
        }

        return $ligacoes;
    }
}
