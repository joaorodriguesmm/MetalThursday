<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Valida a confirmação necessária para reativar o acesso de um utilizador.
 *
 * A autorização é executada através da política antes da validação. A
 * confirmação explícita impede reativações acidentais na interface.
 *
 * @since 2.0.0
 */
final class ReativarUtilizadorRequest extends FormRequest
{
    /**
     * Saco utilizado para os erros da reativação.
     *
     * Esta propriedade não deve ser tipada porque é herdada do
     * {@see FormRequest}.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $errorBag =
        'reativacao';

    /**
     * Determina se o pedido pode ser processado.
     *
     * @return bool Verdadeiro quando o responsável pode reativar o utilizador
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

        $utilizadorAfetado =
            $this->route(
                'utilizador',
            );

        return $responsavel instanceof Utilizador
            && $utilizadorAfetado instanceof Utilizador
            && $responsavel->can(
                'reativar',
                $utilizadorAfetado,
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
            'confirmar_reativacao' => [
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
            'confirmar_reativacao.accepted' => 'Confirma explicitamente a reativação do acesso.',
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
            'confirmar_reativacao' => 'confirmação da reativação',
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
                'Não existe um utilizador autenticado válido para reativar o acesso.',
            );
        }

        return $utilizador;
    }
}
