<?php

declare(strict_types=1);

namespace App\Http\Requests\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;

/**
 * Valida os dados necessários para publicar um comentário ou resposta.
 *
 * @since 1.0.0
 */
final class GuardarComentarioRequest extends PedidoComentarioRequest
{
    /**
     * Confirma que o utilizador pode publicar comentários.
     *
     * @since 2.0.0
     */
    public function authorize(): bool
    {
        $utilizador = $this->user('sessao');

        return $utilizador instanceof Utilizador
            && $utilizador->can(
                'create',
                Comentario::class,
            );
    }

    /**
     * Acrescenta a chave opcional de idempotência.
     *
     * Os clientes existentes continuam a funcionar sem a chave.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),

            'chave_submissao' => [
                'sometimes',
                'required',
                'uuid',
            ],
        ];
    }

    /**
     * Obtém a chave validada, quando fornecida.
     */
    public function obterChaveSubmissao(): ?string
    {
        $chave = $this->validated('chave_submissao');

        return is_string($chave)
            ? $chave
            : null;
    }
}
