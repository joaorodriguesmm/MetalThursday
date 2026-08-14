<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Valida a confirmação necessária para revogar um convite.
 *
 * A autorização é executada através da política antes da validação. A
 * confirmação explícita impede revogações acidentais na interface.
 *
 * @since 2.0.0
 */
final class RevogarConviteRequest extends FormRequest
{
    /**
     * Saco utilizado para os erros da revogação.
     *
     * Esta propriedade não deve ser tipada porque é herdada do
     * {@see FormRequest}.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $errorBag =
        'revogacao_convite';

    /**
     * Determina se o pedido pode ser processado.
     *
     * @return bool Verdadeiro quando o responsável pode revogar o convite
     *              indicado na rota.
     *
     * @since 2.0.0
     */
    public function authorize(): bool
    {
        $responsavel =
            $this->user(
                'sessao',
            );

        $convite =
            $this->route(
                'convite',
            );

        return $responsavel instanceof Utilizador
            && $convite instanceof Convite
            && $responsavel->can(
                'revogar',
                $convite,
            );
    }

    /**
     * Obtém as regras de validação.
     *
     * @return array<string, list<string>> Regras de validação.
     *
     * @since 2.0.0
     */
    public function rules(): array
    {
        return [
            'confirmar_revogacao' => [
                'accepted',
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 2.0.0
     */
    public function messages(): array
    {
        return [
            'confirmar_revogacao.accepted' => 'Confirma explicitamente a revogação do convite.',
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     */
    public function attributes(): array
    {
        return [
            'confirmar_revogacao' => 'confirmação da revogação',
        ];
    }

    /**
     * Obtém o superadministrador autenticado.
     *
     * @return Utilizador Utilizador responsável.
     *
     * @throws LogicException Quando o pedido não possui autenticação válida.
     *
     * @since 2.0.0
     */
    public function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador =
            $this->user(
                'sessao',
            );

        if (! $utilizador instanceof Utilizador) {
            throw new LogicException(
                'Não existe um utilizador autenticado válido para revogar o convite.',
            );
        }

        return $utilizador;
    }
}
