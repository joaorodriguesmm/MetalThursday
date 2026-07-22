<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida a atualização das permissões de e-mail do utilizador.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class AtualizarPermissoesEmailRequest extends FormRequest
{
    /**
     * Saco de erros utilizado pelo formulário das permissões de e-mail.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $errorBag = 'permissoesEmail';

    /**
     * Determina se o utilizador pode executar o pedido.
     *
     * A autenticação é assegurada pelo middleware da rota.
     *
     * @return bool - Verdadeiro quando o pedido está autorizado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação.
     *
     * Uma lista vazia é válida e representa a remoção de todas as permissões.
     *
     * @return array<string, array<int, mixed>> - Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function rules(): array
    {
        return [
            'permissoes_email' => [
                'present',
                'array',
            ],

            'permissoes_email.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists(
                    'email_permissions',
                    'id',
                ),
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação personalizadas.
     *
     * @return array<string, string> - Mensagens de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function messages(): array
    {
        return [
            'permissoes_email.present' => 'A lista de permissões de e-mail deve ser enviada.',

            'permissoes_email.array' => 'As permissões de e-mail devem ser apresentadas numa lista.',

            'permissoes_email.*.integer' => 'Cada permissão de e-mail deve possuir um identificador válido.',

            'permissoes_email.*.distinct' => 'A mesma permissão de e-mail não pode ser selecionada mais do que uma vez.',

            'permissoes_email.*.exists' => 'A permissão de e-mail selecionada não existe.',
        ];
    }

    /**
     * Obtém os nomes legíveis dos atributos.
     *
     * @return array<string, string> - Nomes dos atributos.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function attributes(): array
    {
        return [
            'permissoes_email' => 'permissões de e-mail',

            'permissoes_email.*' => 'permissão de e-mail',
        ];
    }

    /**
     * Obtém os identificadores validados das permissões.
     *
     * @return array<int, int> - Identificadores normalizados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function identificadoresPermissoes(): array
    {
        $dadosValidados = $this->validated();

        $identificadores =
            $dadosValidados['permissoes_email'] ?? [];

        return array_values(
            array_map(
                static fn (
                    mixed $identificador,
                ): int => (int) $identificador,
                $identificadores,
            ),
        );
    }

    /**
     * Prepara os dados antes da validação.
     *
     * Os navegadores não enviam campos checkbox quando nenhum está
     * selecionado. Nesse caso, é criada explicitamente uma lista vazia.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        if ($this->exists('permissoes_email')) {
            return;
        }

        $this->merge([
            'permissoes_email' => [],
        ]);
    }
}
