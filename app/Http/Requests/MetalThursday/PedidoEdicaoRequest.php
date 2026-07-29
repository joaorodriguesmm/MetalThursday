<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday;

use App\Models\MetalThursday\Edicao;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

/**
 * Define a validação comum dos pedidos de criação e atualização de edições.
 *
 * A validação HTTP produz mensagens adequadas para o utilizador. O modelo
 * {@see Edicao} volta a normalizar e validar os atributos antes da
 * persistência, protegendo outros pontos de entrada.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
abstract class PedidoEdicaoRequest extends FormRequest
{
    /**
     * Determina se o pedido pode ser processado.
     *
     * A autorização da operação é realizada pelo controlador através da
     * política da edição.
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
     * Normaliza os dados antes da validação.
     *
     * O nome é reduzido a uma única linha, com espaços consecutivos
     * convertidos num único espaço. As datas são limpas de espaços
     * exteriores e uma data de fim vazia é convertida para nulo.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => $this->normalizarNome(
                $this->input(
                    'nome',
                ),
            ),

            'data_inicio' => $this->normalizarData(
                $this->input(
                    'data_inicio',
                ),
            ),

            'data_fim' => $this->normalizarData(
                $this->input(
                    'data_fim',
                ),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * As datas são recebidas no formato utilizado pelos campos HTML de data:
     * AAAA-MM-DD.
     *
     * O período é validado nesta camada e novamente pelo modelo
     * {@see Edicao}.
     *
     * @return array<string, list<string|Closure|Unique>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function rules(): array
    {
        return [
            'nome' => [
                'bail',
                'required',
                'string',
                'max:'.Edicao::COMPRIMENTO_MAXIMO_NOME,

                /**
                 * Confirma que o nome contém texto UTF-8 válido e não possui
                 * caracteres de controlo.
                 *
                 * @param  string  $atributo  Nome do atributo.
                 * @param  mixed  $valor  Valor recebido.
                 * @param  Closure(string): void  $falhar  Função de erro.
                 *
                 * @since 2.0.0
                 *
                 * @version 1.0.0
                 */
                static function (
                    string $atributo,
                    mixed $valor,
                    Closure $falhar,
                ): void {
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
                            'O nome da edição contém texto inválido.',
                        );

                        return;
                    }

                    if (
                        preg_match(
                            '/[\x00-\x1F\x7F]/',
                            $valor,
                        ) === 1
                    ) {
                        $falhar(
                            'O nome da edição contém caracteres inválidos.',
                        );
                    }
                },

                $this->obterRegraUnicidadeNome(),
            ],

            'data_inicio' => [
                'bail',
                'required',
                'date_format:Y-m-d',
            ],

            'data_fim' => [
                'bail',
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:data_inicio',
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
            'nome.required' => 'Por favor, insere o nome da edição.',

            'nome.string' => 'O nome da edição deve ser uma sequência de caracteres.',

            'nome.max' => sprintf(
                'O nome da edição não pode ter mais de %d caracteres.',
                Edicao::COMPRIMENTO_MAXIMO_NOME,
            ),

            'nome.unique' => 'Já existe uma edição com esse nome.',

            'data_inicio.required' => 'Por favor, insere a data de início da edição.',

            'data_inicio.date_format' => 'A data de início da edição deve ser uma data válida no formato AAAA-MM-DD.',

            'data_fim.date_format' => 'A data de fim da edição deve ser uma data válida no formato AAAA-MM-DD.',

            'data_fim.after_or_equal' => 'A data de fim não pode ser anterior à data de início.',
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
            'nome' => 'nome da edição',

            'data_inicio' => 'data de início',

            'data_fim' => 'data de fim',
        ];
    }

    /**
     * Obtém a regra de unicidade aplicável ao nome.
     *
     * A criação e a atualização diferem apenas na edição que deve ser
     * ignorada pela regra.
     *
     * @return Unique Regra de unicidade.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    abstract protected function obterRegraUnicidadeNome(): Unique;

    /**
     * Normaliza o nome da edição.
     *
     * Valores que não sejam strings permanecem inalterados para que as regras
     * de tipo produzam a respetiva mensagem de validação.
     *
     * Quando a substituição falha devido a texto inválido, o valor original é
     * devolvido e será rejeitado pela regra adicional de UTF-8.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Nome normalizado ou valor original.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function normalizarNome(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        $nome = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                $valor,
            ),
        );

        return is_string($nome)
            ? $nome
            : $valor;
    }

    /**
     * Normaliza uma data recebida.
     *
     * Uma string vazia é convertida para nulo, permitindo que a regra
     * `nullable` seja aplicada à data de fim.
     *
     * Valores que não sejam strings permanecem inalterados para que as regras
     * de validação os rejeitem.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Data normalizada ou valor original.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function normalizarData(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        $data = trim(
            $valor,
        );

        return $data !== ''
            ? $data
            : null;
    }
}
