<?php

declare(strict_types=1);

namespace App\Http\Requests\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Avaliacao;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Valida os dados necessários para guardar uma avaliação.
 *
 * A pontuação deve respeitar a escala e o incremento definidos pelo modelo
 * {@see Avaliacao}. A validação nesta camada produz mensagens adequadas para
 * o pedido HTTP, enquanto o modelo volta a proteger a persistência.
 *
 * @since 1.0.0
 */
final class GuardarAvaliacaoRequest extends FormRequest
{
    /**
     * Determina se o pedido pode ser processado.
     *
     * A autenticação é resolvida explicitamente através do guard `sessao`,
     * impedindo que a normalização e a validação sejam executadas para um
     * utilizador obtido através de outro guard.
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
     * Normaliza a pontuação antes da validação.
     *
     * É aceite uma vírgula como separador decimal, convertendo-a para o ponto
     * utilizado internamente pelo sistema.
     *
     * Apenas espaços ASCII exteriores são removidos. Valores com caracteres
     * de controlo permanecem inalterados para que a validação os rejeite sem
     * os remover silenciosamente.
     *
     * @since 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $pontuacao = $this->input(
            'pontuacao',
        );

        if (! is_string($pontuacao)) {
            return;
        }

        if (
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $pontuacao,
            ) === 1
        ) {
            return;
        }

        $this->merge([
            'pontuacao' => str_replace(
                ',',
                '.',
                trim(
                    $pontuacao,
                    ' ',
                ),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * Os limites e o incremento pertencem exclusivamente ao modelo
     * {@see Avaliacao}.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 1.0.0
     */
    public function rules(): array
    {
        return [
            'pontuacao' => [
                'bail',
                'required',
                $this->criarRegraAusenciaCaracteresControlo(),
                'numeric',

                'between:'
                    .Avaliacao::PONTUACAO_MINIMA
                    .','
                    .Avaliacao::PONTUACAO_MAXIMA,

                'multiple_of:'
                    .Avaliacao::INCREMENTO_PONTUACAO,
            ],
        ];
    }

    /**
     * Obtém as mensagens de erro específicas.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 1.0.0
     */
    public function messages(): array
    {
        return [
            'pontuacao.required' => 'Por favor, seleciona uma pontuação.',

            'pontuacao.numeric' => 'A pontuação selecionada não é válida.',

            'pontuacao.between' => sprintf(
                'A pontuação deve estar compreendida entre %s e %s.',
                $this->formatarPontuacao(
                    Avaliacao::PONTUACAO_MINIMA,
                ),
                $this->formatarPontuacao(
                    Avaliacao::PONTUACAO_MAXIMA,
                ),
            ),

            'pontuacao.multiple_of' => sprintf(
                'A pontuação deve utilizar intervalos de %s.',
                $this->formatarPontuacao(
                    Avaliacao::INCREMENTO_PONTUACAO,
                ),
            ),
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
            'pontuacao' => 'pontuação',
        ];
    }

    /**
     * Obtém a pontuação validada.
     *
     * @return float Pontuação normalizada.
     *
     * @throws LogicException Quando o resultado validado não contém um valor
     *                        numérico.
     *
     * @since 2.0.0
     */
    public function obterPontuacao(): float
    {
        $pontuacao = $this->validated(
            'pontuacao',
        );

        if (
            (
                ! is_int($pontuacao)
                && ! is_float($pontuacao)
                && ! is_string($pontuacao)
            )
            || ! is_numeric($pontuacao)
        ) {
            throw new LogicException(
                'O pedido validado não contém uma pontuação válida.',
            );
        }

        return round(
            (float) $pontuacao,
            1,
        );
    }

    /**
     * Cria a regra que rejeita caracteres de controlo na pontuação textual.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     */
    private function criarRegraAusenciaCaracteresControlo(): Closure
    {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ): void {
            if (
                ! is_string($valor)
                || preg_match(
                    '/[\x00-\x1F\x7F]/',
                    $valor,
                ) !== 1
            ) {
                return;
            }

            $falhar(
                'A pontuação selecionada não é válida.',
            );
        };
    }

    /**
     * Formata uma pontuação para apresentação em português.
     *
     * A parte decimal é omitida quando o valor representa um número inteiro.
     *
     * @param  float  $pontuacao  Pontuação recebida.
     * @return string Pontuação formatada.
     *
     * @since 2.0.0
     */
    private function formatarPontuacao(
        float $pontuacao,
    ): string {
        $pontuacaoFormatada = number_format(
            $pontuacao,
            1,
            ',',
            '',
        );

        return str_ends_with(
            $pontuacaoFormatada,
            ',0',
        )
            ? substr(
                $pontuacaoFormatada,
                0,
                -2,
            )
            : $pontuacaoFormatada;
    }
}
