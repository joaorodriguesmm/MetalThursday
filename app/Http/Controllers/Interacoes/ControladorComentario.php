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
 *
 * @version 3.1.0
 */
final class ControladorComentario extends Controller
{
    use AuthorizesRequests;

    /**
     * Número máximo de tentativas de uma transação em caso de bloqueio mútuo.
     *
     * @var int
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO =
        3;

    /**
     * Ação utilizada ao notificar a publicação de um comentário.
     *
     * @var string
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private const ACAO_COMENTOU =
        'comentou';

    /**
     * Ação utilizada ao notificar a publicação de uma resposta.
     *
     * @var string
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private const ACAO_RESPONDEU =
        'respondeu';

    /**
     * Cria o controlador.
     *
     * @param  NotificadorInteracoes  $notificadorInteracoes  Serviço responsável
     *                                                        pelas notificações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        private readonly NotificadorInteracoes $notificadorInteracoes,
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
     *
     * @version 3.0.0
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
            ],
            Response::HTTP_CREATED,
        );
    }

    /**
     * Publica uma resposta a um comentário.
     *
     * As respostas a outras respostas são associadas ao comentário principal,
     * mantendo apenas dois níveis de apresentação.
     *
     * O comentário principal é utilizado como sujeito da notificação,
     * permitindo identificar corretamente o autor da conversa respondida.
     *
     * @param  GuardarComentarioRequest  $pedido  Pedido validado.
     * @param  Comentario  $comentario  Comentário respondido.
     * @return JsonResponse Resposta criada.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     * @throws NotFoundHttpException Quando a conversa ou a entidade comentada
     *                               já não estão disponíveis.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
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
         *     comentario_principal: Comentario
         * } $resultado
         */
        $resultado =
            DB::transaction(
                function () use (
                    $comentario,
                    $identificadorUtilizador,
                    $conteudo,
                ): array {
                    $comentarioBloqueado =
                        Comentario::query()
                            ->whereKey(
                                $comentario->getKey(),
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $comentarioPrincipal =
                        $this->obterComentarioPrincipal(
                            $comentarioBloqueado,
                        );

                    $comentavel =
                        $this->obterComentavelDoComentario(
                            $comentarioPrincipal,
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

                                'comentario_pai_id' => (int) $comentarioPrincipal->getKey(),
                            ]);

                    return [
                        'resposta' => $resposta,

                        'comentario_principal' => $comentarioPrincipal,
                    ];
                },
                self::TENTATIVAS_TRANSACAO,
            );

        $resposta =
            $resultado['resposta'];

        $comentarioPrincipal =
            $resultado['comentario_principal'];

        $this->carregarComentario(
            $resposta,
        );

        $this
            ->notificadorInteracoes
            ->notificarOutrosUtilizadores(
                sujeito: $comentarioPrincipal,
                causador: $utilizador,
                acao: self::ACAO_RESPONDEU,
            );

        return response()->json(
            [
                'mensagem' => 'Resposta publicada com sucesso.',

                'comentario' => $this->serializarComentario(
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
     * @param  AtualizarComentarioRequest  $pedido  Pedido validado.
     * @param  Comentario  $comentario  Comentário atualizado.
     * @return JsonResponse Comentário atualizado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
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

                    $this->authorize(
                        'update',
                        $comentarioBloqueado,
                    );

                    $comentarioBloqueado->updateOrFail([
                        'conteudo' => $conteudo,
                    ]);

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
     * Elimina logicamente um comentário.
     *
     * A eliminação lógica preserva a estrutura da conversa quando existem
     * respostas associadas.
     *
     * A autorização é verificada antes de abrir a transação, evitando obter
     * um bloqueio exclusivo para pedidos que serão rejeitados. Depois da
     * autorização, o comentário é novamente obtido e bloqueado imediatamente
     * antes da eliminação.
     *
     * @param  Comentario  $comentario  Comentário eliminado.
     * @return JsonResponse Resposta sem conteúdo.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    public function eliminar(
        Comentario $comentario,
    ): JsonResponse {
        $this->obterUtilizadorAutenticado();

        $this->authorize(
            'delete',
            $comentario,
        );

        DB::transaction(
            function () use ($comentario): void {
                $comentarioBloqueado =
                    Comentario::query()
                        ->whereKey(
                            $comentario->getKey(),
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $comentarioBloqueado->deleteOrFail();
            },
            self::TENTATIVAS_TRANSACAO,
        );

        return response()->json(
            null,
            Response::HTTP_NO_CONTENT,
        );
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
     *
     * @version 2.0.0
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
     * @since 3.0.0
     *
     * @version 1.0.0
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
     * Obtém o comentário principal de uma conversa.
     *
     * Quando o comentário recebido já é principal, o próprio modelo
     * bloqueado é devolvido.
     *
     * @param  Comentario  $comentario  Comentário recebido.
     * @return Comentario Comentário principal bloqueado.
     *
     * @since 2.1.0
     *
     * @version 2.0.0
     */
    private function obterComentarioPrincipal(
        Comentario $comentario,
    ): Comentario {
        $identificadorPai =
            $comentario->comentario_pai_id;

        if ($identificadorPai === null) {
            return $comentario;
        }

        return Comentario::query()
            ->whereKey(
                $identificadorPai,
            )
            ->where(
                'tipo_comentavel',
                $comentario->tipo_comentavel,
            )
            ->where(
                'comentavel_id',
                $comentario->comentavel_id,
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
     * @since 2.1.0
     *
     * @version 2.0.0
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
     * Carrega as relações necessárias para a resposta HTTP.
     *
     * @param  Comentario  $comentario  Comentário carregado.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function carregarComentario(
        Comentario $comentario,
    ): void {
        $comentario->load([
            'utilizador:id,nome,fotografia',
        ]);

        $comentario->loadCount([
            'gostos',
            'respostas',
        ]);
    }

    /**
     * Converte um comentário para o formato da resposta HTTP.
     *
     * @param  Comentario  $comentario  Comentário convertido.
     * @return array{
     *     id: int,
     *     conteudo: string,
     *     comentario_pai_id: int|null,
     *     numero_gostos: int,
     *     numero_respostas: int,
     *     criado_em: string|null,
     *     atualizado_em: string|null,
     *     utilizador: array{
     *         id: int,
     *         nome: string,
     *         fotografia: string|null
     *     }|null
     * } Dados do comentário.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function serializarComentario(
        Comentario $comentario,
    ): array {
        $utilizador =
            $comentario->utilizador;

        return [
            'id' => (int) $comentario->getKey(),

            'conteudo' => (string) $comentario->conteudo,

            'comentario_pai_id' => $comentario->comentario_pai_id,

            'numero_gostos' => (int) (
                $comentario->gostos_count
                ?? 0
            ),

            'numero_respostas' => (int) (
                $comentario->respostas_count
                ?? 0
            ),

            'criado_em' => $comentario
                ->created_at
                ?->toIso8601String(),

            'atualizado_em' => $comentario
                ->updated_at
                ?->toIso8601String(),

            'utilizador' => $utilizador instanceof Utilizador
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
     *
     * @version 2.0.0
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
