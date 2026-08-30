<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Http\Controllers\Controller;
use App\Http\Requests\Interacoes\AtualizarComentarioRequest;
use App\Http\Requests\Interacoes\GuardarComentarioRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Servicos\Interacoes\ServicoDisponibilidadeInteracoes;
use App\Servicos\Notificacoes\NotificadorInteracoes;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Gere a publicação, resposta, atualização e eliminação de comentários.
 *
 * Os comentários podem estar associados a uma MetalThursday ou a uma das
 * respetivas secções através de uma relação polimórfica.
 *
 * As operações de escrita bloqueiam os registos envolvidos, impedindo que
 * pedidos concorrentes alterem a mesma conversa com base num estado
 * desatualizado.
 *
 * @since 1.0.0
 */
final class ControladorComentario extends Controller
{
    use AuthorizesRequests;

    /**
     * Número máximo de tentativas de uma transação em caso de bloqueio mútuo.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO =
        3;

    /**
     * Ação utilizada ao notificar a publicação de um comentário.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const ACAO_COMENTOU =
        'comentou';

    /**
     * Ação utilizada ao notificar a publicação de uma resposta.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const ACAO_RESPONDEU =
        'respondeu';

    /**
     * Cria o controlador.
     *
     * @param  NotificadorInteracoes  $notificadorInteracoes  Serviço responsável
     *                                                        pelas notificações.
     * @param  ServicoDisponibilidadeInteracoes  $servicoDisponibilidadeInteracoes  Serviço responsável
     *                                                                              pela disponibilidade
     *                                                                              temporal.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly NotificadorInteracoes $notificadorInteracoes,
        private readonly ServicoDisponibilidadeInteracoes $servicoDisponibilidadeInteracoes,
    ) {}

    /**
     * Publica um comentário numa MetalThursday ou numa secção.
     *
     * A entidade comentada é bloqueada durante a criação, impedindo que seja
     * eliminada ou alterada concorrentemente enquanto o comentário é
     * associado.
     *
     * @param  GuardarComentarioRequest  $pedido  Pedido validado.
     * @param  string  $tipoComentavel  Tipo da entidade comentada.
     * @param  int  $identificadorComentavel  Identificador da entidade.
     * @return JsonResponse Comentário criado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     * @throws NotFoundHttpException Quando o tipo ou o identificador não são
     *                               válidos.
     *
     * @since 1.0.0
     */
    public function guardar(
        GuardarComentarioRequest $pedido,
        string $tipoComentavel,
        int $identificadorComentavel,
    ): JsonResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        $identificadorUtilizador =
            (int) $utilizador->getKey();

        $conteudo =
            $pedido->obterConteudo();

        $comentavel =
            $this->resolverComentavel(
                $tipoComentavel,
                $identificadorComentavel,
            );

        $this->authorize(
            'create',
            Comentario::class,
        );

        /**
         * @var array{
         *     comentario: Comentario,
         *     comentavel: MetalThursday|SeccaoMetalThursday
         * } $resultado
         */
        $resultado =
            DB::transaction(
                function () use (
                    $comentavel,
                    $identificadorUtilizador,
                    $conteudo,
                ): array {
                    $this->servicoDisponibilidadeInteracoes
                        ->obterMetalThursdayPublicadaComBloqueio(
                            $comentavel,
                        );

                    $comentavelBloqueado =
                        $this->bloquearComentavel(
                            $comentavel,
                        );

                    $comentario =
                        $comentavelBloqueado
                            ->comentarios()
                            ->create([
                                'utilizador_id' => $identificadorUtilizador,

                                'conteudo' => $conteudo,

                                'comentario_pai_id' => null,
                            ]);

                    return [
                        'comentario' => $comentario,

                        'comentavel' => $comentavelBloqueado,
                    ];
                },
                self::TENTATIVAS_TRANSACAO,
            );

        $comentario =
            $resultado['comentario'];

        $comentavelAtualizado =
            $resultado['comentavel'];

        $this->carregarComentario(
            $comentario,
        );

