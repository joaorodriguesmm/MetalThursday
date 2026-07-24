<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday;

use App\Servicos\MetalThursday\ServicoMusicasFavoritasEdicao;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Valida as músicas favoritas dos utilizadores numa edição.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class GuardarMusicasFavoritasEdicaoRequest extends FormRequest
{
    /**
     * Determina se o pedido pode ser processado.
     *
     * A autorização da operação é realizada pelo controlador através da
     * política da edição.
     *
     * @return bool Verdadeiro para permitir a validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza os nomes das músicas antes da validação.
     *
     * Strings vazias são convertidas para nulo, permitindo que uma posição
     * ainda não preenchida seja enviada pelo formulário.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $classificacoes = $this->input(
            'classificacoes',
        );

        if (! is_array($classificacoes)) {
            return;
        }

        $classificacoesNormalizadas = [];

        foreach (
            $classificacoes as $identificadorUtilizador => $musicas
        ) {
            if (! is_array($musicas)) {
                $classificacoesNormalizadas[$identificadorUtilizador] = $musicas;

                continue;
            }

            $classificacoesNormalizadas[$identificadorUtilizador] = array_map(
                static function (
                    mixed $musica,
                ): mixed {
                    if (! is_string($musica)) {
                        return $musica;
                    }

                    $musica = trim(
                        $musica,
                    );

                    return $musica !== ''
                        ? $musica
                        : null;
                },
                $musicas,
            );
        }

        $this->merge([
            'classificacoes' => $classificacoesNormalizadas,
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * @return array<string, array<int, string>> Regras de validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function rules(): array
    {
        return [
            'classificacoes' => [
                'bail',
                'required',
                'array',
                'min:1',
            ],

            'classificacoes.*' => [
                'bail',
                'required',
                'array',
                'list',
                'size:'
                    .ServicoMusicasFavoritasEdicao::NUMERO_POSICOES,
            ],

            'classificacoes.*.*' => [
                'bail',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function messages(): array
    {
        return [
            'classificacoes.required' => 'Não foram recebidas as músicas favoritas.',

            'classificacoes.array' => 'As músicas favoritas devem ser enviadas numa lista.',

            'classificacoes.min' => 'Deve existir, pelo menos, uma classificação.',

            'classificacoes.*.required' => 'Não foram recebidas as posições de um dos utilizadores.',

            'classificacoes.*.array' => 'As posições de um dos utilizadores não são válidas.',

            'classificacoes.*.list' => 'As posições devem ser enviadas numa lista ordenada.',

            'classificacoes.*.size' => 'Cada utilizador deve possuir exatamente três posições.',

            'classificacoes.*.*.string' => 'O nome de uma das músicas não é válido.',

            'classificacoes.*.*.max' => 'O nome de uma música não pode ter mais de 255 caracteres.',
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
            'classificacoes' => 'músicas favoritas',

            'classificacoes.*' => 'classificação do utilizador',

            'classificacoes.*.*' => 'música',
        ];
    }

    /**
     * Obtém as classificações validadas.
     *
     * @return array<int|string, array<int, string|null>> Classificações.
     *
     * @throws LogicException Quando o valor validado não é uma lista.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterClassificacoes(): array
    {
        $classificacoes = $this->validated(
            'classificacoes',
        );

        if (! is_array($classificacoes)) {
            throw new LogicException(
                'O pedido validado não contém as classificações.',
            );
        }

        /** @var array<int|string, array<int, string|null>> $classificacoes */
        return $classificacoes;
    }
}
