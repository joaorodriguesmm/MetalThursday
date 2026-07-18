<?php

namespace App\Http\Controllers\Interactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interactions\StoreCommentRequest;
use App\Http\Requests\Interactions\UpdateCommentRequest;
use App\Models\MetalThursday;
use App\Models\Comment;
use App\Models\MtSection;
use App\Traits\NotifiesUsers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gere os comentários.
 *
 * @since 1.0
 * @version 1.0
 */
class CommentController extends Controller
{
    use AuthorizesRequests, NotifiesUsers;

    /**
     * Guarda um comentário.
     *
     * @param Request $request - Pedido HTTP.
     * @param string $commentableType - Tipo de destino do comentário.
     * @param int $commentableId - Id do tipo de destino.
     * @return string - HTML do comentário.
     *
     * @since 1.0
     * @version 1.0
     */
    public function store(StoreCommentRequest $request, string $commentableType, int $commentableId): string
    {
        $validated   = $request->validated();
        $modelClass  = $commentableType === 'section' ? MtSection::class : MetalThursday::class;
        $commentable = $modelClass::findOrFail($commentableId);

        $comment = $commentable->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        if ($commentable instanceof MtSection) {
            $commentable->load('metalThursday.author');
        } else {
            $commentable->load('author');
        }
        $this->notifyOtherUsers($commentable, 'comentou');

        return view('components.comment', ['comment' => $comment])->render();
    }

    /**
     * Guarda uma resposta a um comentário.
     *
     * @param Request $request - Pedido HTTP.
     * @param Comment $comment - Comentário a responder.
     * @return string - HTML da resposta.
     *
     * @since 1.0
     * @version 1.0
     */
    public function storeReply(StoreCommentRequest $request, Comment $comment): string
    {
        $validated = $request->validated();

        $reply = $comment->replies()->create([
            'user_id'          => $request->user()->id,
            'content'          => $validated['content'],
            'commentable_id'   => $comment->commentable_id,
            'commentable_type' => $comment->commentable_type,
        ]);

        if ($comment->commentable instanceof MtSection) {
            $comment->load('commentable.metalThursday.author');
        } else {
            $comment->load('commentable.author');
        }

        $this->notifyOtherUsers($comment, 'respondeu a');

        return view('components.comment', ['comment' => $reply])->render();
    }

    /**
     * Atualiza um comentário.
     *
     * @param UpdateCommentRequest $request - Pedido HTTP.
     * @param Comment $comment - Comentário a atualizar.
     * @return JsonResponse - Resposta JSON com o conteúdo atualizado.
     *
     * @since 1.0
     * @version 1.0
     */
    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $validated = $request->validated();
        $comment->update(['content' => $validated['content']]);

        return response()->json([
            'success'      => true,
            'content'      => $comment->content,
            'content_html' => nl2br(e($comment->content))
        ]);
    }

    /**
     * Elimina um comentário.
     *
     * @param Comment $comment - Comentário a eliminar.
     * @return JsonResponse - Mensagem de sucesso.
     *
     * @since 1.0
     * @version 1.0
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);
        $comment->delete();
        return response()->json(['message' => 'Comentário eliminado com sucesso!']);
    }
}
