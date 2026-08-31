<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Define a validação comum dos pedidos de criação e atualização de artistas.
 *
 * A origem geográfica e os géneros são recebidos através dos respetivos
 * identificadores. Os limites persistidos pertencem aos modelos
 * correspondentes.
 *
 * @since 2.0.0
 */
abstract class PedidoArtistaRequest extends FormRequest
{
    /**
     * Normaliza os dados antes da validação.
     *
     * O nome é normalizado de acordo com o contrato do modelo, desde que não
     * contenha texto ou caracteres que devam ser rejeitados pela validação.
     * Os identificadores numéricos são convertidos para inteiros e a estrutura
     * original da lista de géneros é preservada para que a regra `list` possa
     * detetar chaves inválidas.
     *
     * @since 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => $this->normalizarNome(
                $this->input(
                    'nome',
                ),
            ),

            'origem_geografica_id' => $this->normalizarIdentificador(
                $this->input(
                    'origem_geografica_id',
                ),
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
     * Os artistas e os géneros eliminados logicamente não participam nos
     * contratos de unicidade e existência dos registos ativos.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 1.0.0
     */
    public function rules(): array
    {
        return [
            'nome' => [
                'bail',
                'required',
                'string',
                $this->criarRegraNome(),
                'max:'.Artista::COMPRIMENTO_MAXIMO_NOME,
                $this->obterRegraUnicidadeNome(),
            ],

            'origem_geografica_id' => [
                'bail',
                'required',
                'integer',

                Rule::exists(
                    OrigemGeografica::class,
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
                'required',
                'integer',
                'distinct:strict',

                Rule::exists(
                    Genero::class,
                    'id',
                )->whereNull(
                    'deleted_at',
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
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'Por favor, insere o nome do artista.',

            'nome.string' => 'O nome do artista deve ser uma sequência de caracteres.',

            'nome.max' => sprintf(
                'O nome do artista não pode ter mais de %d caracteres.',
                Artista::COMPRIMENTO_MAXIMO_NOME,
            ),

            'nome.unique' => 'Já existe um artista com esse nome.',

            'origem_geografica_id.required' => 'Por favor, seleciona a origem geográfica do artista.',

            'origem_geografica_id.integer' => 'A origem geográfica selecionada não é válida.',

            'origem_geografica_id.exists' => 'A origem geográfica selecionada não existe.',

            'generos.required' => 'Por favor, seleciona pelo menos um género.',

            'generos.array' => 'Os géneros devem ser enviados numa lista.',

            'generos.list' => 'A lista de géneros não tem um formato válido.',

            'generos.min' => 'Por favor, seleciona pelo menos um género.',

            'generos.*.required' => 'Um dos géneros selecionados não é válido.',

            'generos.*.integer' => 'Um dos géneros selecionados não é válido.',

            'generos.*.distinct' => 'O mesmo género foi selecionado mais do que uma vez.',

            'generos.*.exists' => 'Um dos géneros selecionados não existe ou não está disponível.',
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
            'nome' => 'nome do artista',

            'origem_geografica_id' => 'origem geográfica',

            'generos' => 'géneros',

            'generos.*' => 'género',
        ];
    }

    /**
     * Obtém a regra de unicidade aplicável ao nome.
     *
     * A criação e a atualização diferem apenas no artista que deve ser
     * ignorado pela regra.
     *
     * @return Unique Regra de unicidade.
     *
     * @since 2.0.0
     */
    abstract protected function obterRegraUnicidadeNome(): Unique;

    /**
     * Cria a regra adicional de validação do nome.
     *
     * O nome deve conter texto UTF-8 válido e não pode possuir caracteres de
     * controlo.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
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
                    'O nome do artista contém texto inválido.',
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
                    'O nome do artista contém caracteres inválidos.',
                );
            }
        };
    }

    /**
     * Normaliza o nome do artista.
     *
     * Texto UTF-8 inválido ou com caracteres de controlo permanece inalterado
     * para ser rejeitado pelas regras de validação. Nos restantes casos é
     * aplicada a mesma normalização utilizada pelo modelo {@see Artista}.
     *
     * Valores que não sejam strings permanecem igualmente inalterados para
     * que as regras de tipo produzam a respetiva mensagem.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Nome normalizado ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarNome(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        if (
            preg_match(
                '//u',
                $valor,
            ) !== 1
            || preg_match(
                '/[\x00-\x1F\x7F]/',
                $valor,
            ) === 1
        ) {
            return $valor;
        }

        return Str::squish(
            $valor,
        );
    }

    /**
     * Normaliza um identificador recebido.
     *
     * Uma string composta exclusivamente por algarismos é convertida para
     * inteiro. Os restantes valores são preservados para que sejam rejeitados
     * pelas regras de validação.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Identificador normalizado ou valor original.
     *
     * @since 2.0.0
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
     * A estrutura e as chaves da lista são preservadas para que a regra
     * `list` consiga rejeitar listas associativas ou com índices em falta.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Lista normalizada ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificadores(
        mixed $valor,
    ): mixed {
        if (! is_array($valor)) {
            return $valor;
        }

        $identificadores = [];

        foreach ($valor as $indice => $identificador) {
            $identificadores[$indice] =
                $this->normalizarIdentificador(
                    $identificador,
                );
        }

        return $identificadores;
    }
}
