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
 * Quando o navegador não envia o campo por não existir qualquer checkbox
 * selecionado, o pedido cria explicitamente essa lista vazia.
 *
 * @since 1.0.0
 */
final class AtualizarPermissoesEmailRequest extends FormRequest
{
    /**
     * Saco de erros utilizado pelo formulário das permissões de e-mail.
     *
     * Esta propriedade não deve ser tipada, porque é herdada do
     * {@see FormRequest}.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $errorBag = 'permissoesEmail';

    /**
     * Determina se o pedido pode ser processado.
     *
     * A autenticação é resolvida explicitamente através do guard `sessao`,
     * sem depender do guard predefinido da aplicação.
     *
     * @return bool Verdadeiro quando existe um utilizador autenticado válido.
     *
     * @since 1.0.0
     */
    public function authorize(): bool
    {
        return $this->user(
            'sessao',
        ) instanceof Utilizador;
    }

    /**
     * Prepara os dados antes da validação.
     *
     * Os navegadores não enviam campos de seleção múltipla quando nenhum
     * valor está selecionado. Nesse caso, é criada uma lista vazia.
     *
     * Os identificadores numéricos recebidos como strings são convertidos
     * para inteiros. Os restantes valores são preservados para que a
     * validação os rejeite.
     *
     * As chaves originais são mantidas para permitir que a regra `list`
     * rejeite estruturas associativas ou listas com índices em falta.
     *
     * @since 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $permissoes =
            $this->input(
                'permissoes_email',
                [],
            );

        if (! is_array($permissoes)) {
            $this->merge([
                'permissoes_email' => $permissoes,
            ]);

            return;
        }

        $identificadores = [];

        foreach ($permissoes as $indice => $identificador) {
            $identificadores[$indice] =
                $this->normalizarIdentificador(
                    $identificador,
                );
        }

        $this->merge([
            'permissoes_email' => $identificadores,
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * Uma lista vazia é válida e representa a remoção de todas as permissões.
     * Cada elemento deve corresponder a um identificador inteiro, positivo,
     * único e existente.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 1.0.0
     */
    public function rules(): array
    {
        return [
            'permissoes_email' => [
                'bail',
                'array',
                'list',
            ],

            'permissoes_email.*' => [
                'bail',
                'required',
                'integer',
                'min:1',
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
     */
    public function messages(): array
    {
        return [
            'permissoes_email.array' => 'As permissões de e-mail devem ser apresentadas numa lista.',

            'permissoes_email.list' => 'A lista de permissões de e-mail não tem um formato válido.',

            'permissoes_email.*.required' => 'Cada permissão de e-mail deve possuir um identificador válido.',

            'permissoes_email.*.integer' => 'Cada permissão de e-mail deve possuir um identificador válido.',

            'permissoes_email.*.min' => 'Cada permissão de e-mail deve possuir um identificador válido.',

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
     * @return list<int> Identificadores das permissões.
     *
     * @throws LogicException Quando o pedido validado não contém uma lista de
     *                        identificadores inteiros, positivos e distintos.
     *
     * @since 2.0.0
     */
    public function obterIdentificadoresPermissoes(): array
    {
        $identificadores =
            $this->validated(
                'permissoes_email',
            );

        if (
            ! is_array($identificadores)
            || ! array_is_list($identificadores)
        ) {
            throw new LogicException(
                'O pedido validado não contém uma lista válida de permissões de e-mail.',
            );
        }

        $identificadoresEncontrados = [];

        foreach ($identificadores as $identificador) {
            if (
                ! is_int($identificador)
                || $identificador < 1
            ) {
                throw new LogicException(
                    'Uma permissão de e-mail validada possui um identificador inesperado.',
                );
            }

            if (isset($identificadoresEncontrados[$identificador])) {
                throw new LogicException(
                    'O pedido validado contém uma permissão de e-mail repetida.',
                );
            }

            $identificadoresEncontrados[$identificador] =
                true;
        }

        /** @var list<int> $identificadores */
        return $identificadores;
    }

    /**
     * Normaliza um identificador de permissão de e-mail.
     *
     * Uma string composta exclusivamente por algarismos é convertida para
     * inteiro. Os restantes valores são preservados para que a validação os
     * rejeite.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Identificador normalizado ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificador(
        mixed $valor,
    ): mixed {
        if (
            is_string($valor)
            && ctype_digit(
                $valor,
            )
        ) {
            return (int) $valor;
        }

        return $valor;
    }
}
