<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use LogicException;

/**
 * Valida a atualização das permissões de e-mail do utilizador autenticado.
 *
 * Uma lista vazia representa a remoção de todas as permissões de e-mail.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class AtualizarPermissoesEmailRequest extends FormRequest
{
    /**
     * Saco de erros utilizado pelo formulário das permissões de e-mail.
     *
     * Esta propriedade não deve ser tipada, porque é herdada do FormRequest.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    protected $errorBag = 'permissoesEmail';

    /**
     * Determina se o pedido pode ser processado.
     *
     * Apenas o modelo principal de utilizador autenticado pode atualizar as
     * respetivas permissões de e-mail.
     *
     * @return bool Verdadeiro quando existe um utilizador autenticado.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function authorize(): bool
    {
        return $this->user() instanceof Utilizador;
    }

    /**
     * Prepara os dados antes da validação.
     *
     * Os navegadores não enviam campos de seleção múltipla quando nenhum
     * valor está selecionado. Nesse caso, é criada uma lista vazia.
     *
     * Os identificadores numéricos recebidos como strings são convertidos
     * para inteiros.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    protected function prepareForValidation(): void
    {
        $permissoes = $this->input(
            'permissoes_email',
            [],
        );

        if (! is_array($permissoes)) {
            $this->merge([
                'permissoes_email' => $permissoes,
            ]);

            return;
        }

        $this->merge([
            'permissoes_email' => array_values(
                array_map(
                    static function (
                        mixed $identificador,
                    ): mixed {
                        if (
                            is_string($identificador)
                            && ctype_digit($identificador)
                        ) {
                            return (int) $identificador;
                        }

                        return $identificador;
                    },
                    $permissoes,
                ),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * Uma lista vazia é válida e representa a remoção de todas as permissões.
     *
     * @return array<string, array<int, mixed>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function rules(): array
    {
        return [
            'permissoes_email' => [
                'present',
                'array',
                'list',
            ],

            'permissoes_email.*' => [
                'bail',
                'integer',
                'distinct:strict',

                Rule::exists(
                    PermissaoEmail::class,
                    'id',
                ),
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function messages(): array
    {
        return [
            'permissoes_email.present' => 'A lista de permissões de e-mail deve ser enviada.',

            'permissoes_email.array' => 'As permissões de e-mail devem ser apresentadas numa lista.',

            'permissoes_email.list' => 'A lista de permissões de e-mail não tem um formato válido.',

            'permissoes_email.*.integer' => 'Cada permissão de e-mail deve possuir um identificador válido.',

            'permissoes_email.*.distinct' => 'A mesma permissão de e-mail não pode ser selecionada mais do que uma vez.',

            'permissoes_email.*.exists' => 'A permissão de e-mail selecionada não existe.',
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
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
     * Obtém os identificadores validados das permissões de e-mail.
     *
     * @return array<int, int> Identificadores das permissões.
     *
     * @throws LogicException Quando o pedido validado não contém uma lista
     *                        válida.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function identificadoresPermissoes(): array
    {
        $identificadores = $this->validated(
            'permissoes_email',
        );

        if (! is_array($identificadores)) {
            throw new LogicException(
                'O pedido validado não contém uma lista de permissões de e-mail.',
            );
        }

        return array_values(
            array_map(
                static fn (
                    mixed $identificador,
                ): int => (int) $identificador,
                $identificadores,
            ),
        );
    }
}
