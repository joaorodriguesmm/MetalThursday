<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use LogicException;

/**
 * Valida a alteração administrativa do papel de um utilizador.
 *
 * A autorização é executada através da política antes da validação. O papel
 * é normalizado segundo a enumeração pública da aplicação e a confirmação
 * explícita impede alterações acidentais.
 *
 * @since 2.0.0
 */
final class AlterarPapelUtilizadorRequest extends FormRequest
{
    /**
     * Saco utilizado para os erros da alteração do papel.
     *
     * Esta propriedade não deve ser tipada porque é herdada do
     * {@see FormRequest}.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $errorBag =
        'papel';

    /**
     * Determina se o pedido pode ser processado.
     *
     * A autenticação é resolvida explicitamente através do guard `sessao` e
     * a decisão definitiva pertence à política dos utilizadores.
     *
     * @return bool Verdadeiro quando o responsável pode alterar o papel do
     *              utilizador indicado na rota.
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
                'alterarPapel',
                $utilizadorAfetado,
            );
    }

    /**
     * Normaliza o papel antes da validação.
     *
     * Apenas valores reconhecidos são substituídos pelo respetivo valor
     * canónico. Valores inválidos são preservados para que a validação os
     * rejeite.
     *
     * @since 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $papel =
            PapelUtilizador::tentarCriar(
                $this->input(
                    'papel',
                ),
            );

        if ($papel === null) {
            return;
        }

        $this->merge([
            'papel' => $papel->value,
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 2.0.0
     */
    public function rules(): array
    {
        return [
            'papel' => [
                'bail',
                'required',
                'string',
                Rule::enum(
                    PapelUtilizador::class,
                ),
            ],

            'confirmar_alteracao_papel' => [
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
            'papel.required' => 'Seleciona o novo papel do utilizador.',

            'papel.string' => 'O papel selecionado deve ser uma sequência de caracteres.',

            'papel.enum' => 'O papel selecionado não é reconhecido.',

            'confirmar_alteracao_papel.accepted' => 'Confirma explicitamente a alteração do papel.',
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
            'papel' => 'novo papel',

            'confirmar_alteracao_papel' => 'confirmação da alteração do papel',
        ];
    }

    /**
     * Obtém o novo papel validado.
     *
     * @return PapelUtilizador Papel reconhecido.
     *
     * @throws LogicException Quando o resultado validado deixa de cumprir o
     *                        contrato da enumeração.
     *
     * @since 2.0.0
     */
    public function obterPapelNovo(): PapelUtilizador
    {
        $papel =
            PapelUtilizador::tentarCriar(
                $this->validated(
                    'papel',
                ),
            );

        if ($papel === null) {
            throw new LogicException(
                'O pedido validado não contém um papel reconhecido.',
            );
        }

        return $papel;
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
                'Não existe um utilizador autenticado válido para alterar o papel.',
            );
        }

        return $utilizador;
    }
}