        $this
            ->notificadorInteracoes
            ->notificarOutrosUtilizadores(
                sujeito: $comentavelAtualizado,
                causador: $utilizador,
                acao: self::ACAO_COMENTOU,
            );

        return response()->json(
            [
                'mensagem' => 'Comentário publicado com sucesso.',

                'comentario' => $this->serializarComentario(
                    $comentario,
                ),

                'comentario_html' => $this->renderizarComentario(
                    $comentario,
                ),
            ],
            Response::HTTP_CREATED,
        );
    }

    /**
     * Lista as respostas diretas de um comentário.
     *
     * Apenas os filhos diretos são devolvidos. Cada filho inclui a quantidade das
     * próprias respostas, permitindo à interface apresentar um novo controlo de
     * expansão sem carregar antecipadamente o nível seguinte.
     *
     * @param  Comentario  $comentario  Comentário cujas respostas são consultadas.
     * @return JsonResponse Respostas diretas ordenadas cronologicamente.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     */
    public function listarRespostas(
        Comentario $comentario,
    ): JsonResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        $this->authorize(
            'view',
            $comentario,
        );

        /*
        * Confirma que a entidade à qual a conversa pertence continua disponível.
        * Um comentário pode permanecer fisicamente na base de dados depois da
        * eliminação lógica da MetalThursday ou da secção, mas nesse caso não deve
        * funcionar como ponto de acesso autónomo ao conteúdo da conversa.
        */
        $comentavel =
            $this->obterComentavelDoComentario(
                $comentario,
            );

        $this->servicoDisponibilidadeInteracoes
            ->obterMetalThursdayPublicada(
                $comentavel,
            );

        $identificadorUtilizador =
            (int) $utilizador->getKey();

        $respostas =
            $comentario
                ->respostas()
                ->comDadosApresentacao(
                    $identificadorUtilizador,
                )
                ->ordenadosCronologicamente()
                ->get();

        $respostasSerializadas =
            $respostas
                ->map(
                    fn (
                        Comentario $resposta,
                    ): array => [
                        'comentario' => $this->serializarComentario(
                            $resposta,
                        ),

                        'comentario_html' => $this->renderizarComentario(
                            $resposta,
                        ),
                    ],
                )
                ->values()
                ->all();

        return response()->json([
            'comentario_id' => (int) $comentario->getKey(),

            'numero_respostas' => count(
                $respostasSerializadas,
            ),

            'respostas' => $respostasSerializadas,
        ]);
    }

    /**
     * Publica uma resposta a um comentário.
     *
     * A resposta fica diretamente associada ao comentário concretamente
     * respondido. Desta forma é preservada a árvore real da conversa,
     * independentemente da profundidade visual utilizada pela interface.
     *
     * A entidade comentada é bloqueada durante a criação para impedir alterações
     * concorrentes enquanto a nova resposta é associada.
     *
     * @param  GuardarComentarioRequest  $pedido  Pedido validado.
     * @param  Comentario  $comentario  Comentário respondido.
     * @return JsonResponse Resposta criada.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     * @throws NotFoundHttpException Quando o comentário ou a entidade comentada
     *                               já não estão disponíveis.
     *
     * @since 1.0.0
     */
    public function responder(
        GuardarComentarioRequest $pedido,
        Comentario $comentario,
    ): JsonResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        $identificadorUtilizador =
            (int) $utilizador->getKey();

        $conteudo =
            $pedido->obterConteudo();

        $this->authorize(
            'create',
            Comentario::class,
        );

        /**
         * @var array{
         *     resposta: Comentario,
         *     comentario_respondido: Comentario
         * } $resultado
         */
        $resultado =
            DB::transaction(
                function () use (
                    $comentario,
                    $identificadorUtilizador,
                    $conteudo,
                ): array {
                    $comentarioRespondido =
                        Comentario::query()
                            ->whereKey(
                                $comentario->getKey(),
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        $comentarioRespondido
                            ->temConteudoEliminado()
                    ) {
                        abort(
                            Response::HTTP_GONE,
                        );
                    }

                    $comentavel =
                        $this->obterComentavelDoComentario(
                            $comentarioRespondido,
                        );

                    $this->servicoDisponibilidadeInteracoes
                        ->obterMetalThursdayPublicadaComBloqueio(
                            $comentavel,
                        );

                    $comentavelBloqueado =
                        $this->bloquearComentavel(
                            $comentavel,
                        );

                    $resposta =
                        $comentavelBloqueado
                            ->comentarios()
                            ->create([
                                'utilizador_id' => $identificadorUtilizador,

                                'conteudo' => $conteudo,

                                'comentario_pai_id' => (int) $comentarioRespondido->getKey(),
                            ]);

                    return [
                        'resposta' => $resposta,

                        'comentario_respondido' => $comentarioRespondido,
                    ];
                },
                self::TENTATIVAS_TRANSACAO,
            );

        $resposta =
            $resultado['resposta'];

        $comentarioRespondido =
            $resultado['comentario_respondido'];

        $this->carregarComentario(
            $resposta,
        );

        $this
            ->notificadorInteracoes
            ->notificarOutrosUtilizadores(
                sujeito: $comentarioRespondido,
                causador: $utilizador,
                acao: self::ACAO_RESPONDEU,
            );

        return response()->json(
            [
                'mensagem' => 'Resposta publicada com sucesso.',

                'comentario' => $this->serializarComentario(
                    $resposta,
                ),

                'comentario_html' => $this->renderizarComentario(
                    $resposta,
                ),
            ],
            Response::HTTP_CREATED,
        );
    }

    /**
     * Atualiza o conteúdo de um comentário.
     *
     * O comentário é novamente obtido e bloqueado dentro da transação. A
     * autorização é aplicada ao modelo bloqueado, garantindo que a decisão
     * utiliza o estado persistido atual.
     *
     * Quando o conteúdo normalizado não sofreu alterações, não é executada
     * qualquer escrita nem atualizado o respetivo timestamp.
     *
     * @param  AtualizarComentarioRequest  $pedido  Pedido validado.
     * @param  Comentario  $comentario  Comentário atualizado.
     * @return JsonResponse Comentário atualizado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     */
    public function atualizar(
        AtualizarComentarioRequest $pedido,
        Comentario $comentario,
    ): JsonResponse {
        $this->obterUtilizadorAutenticado();

        $conteudo =
            $pedido->obterConteudo();

        $comentarioAtualizado =
            DB::transaction(
                function () use (
                    $comentario,
                    $conteudo,
                ): Comentario {
                    $comentarioBloqueado =
                        Comentario::query()
                            ->whereKey(
                                $comentario->getKey(),
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        $comentarioBloqueado
                            ->temConteudoEliminado()
                    ) {
                        abort(
                            Response::HTTP_GONE,
                        );
                    }

                    $this->servicoDisponibilidadeInteracoes
                        ->obterMetalThursdayPublicadaComBloqueio(
                            $comentarioBloqueado,
                        );

                    $this->authorize(
                        'update',
                        $comentarioBloqueado,
                    );

                    if (
                        $comentarioBloqueado->conteudo
                        !== $conteudo
                    ) {
                        $comentarioBloqueado->conteudo =
                            $conteudo;

                        $comentarioBloqueado->editado_em =
                            now();

                        $comentarioBloqueado
                            ->saveOrFail();
                    }

                    return $comentarioBloqueado;
                },
                self::TENTATIVAS_TRANSACAO,
            );

        $this->carregarComentario(
            $comentarioAtualizado,
        );

        return response()->json([
            'mensagem' => 'Comentário atualizado com sucesso.',

            'comentario' => $this->serializarComentario(
                $comentarioAtualizado,
            ),
        ]);
    }

    /**
     * Elimina um comentário.
     *
     * Um comentário sem respostas é eliminado logicamente. Quando possui
     * respostas, mantém-se como marcador estrutural e apenas o respetivo conteúdo
     * é considerado eliminado.
     *
     * Depois da remoção efetiva de uma folha, tombstones ancestrais que tenham
     * ficado sem respostas são também removidos automaticamente.
     *
     * @param  Comentario  $comentario  Comentário eliminado.
     * @return JsonResponse Resultado da eliminação.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     */
    public function eliminar(
        Comentario $comentario,
    ): JsonResponse {
        $this->obterUtilizadorAutenticado();

        $this->authorize(
            'delete',
            $comentario,
        );

        /**
         * @var array{
         *     modo: 'marcador'|'remover',
         *     comentario: Comentario|null,
         *     comentario_pai_id: int|null,
         *     comentarios_removidos_ids: list<int>,
         *     pai_atualizado: array{
         *         id: int,
         *         numero_respostas: int
         *     }|null
         * } $resultado
         */
        $resultado =
            DB::transaction(
                function () use (
                    $comentario,
                ): array {
                    $comentarioBloqueado =
                        Comentario::query()
                            ->whereKey(
                                $comentario->getKey(),
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        $comentarioBloqueado
                            ->temConteudoEliminado()
                    ) {
                        abort(
                            Response::HTTP_GONE,
                        );
                    }

                    $this->servicoDisponibilidadeInteracoes
                        ->obterMetalThursdayPublicadaComBloqueio(
                            $comentarioBloqueado,
                        );

                    if (
                        $comentarioBloqueado
                            ->respostas()
                            ->exists()
                    ) {
                        $comentarioBloqueado
                            ->conteudo_eliminado_em =
                            now();

                        $comentarioBloqueado
                            ->saveOrFail();

                        return [
                            'modo' => 'marcador',

                            'comentario' => $comentarioBloqueado,

                            'comentario_pai_id' => $comentarioBloqueado
                                ->comentario_pai_id,

                            'comentarios_removidos_ids' => [],

                            'pai_atualizado' => null,
                        ];
                    }

                    $identificadorComentario =
                        (int) $comentarioBloqueado
                            ->getKey();

                    $identificadorPai =
                        $comentarioBloqueado
                            ->comentario_pai_id;

                    $comentarioBloqueado
                        ->deleteOrFail();

                    $limpeza =
                        $this
                            ->limparMarcadoresSemRespostas(
                                $identificadorPai,
                            );

                    return [
                        'modo' => 'remover',

                        'comentario' => null,

                        'comentario_pai_id' => $identificadorPai,

                        'comentarios_removidos_ids' => [
                            $identificadorComentario,
                            ...$limpeza['comentarios_removidos_ids'],
                        ],

                        'pai_atualizado' => $limpeza['pai_atualizado'],
                    ];
                },
                self::TENTATIVAS_TRANSACAO,
            );

        $comentarioMantido =
            $resultado['comentario'];

        if (
            $comentarioMantido
            instanceof Comentario
        ) {
            $this->carregarComentario(
                $comentarioMantido,
            );

            return response()->json([
                'mensagem' => 'Comentário eliminado com sucesso.',

                'modo_eliminacao' => 'marcador',

                'numero_conteudos_removidos' => 1,

                'comentarios_removidos_ids' => [],

                'pai_atualizado' => null,

                'comentario' => $this->serializarComentario(
                    $comentarioMantido,
                ),

                'comentario_html' => $this->renderizarComentario(
                    $comentarioMantido,
                ),
            ]);
        }

        return response()->json([
            'mensagem' => 'Comentário eliminado com sucesso.',

            'modo_eliminacao' => 'remover',

            'numero_conteudos_removidos' => 1,

            'comentario_id' => (int) $comentario->getKey(),

            'comentario_pai_id' => $resultado['comentario_pai_id'],

            'comentarios_removidos_ids' => $resultado['comentarios_removidos_ids'],

            'pai_atualizado' => $resultado['pai_atualizado'],
        ]);
    }

    /**
     * Resolve a entidade que recebe o comentário.
     *
     * @param  string  $tipo  Slug recebido através da rota.
     * @param  int  $identificador  Identificador da entidade.
     * @return MetalThursday|SeccaoMetalThursday Entidade encontrada.
     *
     * @throws NotFoundHttpException Quando o tipo ou o identificador não são
     *                               válidos.
     *
     * @since 2.0.0
     */
    private function resolverComentavel(
        string $tipo,
        int $identificador,
    ): MetalThursday|SeccaoMetalThursday {
        if ($identificador < 1) {
            throw new NotFoundHttpException;
        }

        $tipoEntidade =
            TipoEntidadeInteracao::deSlug(
                $tipo,
            );

        if ($tipoEntidade === null) {
            throw new NotFoundHttpException;
        }

        $classeModelo =
            $tipoEntidade->obterClasseModelo();

        return $classeModelo::query()
            ->findOrFail(
                $identificador,
            );
    }

    /**
     * Bloqueia a entidade comentada durante uma operação de escrita.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $comentavel  Entidade
     *                                                         comentada.
     * @return MetalThursday|SeccaoMetalThursday Entidade bloqueada.
     *
     * @since 2.0.0
     */
    private function bloquearComentavel(
        MetalThursday|SeccaoMetalThursday $comentavel,
    ): MetalThursday|SeccaoMetalThursday {
        if ($comentavel instanceof MetalThursday) {
            return MetalThursday::query()
                ->whereKey(
                    $comentavel->getKey(),
                )
                ->lockForUpdate()
                ->firstOrFail();
        }

        return SeccaoMetalThursday::query()
            ->whereKey(
                $comentavel->getKey(),
            )
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Obtém a entidade associada a um comentário.
     *
     * @param  Comentario  $comentario  Comentário consultado.
     * @return MetalThursday|SeccaoMetalThursday Entidade comentada.
     *
     * @throws NotFoundHttpException Quando a entidade não é suportada ou já
     *                               não está disponível.
     *
     * @since 2.0.0
     */
    private function obterComentavelDoComentario(
        Comentario $comentario,
    ): MetalThursday|SeccaoMetalThursday {
        $comentavel =
            $comentario
                ->comentavel()
                ->first();

        if (
            ! $comentavel instanceof MetalThursday
            && ! $comentavel instanceof SeccaoMetalThursday
        ) {
            throw new NotFoundHttpException;
        }

        return $comentavel;
    }

    /**
     * Carrega os dados necessários para a resposta HTTP e para a renderização
     * assíncrona do comentário.
     *
     * As respostas não são carregadas; apenas a respetiva quantidade é obtida.
     *
     * @param  Comentario  $comentario  Comentário carregado.
     *
     * @since 2.0.0
     */
    private function carregarComentario(
        Comentario $comentario,
    ): void {
        $comentario->load([
            'utilizador:id,nome,fotografia',
        ]);

        $comentario->loadCount([
            'gostos as quantidade_gostos',
            'respostas as quantidade_respostas',
        ]);
    }

    /**
     * Remove tombstones ancestrais que deixaram de possuir respostas.
     *
     * A limpeza propaga-se para cima até encontrar um comentário com conteúdo,
     * um tombstone que ainda possua descendentes ou a raiz da conversa.
     *
     * O primeiro pai que permanece ativo é devolvido com a quantidade atualizada
     * das respetivas respostas, permitindo sincronizar a interface sem novo
     * pedido HTTP.
     *
     * @param  int|null  $identificadorPai  Primeiro pai verificado.
     * @return array{
     *     comentarios_removidos_ids: list<int>,
     *     pai_atualizado: array{
     *         id: int,
     *         numero_respostas: int
     *     }|null
     * } Resultado da limpeza.
     *
     * @since 2.0.0
     */
    private function limparMarcadoresSemRespostas(
        ?int $identificadorPai,
    ): array {
        $identificadoresRemovidos =
            [];

        $identificadorAtual =
            $identificadorPai;

        while ($identificadorAtual !== null) {
            $comentarioPai =
                Comentario::query()
                    ->whereKey(
                        $identificadorAtual,
                    )
                    ->lockForUpdate()
                    ->first();

            if (! $comentarioPai instanceof Comentario) {
                return [
                    'comentarios_removidos_ids' => $identificadoresRemovidos,

                    'pai_atualizado' => null,
                ];
            }

            $numeroRespostas =
                $comentarioPai
                    ->respostas()
                    ->count();

            if (
                ! $comentarioPai
                    ->temConteudoEliminado()
                || $numeroRespostas > 0
            ) {
                return [
                    'comentarios_removidos_ids' => $identificadoresRemovidos,

                    'pai_atualizado' => [
                        'id' => (int) $comentarioPai
                            ->getKey(),

                        'numero_respostas' => $numeroRespostas,
                    ],
                ];
            }

            $proximoIdentificadorPai =
                $comentarioPai
                    ->comentario_pai_id;

            $identificadoresRemovidos[] =
                (int) $comentarioPai
                    ->getKey();

            $comentarioPai
                ->deleteOrFail();

            $identificadorAtual =
                $proximoIdentificadorPai;
        }

        return [
            'comentarios_removidos_ids' => $identificadoresRemovidos,

            'pai_atualizado' => null,
        ];
    }

    /**
     * Renderiza um comentário para inserção assíncrona na interface.
     *
     * @param  Comentario  $comentario  Comentário apresentado.
     * @return string HTML renderizado.
     *
     * @since 2.0.0
     */
    private function renderizarComentario(
        Comentario $comentario,
    ): string {
        return view(
            'components.fragmento-comentario',
            [
                'comentario' => $comentario,
            ],
        )->render();
    }

    /**
     * Converte um comentário para o formato da resposta HTTP.
     *
     * O conteúdo original nunca é exposto quando o comentário se encontra no
     * estado de conteúdo eliminado.
     *
     * @param  Comentario  $comentario  Comentário convertido.
     * @return array{
     *     id: int,
     *     conteudo: string,
     *     conteudo_eliminado: bool,
     *     comentario_pai_id: int|null,
     *     numero_gostos: int,
     *     numero_respostas: int,
     *     criado_em: string|null,
     *     atualizado_em: string|null,
     *     utilizador: array{
     *         id: int,
     *         nome: string,
     *         fotografia: string|null
     *     }|null,
     *     editado_em: string|null,
     * } Dados do comentário.
     *
     * @since 2.0.0
     */
    private function serializarComentario(
        Comentario $comentario,
    ): array {
        $utilizador =
            $comentario->utilizador;

        $conteudoEliminado =
            $comentario
                ->temConteudoEliminado();

        return [
            'id' => (int) $comentario->getKey(),

            'conteudo' => $conteudoEliminado
                ? 'Comentário eliminado'
                : (string) $comentario->conteudo,

            'conteudo_eliminado' => $conteudoEliminado,

            'comentario_pai_id' => $comentario->comentario_pai_id,

            'numero_gostos' => (int) (
                $comentario->quantidade_gostos
                ?? 0
            ),

            'numero_respostas' => (int) (
                $comentario->quantidade_respostas
                ?? 0
            ),

            'criado_em' => $comentario
                ->created_at
                ?->toIso8601String(),

            'atualizado_em' => $comentario
                ->updated_at
                ?->toIso8601String(),

            'editado_em' => $comentario
                ->editado_em
                ?->toIso8601String(),

            'utilizador' => ! $conteudoEliminado
                && $utilizador instanceof Utilizador
                ? [
                    'id' => (int) $utilizador->getKey(),

                    'nome' => $utilizador->nome,

                    'fotografia' => $utilizador->url_fotografia,
                ]
                : null,
        ];
    }

    /**
     * Obtém o utilizador autenticado através do guard da aplicação.
     *
     * @return Utilizador Utilizador autenticado e persistido.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     */
    private function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador =
            Auth::guard(
                'sessao',
            )->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para interagir com comentários.',
            );
        }

        $identificador =
            $utilizador->getKey();

        if (
            ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new AuthenticationException(
                'Não foi possível identificar o utilizador autenticado.',
            );
        }

        return $utilizador;
    }
}
