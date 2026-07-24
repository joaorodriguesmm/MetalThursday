<?php

declare(strict_types=1);

namespace App\Http\Requests\Interacoes;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida os dados necessários para guardar uma avaliação.
 *
 * A pontuação deve estar compreendida entre 0,5 e 10 e utilizar incrementos
 * de 0,5.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class GuardarAvaliacaoRequest extends FormRequest
{
    /**
     * Determina se o pedido pode ser processado.
     *
     * A autenticação e a autorização da interação são realizadas pela rota e
     * pelo controlador responsável.
     *
     * @return bool Verdadeiro para permitir a validação.
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
     * Normaliza a pontuação antes da validação.
     *
     * É aceite uma vírgula como separador decimal, convertendo-a para o ponto
     * esperado pelo sistema.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $pontuacao = $this->input('pontuacao');

        if (! is_string($pontuacao)) {
            return;
        }

        $this->merge([
            'pontuacao' => str_replace(
                ',',
                '.',
                trim($pontuacao),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * @return array<string, array<int, string>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function rules(): array
    {
        return [
            'pontuacao' => [
                'bail',
                'required',
                'numeric',
                'between:0.5,10',
                'multiple_of:0.5',
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
     * @version 2.0.0
     */
    public function messages(): array
    {
        return [
            'pontuacao.required' => 'Por favor, seleciona uma pontuação.',

            'pontuacao.numeric' => 'A pontuação selecionada não é válida.',

            'pontuacao.between' => 'A pontuação deve estar compreendida entre 0,5 e 10.',

            'pontuacao.multiple_of' => 'A pontuação deve utilizar intervalos de 0,5.',
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
            'pontuacao' => 'pontuação',
        ];
    }

    /**
     * Obtém a pontuação validada.
     *
     * @return float Pontuação normalizada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterPontuacao(): float
    {
        return (float) $this->validated(
            'pontuacao',
        );
    }
}
