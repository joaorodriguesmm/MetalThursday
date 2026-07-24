<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Models\Musica\Genero;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use LogicException;

/**
 * Valida os dados necessários para atualizar um género musical.
 *
 * Impede que o próprio género ou qualquer um dos seus descendentes seja
 * selecionado como género pai, evitando ciclos na hierarquia.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class AtualizarGeneroRequest extends FormRequest
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
     * Quando não são enviados géneros pais, é criada uma lista vazia para
     * permitir a remoção de todas as relações existentes.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => $this->normalizarNome(
                $this->input('nome'),
            ),

            'generos_pai' => $this->normalizarIdentificadores(
                $this->input(
                    'generos_pai',
                    [],
                ),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * O género atual é ignorado na regra de unicidade. Géneros eliminados
     * logicamente não impedem a reutilização do respetivo nome.
     *
     * @return array<string, array<int, mixed>> Regras de validação.
     *
     * @throws LogicException Quando a rota não contém um género válido.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function rules(): array
    {
        $genero = $this->obterGeneroDaRota();

        $identificadoresProibidos =
            $genero
                ->obterIdentificadoresComDescendentes();

        return [
            'nome' => [
                'bail',
                'required',
                'string',
                'max:255',

                Rule::unique(
                    Genero::class,
                    'nome',
                )
                    ->ignore($genero)
                    ->whereNull('deleted_at'),
            ],

            'generos_pai' => [
                'present',
                'array',
                'list',
            ],

            'generos_pai.*' => [
                'bail',
                'integer',
                'distinct:strict',

                Rule::exists(
                    Genero::class,
                    'id',
                )->whereNull(
                    'deleted_at',
                ),

                Rule::notIn(
                    $identificadoresProibidos,
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
     *
     * @version 2.0.0
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'Por favor, insere o nome do género.',

            'nome.string' => 'O nome do género deve ser uma sequência de caracteres.',

            'nome.max' => 'O nome do género não pode ter mais de 255 caracteres.',

            'nome.unique' => 'Já existe um género com esse nome.',

            'generos_pai.present' => 'Não foi recebida a lista de géneros pais.',

            'generos_pai.array' => 'Os géneros pais devem ser enviados numa lista.',

            'generos_pai.list' => 'A lista de géneros pais não tem um formato válido.',

            'generos_pai.*.integer' => 'Um dos géneros pais selecionados não é válido.',

            'generos_pai.*.distinct' => 'O mesmo género pai foi selecionado mais do que uma vez.',

            'generos_pai.*.exists' => 'Um dos géneros pais selecionados não existe.',

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
     * Obtém o género associado ao parâmetro da rota.
     *
     * @return Genero Género que será atualizado.
     *
     * @throws LogicException Quando a rota não contém um género válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterGeneroDaRota(): Genero
    {
        $genero = $this->route('genero');

        if (! $genero instanceof Genero) {
            throw new LogicException(
                'A rota não contém um género válido.',
            );
        }

        return $genero;
    }

    /**
     * Normaliza o nome do género.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Nome normalizado ou valor original.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
            trim($valor),
        );

        return is_string($nome)
            ? $nome
            : trim($valor);
    }

    /**
     * Normaliza uma lista de identificadores.
     *
     * Valores numéricos são convertidos para inteiros. Os restantes valores
     * são preservados para que a validação os possa rejeitar.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Lista normalizada ou valor original.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarIdentificadores(
        mixed $valor,
    ): mixed {
        if (! is_array($valor)) {
            return $valor;
        }

        return array_map(
            static function (
                mixed $identificador,
            ): mixed {
                if (
                    is_string($identificador)
                    && ctype_digit($identificador)
                ) {
                    return (int) $identificador;
                }

                return $identificador;
            },
            $valor,
        );
    }
}
