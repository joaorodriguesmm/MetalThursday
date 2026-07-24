<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Models\Geografia\Pais;
use App\Models\Musica\Banda;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use LogicException;

/**
 * Valida os dados necessários para atualizar uma banda.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class AtualizarBandaRequest extends FormRequest
{
    /**
     * Determina se o pedido pode ser processado.
     *
     * A autorização da operação é realizada pelo controlador através da
     * política da banda.
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

            'pais_id' => $this->normalizarIdentificador(
                $this->input('pais_id'),
            ),

            'generos' => $this->normalizarIdentificadores(
                $this->input(
                    'generos',
                    [],
                ),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * O nome da própria banda é ignorado na verificação de unicidade. Bandas
     * eliminadas logicamente não impedem a reutilização do nome.
     *
     * @return array<string, array<int, mixed>> Regras de validação.
     *
     * @throws LogicException Quando a rota não contém uma banda válida.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function rules(): array
    {
        $banda = $this->obterBandaDaRota();

        return [
            'nome' => [
                'bail',
                'required',
                'string',
                'max:255',

                Rule::unique(
                    Banda::class,
                    'nome',
                )
                    ->ignore($banda)
                    ->whereNull('deleted_at'),
            ],

            'pais_id' => [
                'bail',
                'required',
                'integer',

                Rule::exists(
                    Pais::class,
                    'id',
                ),
            ],

            'generos' => [
                'bail',
                'required',
                'array',
                'list',
                'min:1',
            ],

            'generos.*' => [
                'bail',
                'integer',
                'distinct:strict',

                Rule::exists(
                    Genero::class,
                    'id',
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
            'nome.required' => 'Por favor, insere o nome da banda.',

            'nome.string' => 'O nome da banda deve ser uma sequência de caracteres.',

            'nome.max' => 'O nome da banda não pode ter mais de 255 caracteres.',

            'nome.unique' => 'Já existe uma banda com esse nome.',

            'pais_id.required' => 'Por favor, seleciona o país da banda.',

            'pais_id.integer' => 'O país selecionado não é válido.',

            'pais_id.exists' => 'O país selecionado não existe.',

            'generos.required' => 'Por favor, seleciona pelo menos um género.',

            'generos.array' => 'Os géneros devem ser enviados numa lista.',

            'generos.list' => 'A lista de géneros não tem um formato válido.',

            'generos.min' => 'Por favor, seleciona pelo menos um género.',

            'generos.*.integer' => 'Um dos géneros selecionados não é válido.',

            'generos.*.distinct' => 'O mesmo género foi selecionado mais do que uma vez.',

            'generos.*.exists' => 'Um dos géneros selecionados não existe.',
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
            'nome' => 'nome da banda',

            'pais_id' => 'país',

            'generos' => 'géneros',

            'generos.*' => 'género',
        ];
    }

    /**
     * Obtém a banda associada ao parâmetro da rota.
     *
     * @return Banda Banda que será atualizada.
     *
     * @throws LogicException Quando a rota não contém uma banda válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterBandaDaRota(): Banda
    {
        $banda = $this->route('banda');

        if (! $banda instanceof Banda) {
            throw new LogicException(
                'A rota não contém uma banda válida.',
            );
        }

        return $banda;
    }

    /**
     * Normaliza o nome da banda.
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
     * Normaliza um identificador recebido.
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

    /**
     * Normaliza uma lista de identificadores.
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
            fn (mixed $identificador): mixed => $this->normalizarIdentificador(
                $identificador,
            ),
            $valor,
        );
    }
}
