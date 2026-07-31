<?php

declare(strict_types=1);

namespace App\Http\Requests\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;

/**
 * Valida os dados necessários para publicar um comentário ou uma resposta.
 *
 * @since 1.0.0
 *
 * @version 2.2.0
 */
final class GuardarComentarioRequest extends PedidoComentarioRequest
{
    /**
     * Determina se o utilizador autenticado pode publicar comentários.
     *
     * A política é aplicada antes da normalização do conteúdo e da execução
     * das regras de validação.
     *
     * @return bool Verdadeiro quando a publicação é autorizada.
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

        return $utilizador instanceof Utilizador
            && $utilizador->can(
                'create',
                Comentario::class,
            );
    }
}
