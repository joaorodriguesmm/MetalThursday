<?php

declare(strict_types=1);

namespace App\Http\Requests\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;

/**
 * Valida os dados necessários para atualizar um comentário.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class AtualizarComentarioRequest extends PedidoComentarioRequest
{
    /**
     * Determina se o utilizador autenticado pode atualizar o comentário.
     *
     * A política é aplicada antes da normalização do conteúdo e da execução
     * das regras de validação.
     *
     * @return bool Verdadeiro quando a atualização é autorizada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function authorize(): bool
    {
        $utilizador = $this->user(
            'sessao',
        );

        $comentario = $this->route(
            'comentario',
        );

        return $utilizador instanceof Utilizador
            && $comentario instanceof Comentario
            && $utilizador->can(
                'update',
                $comentario,
            );
    }
}
