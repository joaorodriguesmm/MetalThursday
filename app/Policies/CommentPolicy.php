<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

/**
 * Define as permissões para executar ações em comentários.
 *
 * @since 1.0
 * @version 1.0
 */
class CommentPolicy
{
    /**
     * Permite que um super-administrador execute qualquer ação.
     *
     * @param User $user - O utilizador autenticado.
     * @param string $ability - A ação a executar.
     * @return bool|null - Verdadeiro se o utilizador for super-administrador.
     *
     * @since 1.0
     * @version 1.0
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->id === 1) {
            return true;
        }

        return null;
    }

    /**
     * Obtém se o utilizador pode editar um comentário.
     *
     * @param User $user - O utilizador autenticado.
     * @param Comment $comment - O comentário.
     * @return bool - Verdadeiro se o utilizador for o autor do comentário.
     *
     * @since 1.0
     * @version 1.0
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * Obtém se o utilizador pode apagar um comentário.
     *
     * @param User $user - O utilizador autenticado.
     * @param Comment $comment - O comentário.
     * @return bool - Verdadeiro se o utilizador for o autor do comentário.
     *
     * @since 1.0
     * @version 1.0
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }
}
