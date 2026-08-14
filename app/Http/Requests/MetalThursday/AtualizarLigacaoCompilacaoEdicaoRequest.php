<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Valida a ligação da compilação associada a uma edição.
 *
 * Uma ligação vazia representa a remoção da ligação atualmente persistida.
 * Quando existe uma ligação, apenas são aceites endereços absolutos HTTP ou
 * HTTPS sem credenciais incorporadas.
 *
 * A validação HTTP produz mensagens adequadas para o utilizador. O modelo
 * {@see Edicao} volta a validar o valor antes da persistência.
 *
 * @since 2.0.0
 */
final class AtualizarLigacaoCompilacaoEdicaoRequest extends FormRequest
{
    /**
     * Determina se o utilizador autenticado pode atualizar a edição.
     *
     * A política é aplicada antes da normalização da ligação e da execução
     * das regras de validação.
     *
     * @return bool Verdadeiro quando a atualização é autorizada.
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
     * Normaliza a ligação antes da validação.
     *
     * Uma string vazia ou composta apenas por espaços ASCII é convertida para
     * nulo, permitindo remover a ligação atualmente associada à edição.
     *
     * Apenas espaços ASCII exteriores são removidos. Caracteres de controlo
     * permanecem inalterados para serem rejeitados pelas regras de validação.
     *
     * Valores que não sejam strings permanecem inalterados para que as regras
     * de tipo produzam a mensagem de validação correspondente.
     *
     * @since 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $ligacao = $this->input(
            'ligacao_compilacao',
        );

        if (! is_string($ligacao)) {
            return;
        }

        $ligacaoNormalizada = trim(
            $ligacao,
            ' ',
        );

        $this->merge([
            'ligacao_compilacao' => $ligacaoNormalizada !== ''
                ? $ligacaoNormalizada
                : null,
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * A regra personalizada confirma as restrições adicionais aplicadas pelo
     * modelo: texto UTF-8 válido, ausência de caracteres de controlo,
     * credenciais, barras invertidas e espaços interiores.
     *
     * @return array<string, list<string|Closure>> Regras de validação.
     *
     * @since 2.0.0
     */
    public function rules(): array
    {
        return [
            'ligacao_compilacao' => [
                'bail',
                'present',
                'nullable',
                'string',
                'url:http,https',
                'max:'.Edicao::COMPRIMENTO_MAXIMO_LIGACAO_COMPILACAO,

                /**
                 * Confirma as restrições adicionais da ligação.
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
                    if ($valor === null) {
                        return;
                    }

                    if (! is_string($valor)) {
                        return;
                    }

                    if (
                        preg_match(
                            '//u',
                            $valor,
                        ) !== 1
                    ) {
                        $falhar(
                            'A ligação da compilação contém texto inválido.',
                        );

                        return;
                    }

                    if (
                        str_contains(
                            $valor,
                            '\\',
                        )
                        || preg_match(
                            '/[\x00-\x20\x7F]/',
                            $valor,
                        ) === 1
                    ) {
                        $falhar(
                            'A ligação da compilação contém caracteres inválidos.',
                        );

                        return;
                    }

                    $componentes = parse_url(
                        $valor,
                    );

                    if (
                        ! is_array($componentes)
                        || ! isset(
                            $componentes['scheme'],
                            $componentes['host'],
                        )
                        || trim(
                            (string) $componentes['host'],
                        ) === ''
                        || isset(
                            $componentes['user'],
                        )
                        || isset(
                            $componentes['pass'],
                        )
                    ) {
                        $falhar(
                            'A ligação da compilação deve ser um endereço HTTP ou HTTPS válido.',
                        );
                    }
                },
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
            'ligacao_compilacao.present' => 'Não foi recebida a ligação da compilação.',

            'ligacao_compilacao.string' => 'A ligação da compilação não é válida.',

            'ligacao_compilacao.url' => 'A ligação da compilação deve ser um endereço HTTP ou HTTPS válido.',

            'ligacao_compilacao.max' => sprintf(
                'A ligação da compilação não pode ter mais de %d caracteres.',
                Edicao::COMPRIMENTO_MAXIMO_LIGACAO_COMPILACAO,
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
            'ligacao_compilacao' => 'ligação da compilação',
        ];
    }

    /**
     * Obtém a ligação validada.
     *
     * O valor nulo representa a remoção da ligação atualmente persistida.
     *
     * @return string|null Ligação normalizada ou nulo.
     *
     * @throws LogicException Quando o resultado validado não contém uma
     *                        ligação válida nem o valor nulo esperado.
     *
     * @since 2.0.0
     */
    public function obterLigacaoCompilacao(): ?string
    {
        $ligacao = $this->validated(
            'ligacao_compilacao',
        );

        if ($ligacao === null) {
            return null;
        }

        if (
            ! is_string($ligacao)
            || $ligacao === ''
        ) {
            throw new LogicException(
                'O pedido validado não contém uma ligação da compilação válida.',
            );
        }

        return $ligacao;
    }
}
