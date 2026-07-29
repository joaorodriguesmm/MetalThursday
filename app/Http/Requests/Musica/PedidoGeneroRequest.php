<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Models\Musica\Genero;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Define a validação comum dos pedidos de criação e atualização de géneros.
 *
 * O campo `generos_pai` deve estar explicitamente presente, podendo conter
 * uma lista vazia quando o género não possui relações hierárquicas.
 *
 * Os limites persistidos pertencem ao modelo {@see Genero}.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
abstract class PedidoGeneroRequest extends FormRequest
{
    /**
     * Determina se o pedido pode ser processado.
     *
     * A autorização da operação é realizada pelo controlador através da
     * política do género.
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
     * O campo `generos_pai` apenas é normalizado quando foi efetivamente
     * recebido. Desta forma, a regra `present` continua a distinguir um campo
     * omitido de uma lista vazia.
     *
     * Uma posição vazia utilizada pelo formulário para representar a ausência
     * de géneros pais é removida da lista.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $dadosNormalizados = [
            'nome' => $this->normalizarNome(
                $this->input(
                    'nome',
                ),
            ),
        ];

        if (
            array_key_exists(
                'generos_pai',
                $this->all(),
            )
        ) {
            $dadosNormalizados['generos_pai'] =
                $this->normalizarIdentificadores(
                    $this->input(
                        'generos_pai',
                    ),
                );
        }

        $this->merge(
            $dadosNormalizados,
        );
    }

    /**
     * Obtém as regras de validação.
     *
     * Os géneros eliminados logicamente não podem ser selecionados como pais.
     * As regras específicas da atualização podem acrescentar restrições
     * destinadas a impedir ciclos na hierarquia.
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
            'nome' => [
                'bail',
                'required',
                'string',
                $this->criarRegraNome(),
                'max:'.Genero::COMPRIMENTO_MAXIMO_NOME,
                $this->obterRegraUnicidadeNome(),
            ],

            'generos_pai' => [
                'bail',
                'present',
                'array',
                'list',
            ],

            'generos_pai.*' => [
                'bail',
                'required',
                'integer',
                'distinct:strict',

                Rule::exists(
                    Genero::class,
                    'id',
                )->whereNull(
                    'deleted_at',
                ),

                ...$this->obterRegrasAdicionaisGenerosPai(),
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
            'nome.required' => 'Por favor, insere o nome do género.',

            'nome.string' => 'O nome do género deve ser uma sequência de caracteres.',

            'nome.max' => sprintf(
                'O nome do género não pode ter mais de %d caracteres.',
                Genero::COMPRIMENTO_MAXIMO_NOME,
            ),

            'nome.unique' => 'Já existe um género com esse nome.',

            'generos_pai.present' => 'Não foi recebida a lista de géneros pais.',

            'generos_pai.array' => 'Os géneros pais devem ser enviados numa lista.',

            'generos_pai.list' => 'A lista de géneros pais não tem um formato válido.',

            'generos_pai.*.required' => 'Um dos géneros pais selecionados não é válido.',

            'generos_pai.*.integer' => 'Um dos géneros pais selecionados não é válido.',

            'generos_pai.*.distinct' => 'O mesmo género pai foi selecionado mais do que uma vez.',

            'generos_pai.*.exists' => 'Um dos géneros pais selecionados não existe ou não está disponível.',

            'generos_pai.*.not_in' => 'Um género não pode ter como pai o próprio género nem um dos seus descendentes.',
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
            'nome' => 'nome do género',

            'generos_pai' => 'géneros pais',

            'generos_pai.*' => 'género pai',
        ];
    }

    /**
     * Obtém a regra de unicidade aplicável ao nome.
     *
     * A criação e a atualização diferem apenas no género que deve ser
     * ignorado pela regra.
     *
     * @return Unique Regra de unicidade.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    abstract protected function obterRegraUnicidadeNome(): Unique;

    /**
     * Obtém as regras adicionais aplicáveis aos géneros pais.
     *
     * A criação não possui restrições adicionais. A atualização acrescenta
     * os identificadores que originariam um ciclo na hierarquia.
     *
     * @return list<mixed> Regras adicionais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function obterRegrasAdicionaisGenerosPai(): array
    {
        return [];
    }

    /**
     * Cria a regra adicional de validação do nome.
     *
     * O nome deve conter texto UTF-8 válido e não pode possuir caracteres de
     * controlo.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarRegraNome(): Closure
    {
        return static function (
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
                    'O nome do género contém texto inválido.',
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
                    'O nome do género contém caracteres inválidos.',
                );
            }
        };
    }

    /**
     * Normaliza o nome do género.
     *
     * Os espaços exteriores são removidos e qualquer sequência de espaços
     * interiores é convertida num único espaço.
     *
     * Quando o texto não é UTF-8 válido, o valor original é preservado para
     * que a regra adicional o rejeite.
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
     * Normaliza uma lista de identificadores.
     *
     * Strings vazias e valores nulos são removidos para suportar o campo
     * oculto utilizado pelo formulário quando não existem géneros pais
     * selecionados.
     *
     * A estrutura original é preservada quando o valor recebido não é uma
     * lista, permitindo que a regra `list` o rejeite.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Lista normalizada ou valor original.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function normalizarIdentificadores(
        mixed $valor,
    ): mixed {
        if (! is_array($valor)) {
            return $valor;
        }

        $valorEraLista =
            array_is_list(
                $valor,
            );

        $identificadores = [];

        foreach ($valor as $indice => $identificador) {
            if (
                $identificador === null
                || $identificador === ''
            ) {
                continue;
            }

            $identificadores[$indice] =
                $this->normalizarIdentificador(
                    $identificador,
                );
        }

        return $valorEraLista
            ? array_values(
                $identificadores,
            )
            : $identificadores;
    }

    /**
     * Normaliza um identificador.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Identificador normalizado ou valor original.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarIdentificador(
        mixed $valor,
    ): mixed {
        if (
            is_string($valor)
            && ctype_digit($valor)
        ) {
            return (int) $valor;
        }

        return $valor;
    }
}
