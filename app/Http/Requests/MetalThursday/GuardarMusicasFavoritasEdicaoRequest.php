<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MusicaFavoritaEdicao;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Valida as músicas favoritas dos utilizadores numa edição.
 *
 * Cada chave do primeiro nível representa um utilizador e contém uma lista
 * ordenada com o número de posições definido pelo modelo
 * {@see MusicaFavoritaEdicao}.
 *
 * As posições ainda não preenchidas podem ser enviadas como strings vazias,
 * sendo normalizadas para nulo antes da validação.
 *
 * @since 2.0.0
 */
final class GuardarMusicasFavoritasEdicaoRequest extends FormRequest
{
    /**
     * Determina se o utilizador autenticado pode alterar a edição.
     *
     * A política é aplicada antes da normalização das músicas e da execução
     * das regras de validação.
     *
     * @return bool Verdadeiro quando a alteração é autorizada.
     *
     * @since 2.0.0
     */
    public function authorize(): bool
    {
        $utilizador = $this->user(
            'sessao',
        );

        $edicao = $this->route(
            'edicao',
        );

        return $utilizador instanceof Utilizador
            && $edicao instanceof Edicao
            && $utilizador->can(
                'update',
                $edicao,
            );
    }

    /**
     * Normaliza os nomes das músicas antes da validação.
     *
     * Sequências de whitespace são convertidas num único espaço e os espaços
     * exteriores são removidos. Strings vazias são convertidas para nulo,
     * permitindo que uma posição ainda não preenchida seja enviada pelo
     * formulário.
     *
     * Texto UTF-8 inválido e caracteres de controlo proibidos permanecem
     * inalterados para que as regras de validação os possam rejeitar.
     *
     * Valores com estruturas ou tipos inesperados permanecem igualmente
     * inalterados para que sejam rejeitados pelas regras correspondentes.
     *
     * @since 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $musicasFavoritas = $this->input(
            'musicas_favoritas',
        );

        if (! is_array($musicasFavoritas)) {
            return;
        }

        $musicasFavoritasNormalizadas = [];

        foreach (
            $musicasFavoritas as $identificadorUtilizador => $musicas
        ) {
            if (! is_array($musicas)) {
                $musicasFavoritasNormalizadas[$identificadorUtilizador] =
                    $musicas;

                continue;
            }

            $musicasNormalizadas = [];

            foreach ($musicas as $indice => $musica) {
                $musicasNormalizadas[$indice] =
                    $this->normalizarMusica(
                        $musica,
                    );
            }

            $musicasFavoritasNormalizadas[$identificadorUtilizador] =
                $musicasNormalizadas;
        }

        $this->merge([
            'musicas_favoritas' => $musicasFavoritasNormalizadas,
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * Os limites e o número de posições pertencem exclusivamente ao modelo
     * {@see MusicaFavoritaEdicao}.
     *
     * A existência e a disponibilidade dos utilizadores indicados são
     * verificadas pelo serviço responsável pela sincronização.
     *
     * @return array<string, list<string|Closure>> Regras de validação.
     *
     * @since 2.0.0
     */
    public function rules(): array
    {
        return [
            'musicas_favoritas' => [
                'bail',
                'required',
                'array',
                'min:1',
            ],

            'musicas_favoritas.*' => [
                'bail',
                'required',
                'array',
                'list',
                'size:'.MusicaFavoritaEdicao::NUMERO_POSICOES,
            ],

            'musicas_favoritas.*.*' => [
                'bail',
                'nullable',
                'string',

                /**
                 * Confirma que a identificação da música contém texto UTF-8
                 * válido e não possui caracteres de controlo proibidos.
                 *
                 * Tabulações e quebras de linha já foram normalizadas para
                 * espaços durante a preparação do pedido.
                 *
                 * @param  string  $atributo  Nome do atributo.
                 * @param  mixed  $valor  Valor recebido.
                 * @param  Closure(string): void  $falhar  Função de erro.
                 *
                 * @since 2.0.0
                 */
                static function (
                    string $atributo,
                    mixed $valor,
                    Closure $falhar,
                ): void {
                    if (
                        $valor === null
                        || ! is_string($valor)
                    ) {
                        return;
                    }

                    if (
                        preg_match(
                            '//u',
                            $valor,
                        ) !== 1
                    ) {
                        $falhar(
                            'A identificação de uma das músicas contém texto inválido.',
                        );

                        return;
                    }

                    if (
                        preg_match(
                            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                            $valor,
                        ) === 1
                    ) {
                        $falhar(
                            'A identificação de uma das músicas contém caracteres inválidos.',
                        );
                    }
                },

                'max:'.MusicaFavoritaEdicao::COMPRIMENTO_MAXIMO_MUSICA,
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
            'musicas_favoritas.required' => 'Não foram recebidas as músicas favoritas.',

            'musicas_favoritas.array' => 'As músicas favoritas devem ser enviadas numa lista.',

            'musicas_favoritas.min' => 'Deve existir, pelo menos, um conjunto de músicas favoritas.',

            'musicas_favoritas.*.required' => 'Não foram recebidas as posições de um dos utilizadores.',

            'musicas_favoritas.*.array' => 'As posições de um dos utilizadores não são válidas.',

            'musicas_favoritas.*.list' => 'As posições devem ser enviadas numa lista ordenada.',

            'musicas_favoritas.*.size' => sprintf(
                'Cada utilizador deve possuir exatamente %d posições.',
                MusicaFavoritaEdicao::NUMERO_POSICOES,
            ),

            'musicas_favoritas.*.*.string' => 'O nome de uma das músicas não é válido.',

            'musicas_favoritas.*.*.max' => sprintf(
                'O nome de uma música não pode ter mais de %d caracteres.',
                MusicaFavoritaEdicao::COMPRIMENTO_MAXIMO_MUSICA,
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
            'musicas_favoritas' => 'músicas favoritas',

            'musicas_favoritas.*' => 'músicas favoritas do utilizador',

            'musicas_favoritas.*.*' => 'música',
        ];
    }

    /**
     * Obtém as músicas favoritas validadas.
     *
     * @return array<int|string, list<string|null>> Músicas favoritas
     *                                              organizadas por utilizador.
     *
     * @throws LogicException Quando o resultado validado não possui a
     *                        estrutura esperada.
     *
     * @since 2.0.0
     */
    public function obterMusicasFavoritas(): array
    {
        $musicasFavoritas = $this->validated(
            'musicas_favoritas',
        );

        if (! is_array($musicasFavoritas)) {
            throw new LogicException(
                'O pedido validado não contém as músicas favoritas.',
            );
        }

        foreach ($musicasFavoritas as $musicas) {
            if (
                ! is_array($musicas)
                || ! array_is_list($musicas)
            ) {
                throw new LogicException(
                    'O pedido validado contém uma lista de músicas inválida.',
                );
            }

            foreach ($musicas as $musica) {
                if (
                    $musica !== null
                    && ! is_string($musica)
                ) {
                    throw new LogicException(
                        'O pedido validado contém uma música inválida.',
                    );
                }
            }
        }

        /** @var array<int|string, list<string|null>> $musicasFavoritas */
        return $musicasFavoritas;
    }

    /**
     * Normaliza o nome de uma música.
     *
     * @param  mixed  $musica  Valor recebido.
     * @return mixed Nome normalizado, nulo ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarMusica(
        mixed $musica,
    ): mixed {
        if (! is_string($musica)) {
            return $musica;
        }

        if (
            preg_match(
                '//u',
                $musica,
            ) !== 1
            || preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $musica,
            ) === 1
        ) {
            return $musica;
        }

        $musicaNormalizada = preg_replace(
            '/\s+/u',
            ' ',
            $musica,
        );

        if (! is_string($musicaNormalizada)) {
            return $musica;
        }

        $musicaNormalizada = trim(
            $musicaNormalizada,
            ' ',
        );

        return $musicaNormalizada !== ''
            ? $musicaNormalizada
            : null;
    }
}
