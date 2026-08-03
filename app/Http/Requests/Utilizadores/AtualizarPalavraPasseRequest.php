<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Valida os dados necessários para alterar a palavra-passe.
 *
 * As palavras-passe não são normalizadas, porque espaços e outros caracteres
 * podem fazer parte do segredo definido pelo utilizador.
 *
 * A validação do pedido fornece resposta imediata ao formulário. O serviço de
 * atualização volta a confirmar a palavra-passe atual e as regras de domínio
 * dentro da operação definitiva.
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
final class AtualizarPalavraPasseRequest extends FormRequest
{
    /**
     * Saco utilizado para os erros da alteração da palavra-passe.
     *
     * Esta propriedade não deve ser tipada, porque é herdada do
     * {@see FormRequest}.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.2.0
     */
    protected $errorBag = 'palavraPasse';

    /**
     * Determina se o pedido pode ser processado.
     *
     * A autenticação é resolvida explicitamente através do guard `sessao`,
     * sem depender do guard predefinido da aplicação.
     *
     * @return bool Verdadeiro quando existe um utilizador autenticado válido.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function authorize(): bool
    {
        return $this->user(
            'sessao',
        ) instanceof Utilizador;
    }

    /**
     * Obtém as regras de validação.
     *
     * A palavra-passe atual é confirmada através do guard `sessao`. A nova
     * palavra-passe utiliza a política central da aplicação, deve ser
     * diferente da atual e coincidir com a confirmação explícita.
     *
     * O limite aplicado à palavra-passe atual protege o processamento de
     * valores anormalmente extensos, sem exigir que uma palavra-passe antiga
     * cumpra a política de complexidade atual.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function rules(): array
    {
        return [
            'palavra_passe_atual' => [
                'bail',
                'required',
                'string',
                'max:'.RequisitosPalavraPasse::comprimentoMaximo(),
                'current_password:sessao',
            ],

            'nova_palavra_passe' => [
                ...RequisitosPalavraPasse::regrasObrigatorias(),
                'different:palavra_passe_atual',
            ],

            'confirmacao_nova_palavra_passe' => [
                'bail',
                'required',
                'string',
                'max:'.RequisitosPalavraPasse::comprimentoMaximo(),
                'same:nova_palavra_passe',
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
     * @version 3.0.0
     */
    public function messages(): array
    {
        return [
            'palavra_passe_atual.required' => 'Por favor, insere a tua palavra-passe atual.',

            'palavra_passe_atual.string' => 'A palavra-passe atual não é válida.',

            'palavra_passe_atual.max' => 'A palavra-passe atual não é válida.',

            'palavra_passe_atual.current_password' => 'A palavra-passe atual introduzida não está correta.',

            'nova_palavra_passe.required' => 'Por favor, insere a nova palavra-passe.',

            'nova_palavra_passe.string' => 'A nova palavra-passe não é válida.',

            'nova_palavra_passe.max' => 'A nova palavra-passe é demasiado longa.',

            'nova_palavra_passe.different' => 'A nova palavra-passe deve ser diferente da palavra-passe atual.',

            'confirmacao_nova_palavra_passe.required' => 'Por favor, confirma a nova palavra-passe.',

            'confirmacao_nova_palavra_passe.string' => 'A confirmação da nova palavra-passe não é válida.',

            'confirmacao_nova_palavra_passe.max' => 'A confirmação da nova palavra-passe não é válida.',

            'confirmacao_nova_palavra_passe.same' => 'A nova palavra-passe e a confirmação não coincidem.',
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function attributes(): array
    {
        return [
            'palavra_passe_atual' => 'palavra-passe atual',

            'nova_palavra_passe' => 'nova palavra-passe',

            'confirmacao_nova_palavra_passe' => 'confirmação da nova palavra-passe',
        ];
    }

    /**
     * Obtém a palavra-passe atual validada.
     *
     * @return string Palavra-passe atual.
     *
     * @throws LogicException Quando o valor validado possui um tipo
     *                        inesperado.
     *
     * @since 2.2.0
     *
     * @version 2.0.0
     */
    public function obterPalavraPasseAtual(): string
    {
        return $this->obterTextoValidado(
            'palavra_passe_atual',
        );
    }

    /**
     * Obtém a nova palavra-passe validada.
     *
     * @return string Nova palavra-passe.
     *
     * @throws LogicException Quando o valor validado possui um tipo
     *                        inesperado.
     *
     * @since 2.1.0
     *
     * @version 2.0.0
     */
    public function obterNovaPalavraPasse(): string
    {
        return $this->obterTextoValidado(
            'nova_palavra_passe',
        );
    }

    /**
     * Obtém a confirmação da nova palavra-passe.
     *
     * @return string Confirmação da nova palavra-passe.
     *
     * @throws LogicException Quando o valor validado possui um tipo
     *                        inesperado.
     *
     * @since 2.2.0
     *
     * @version 1.0.0
     */
    public function obterConfirmacaoNovaPalavraPasse(): string
    {
        return $this->obterTextoValidado(
            'confirmacao_nova_palavra_passe',
        );
    }

    /**
     * Obtém um texto validado.
     *
     * @param  string  $campo  Nome do campo validado.
     * @return string Texto validado.
     *
     * @throws LogicException Quando o valor validado possui um tipo
     *                        inesperado.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function obterTextoValidado(
        string $campo,
    ): string {
        $valor =
            $this->validated(
                $campo,
            );

        if (! is_string($valor)) {
            throw new LogicException(
                sprintf(
                    'O campo validado "%s" possui um tipo inesperado.',
                    $campo,
                ),
            );
        }

        return $valor;
    }
}
