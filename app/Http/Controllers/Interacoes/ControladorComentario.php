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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Gere a publicação, resposta, atualização e eliminação de comentários.
 *
 * Os comentários podem estar associados a uma MetalThursday ou a uma das
 * respetivas secções através de uma relação polimórfica.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class ControladorComentario extends Controller
{
    use AuthorizesRequests;

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
     * @param  GuardarComentarioRequest  $pedido  Pedido validado.
     * @param  string  $tipoComentavel  Tipo da entidade comentada.
     * @param  int  $identificadorComentavel  Identificador da entidade comentada.
     * @return JsonResponse Comentário criado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function guardar(
        GuardarComentarioRequest $pedido,
        string $tipoComentavel,
        int $identificadorComentavel,
    ): JsonResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado(
                $pedido,
            );

        $comentavel =
            $this->resolverComentavel(
                $tipoComentavel,
                $identificadorComentavel,
            );

        $this->authorize(
            'create',
            Comentario::class,
        );

        $comentario = DB::transaction(
            static fn (): Comentario => $comentavel
                ->comentarios()
                ->create([
                    'utilizador_id' => $utilizador->getKey(),

                    'conteudo' => $pedido->obterConteudo(),

                    'comentario_pai_id' => null,
                ]),
        );

        $this->carregarComentario(
            $comentario,
        );

        $this->notificadorInteracoes
            ->notificarOutrosUtilizadores(
                $comentavel,
                'comentou',
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
     * @param  GuardarComentarioRequest  $pedido  Pedido validado.
     * @param  Comentario  $comentario  Comentário respondido.
     * @return JsonResponse Resposta criada.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function responder(
        GuardarComentarioRequest $pedido,
        Comentario $comentario,
    ): JsonResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado(
                $pedido,
            );

        $this->authorize(
            'create',
            Comentario::class,
        );

        $resultado = DB::transaction(
            function () use (
                $comentario,
                $pedido,
                $utilizador,
            ): array {
                $comentarioBloqueado = Comentario::query()
                    ->whereKey(
                        $comentario->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $comentarioPai =
                    $this->obterComentarioPrincipal(
                        $comentarioBloqueado,
                    );

                $comentavel =
                    $this->obterComentavelDoComentario(
                        $comentarioPai,
                    );

                $resposta = $comentavel
                    ->comentarios()
                    ->create([
                        'utilizador_id' => $utilizador->getKey(),

                        'conteudo' => $pedido->obterConteudo(),

                        'comentario_pai_id' => $comentarioPai->getKey(),
                    ]);

                return [
                    'comentario' => $resposta,

                    'comentavel' => $comentavel,
                ];
            },
        );

        /** @var Comentario $resposta */
        $resposta =
            $resultado['comentario'];

        /** @var MetalThursday|SeccaoMetalThursday $comentavel */
        $comentavel =
            $resultado['comentavel'];

        $this->carregarComentario(
            $resposta,
        );

        $this
            ->notificadorInteracoes
            ->notificarOutrosUtilizadores(
                $comentavel,
                'respondeu',
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
     * @param  AtualizarComentarioRequest  $pedido  Pedido validado.
     * @param  Comentario  $comentario  Comentário atualizado.
     * @return JsonResponse Comentário atualizado.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function atualizar(
        AtualizarComentarioRequest $pedido,
        Comentario $comentario,
    ): JsonResponse {
        $this->authorize(
            'update',
            $comentario,
        );

        $comentario->updateOrFail([
            'conteudo' => $pedido->obterConteudo(),
        ]);

        $comentario->refresh();

        $this->carregarComentario(
            $comentario,
        );

        return response()->json([
            'mensagem' => 'Comentário atualizado com sucesso.',

            'comentario' => $this->serializarComentario(
                $comentario,
            ),
        ]);
    }

    /**
     * Elimina logicamente um comentário.
     *
     * A eliminação lógica preserva a estrutura da conversa quando existem
     * respostas associadas.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Comentario  $comentario  Comentário eliminado.
     * @return JsonResponse Resposta sem conteúdo.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function eliminar(
        Request $pedido,
        Comentario $comentario,
    ): JsonResponse {
        $this->obterUtilizadorAutenticado(
            $pedido,
        );

        $this->authorize(
            'delete',
            $comentario,
        );

        $comentario->deleteOrFail();

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
     * @throws NotFoundHttpException Quando o tipo ou identificador não são
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
     * Obtém o comentário principal de uma conversa.
     *
     * @param  Comentario  $comentario  Comentário recebido.
     * @return Comentario Comentário principal.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function obterComentarioPrincipal(
        Comentario $comentario,
    ): Comentario {
        $identificadorPai =
            $comentario->comentario_pai_id;

        if (
            ! is_numeric($identificadorPai)
            || (int) $identificadorPai < 1
        ) {
            return $comentario;
        }

        return Comentario::query()
            ->whereKey(
                (int) $identificadorPai,
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
     * @throws NotFoundHttpException Quando a entidade não é suportada.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
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
     * Carrega as relações necessárias para a resposta.
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

            'comentario_pai_id' => is_numeric(
                $comentario->comentario_pai_id,
            )
                ? (int) $comentario->comentario_pai_id
                : null,

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
     * Obtém o utilizador autenticado.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return Utilizador Utilizador autenticado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterUtilizadorAutenticado(
        Request $pedido,
    ): Utilizador {
        $utilizador =
            $pedido->user();

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
