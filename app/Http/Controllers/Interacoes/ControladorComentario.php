<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interacoes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interactions\StoreCommentRequest;
use App\Http\Requests\Interactions\UpdateCommentRequest;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Servicos\Notificacoes\NotificadorInteracoes;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LogicException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Gere a criação, resposta, atualização e eliminação de comentários.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
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
     * Guarda um comentário numa MetalThursday ou numa secção.
     *
     * Os tipos antigos `metal_thursday` e `section` continuam a ser
     * reconhecidos até à revisão das rotas e do JavaScript.
     *
     * @param  StoreCommentRequest  $pedido  Pedido validado.
     * @param  string  $tipoComentavel  Tipo do recurso comentado.
     * @param  int  $identificadorComentavel  Identificador do recurso.
     * @return string HTML do comentário criado.
     *
     * @throws AuthenticationException Quando não existe utilizador autenticado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function guardar(
        StoreCommentRequest $pedido,
        string $tipoComentavel,
        int $identificadorComentavel,
    ): string {
        $comentavel = $this->resolverComentavel(
            $tipoComentavel,
            $identificadorComentavel,
        );

        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $pedido,
            );

        $conteudo = $this->obterConteudoValidado(
            $pedido->validated(),
        );

        $comentario = DB::transaction(
            static fn (): Comentario => $comentavel
                ->comentarios()
                ->create([
                    'utilizador_id' => $identificadorUtilizador,

                    'conteudo' => $conteudo,
                ]),
        );

        $comentario->loadMissing(
            'utilizador',
        );

        $this->notificadorInteracoes
            ->notificarOutrosUtilizadores(
                $comentavel,
                'comentou',
            );

        return view(
            'components.comment',
            [
                'comment' => $comentario,
            ],
        )->render();
    }

    /**
     * Guarda uma resposta a um comentário.
     *
     * A resposta é criada através da relação do recurso comentado, garantindo
     * que os campos polimórficos correspondem ao comentário original.
     *
     * @param  StoreCommentRequest  $pedido  Pedido validado.
     * @param  Comentario  $comentario  Comentário respondido.
     * @return string HTML da resposta criada.
     *
     * @throws AuthenticationException Quando não existe utilizador autenticado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function responder(
        StoreCommentRequest $pedido,
        Comentario $comentario,
    ): string {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $pedido,
            );

        $conteudo = $this->obterConteudoValidado(
            $pedido->validated(),
        );

        $comentavel = $comentario
            ->comentavel()
            ->firstOrFail();

        $resposta = DB::transaction(
            static fn (): Comentario => $comentavel
                ->comentarios()
                ->create([
                    'utilizador_id' => $identificadorUtilizador,

                    'conteudo' => $conteudo,

                    'comentario_pai_id' => $comentario->getKey(),
                ]),
        );

        $resposta->loadMissing(
            'utilizador',
        );

        $this->notificadorInteracoes
            ->notificarOutrosUtilizadores(
                $comentario,
                'respondeu a',
            );

        return view(
            'components.comment',
            [
                'comment' => $resposta,
            ],
        )->render();
    }

    /**
     * Atualiza o conteúdo de um comentário.
     *
     * @param  UpdateCommentRequest  $pedido  Pedido validado.
     * @param  Comentario  $comentario  Comentário atualizado.
     * @return JsonResponse Conteúdo atualizado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function atualizar(
        UpdateCommentRequest $pedido,
        Comentario $comentario,
    ): JsonResponse {
        $this->authorize(
            'update',
            $comentario,
        );

        $conteudo = $this->obterConteudoValidado(
            $pedido->validated(),
        );

        $comentario->updateOrFail([
            'conteudo' => $conteudo,
        ]);

        return response()->json([
            /*
             * Estas chaves permanecem temporariamente em inglês porque são
             * utilizadas pelo JavaScript atual.
             */
            'success' => true,
            'content' => $comentario->conteudo,
            'content_html' => nl2br(
                e($comentario->conteudo),
                false,
            ),
        ]);
    }

    /**
     * Elimina um comentário.
     *
     * @param  Comentario  $comentario  Comentário eliminado.
     * @return JsonResponse Resultado da operação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function eliminar(
        Comentario $comentario,
    ): JsonResponse {
        $this->authorize(
            'delete',
            $comentario,
        );

        $comentario->deleteOrFail();

        return response()->json([
            'message' => 'Comentário eliminado com sucesso.',
        ]);
    }

    /**
     * Resolve o recurso que recebe o comentário.
     *
     * @param  string  $tipo  Tipo recebido pela rota.
     * @param  int  $identificador  Identificador recebido pela rota.
     * @return MetalThursday|SeccaoMetalThursday Recurso encontrado.
     *
     * @throws NotFoundHttpException Quando o tipo não é reconhecido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function resolverComentavel(
        string $tipo,
        int $identificador,
    ): MetalThursday|SeccaoMetalThursday {
        if ($identificador < 1) {
            throw new NotFoundHttpException;
        }

        $tipoNormalizado = mb_strtolower(
            trim($tipo),
        );

        $classeModelo = match ($tipoNormalizado) {
            'metal_thursday',
            'metal-thursday',
            'metalthursday' => MetalThursday::class,

            'section',
            'seccao',
            'seccao_metal_thursday' => SeccaoMetalThursday::class,

            default => throw new NotFoundHttpException,
        };

        return $classeModelo::query()
            ->findOrFail(
                $identificador,
            );
    }

    /**
     * Obtém o conteúdo validado pelo pedido.
     *
     * O campo `content` permanece temporariamente suportado até à revisão dos
     * pedidos, formulários e JavaScript.
     *
     * @param  array<string, mixed>  $dados  Dados validados.
     * @return string Conteúdo normalizado.
     *
     * @throws LogicException Quando o pedido validado não contém o campo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterConteudoValidado(
        array $dados,
    ): string {
        $conteudo =
            $dados['conteudo']
            ?? $dados['content']
            ?? null;

        if (! is_string($conteudo)) {
            throw new LogicException(
                'O pedido validado não contém o conteúdo do comentário.',
            );
        }

        $conteudoNormalizado = trim(
            $conteudo,
        );

        if ($conteudoNormalizado === '') {
            throw new LogicException(
                'O conteúdo validado do comentário está vazio.',
            );
        }

        return $conteudoNormalizado;
    }

    /**
     * Obtém o identificador do utilizador autenticado.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return int Identificador do utilizador.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorUtilizador(
        Request $pedido,
    ): int {
        $identificador = $pedido
            ->user()
            ?->getAuthIdentifier();

        if (
            ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para comentar.',
            );
        }

        return (int) $identificador;
    }
}
