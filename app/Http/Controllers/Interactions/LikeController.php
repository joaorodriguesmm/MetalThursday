<?php

namespace App\Http\Controllers\Interactions;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Traits\NotifiesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gere os gostos a comentários.
 *
 * @since 1.0
 * @version 1.0
 */
class LikeController extends Controller
{
    use NotifiesUsers;

    /**
     * Adiciona ou remove um gosto a um comentário.
     *
     * @param Request $request - Pedido HTTP.
     * @param Comment $comment - Comentário.
     * @return JsonResponse - Resposta JSON.
     *
     * @since 1.0
     * @version 1.0
     */
    public function toggleLike(Request $request, Comment $comment): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $user = Auth::user();
        $like = $comment->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            $comment->likes()->create(['user_id' => $user->id]);
            $liked = true;
            $commentAuthor = $comment->user;
            if ($commentAuthor && $user->id !== $commentAuthor->id) {
                $notification = new \App\Notifications\UserInteractionOccurred($comment, $user, 'gostou do');
                \Illuminate\Support\Facades\Notification::send($commentAuthor, $notification);
            }
        }

        return response()->json([
            'liked'       => $liked,
            'likes_count' => $comment->likes()->count()
        ]);
    }

    public function getLikers(Comment $comment)
    {
        $names = $comment->likes()
            ->with('user')
            ->get()
            ->pluck('user.name')
            ->toArray();

        return response()->json([
            'names' => $names,
            'html' => count($names) > 0 ? implode('<br>', $names) : 'Ainda não há gostos.'
        ]);
    }
}
