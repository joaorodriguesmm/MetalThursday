<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida os dados que podem ser persistidos num rascunho de MetalThursday.
 *
 * Ao contrário da validação da MetalThursday final, um rascunho pode estar
 * incompleto. As regras garantem apenas uma estrutura conhecida, limites
 * seguros e referências válidas quando os identificadores são fornecidos.
 *
 * A data, o autor e a edição não fazem parte do rascunho porque continuam a
 * ser determinados pela reserva e pelo domínio da MetalThursday.
 *
 * @since 2.0.0
 */
final class GuardarRascunhoMetalThursdayRequest extends FormRequest
{
    /**
     * Número máximo de secções aceite num rascunho.
     *
     * Mantém o mesmo limite funcional da submissão definitiva.
     *
     * @since 2.0.0
     */
    private const NUMERO_MAXIMO_SECCOES = 50;

    /**
     * Determina se o utilizador autenticado pode guardar o rascunho.
     *
     * A rota já possui middleware equivalente, mas esta validação mantém a
     * autorização também junto da própria entrada HTTP.
     *
     * @return bool Verdadeiro quando a reserva continua disponível para o
     *              respetivo responsável.
     *
     * @since 2.0.0
     */
    public function authorize(): bool
    {
        $utilizador = $this->user(
            'sessao',
        );

        $reserva = $this->route(
            'reservaMetalThursday',
        );

        if (
            ! $utilizador instanceof Utilizador
            || ! $reserva instanceof ReservaMetalThursday
            || ! $reserva->estaPendente()
            || ! is_numeric(
                $reserva->responsavel_id,
            )
            || (int) $reserva->responsavel_id
            !== (int) $utilizador->getKey()
        ) {
            return false;
        }

        return $utilizador->can(
            'create',
            MetalThursday::class,
        );
    }

    /**
     * Normaliza os valores recebidos antes da validação.
     *
     * Os rascunhos preservam conteúdo incompleto, mas utilizam a mesma
     * normalização básica dos identificadores e textos da submissão final.
     *
     * @since 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => $this->normalizarTextoLinhaOpcional(
                $this->input(
                    'nome',
                ),
            ),

            'proximo_nomeado_id' => $this->normalizarIdentificador(
                $this->input(
                    'proximo_nomeado_id',
                ),
            ),

            'seccoes' => $this->normalizarSeccoes(
                $this->input(
                    'seccoes',
                    [],
                ),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação do rascunho.
     *
     * Não são aplicadas regras de completude, compatibilidade entre campos ou
     * obrigatoriedade dependente do tipo da secção. Essas regras pertencem à
     * finalização da MetalThursday.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 2.0.0
     */
    public function rules(): array
    {
        return [
            'nome' => [
                'bail',
                'nullable',
                'string',
                $this->criarRegraTextoLinha(
                    'O nome contém texto inválido.',
                    'O nome contém caracteres inválidos.',
                ),
                'max:'.MetalThursday::COMPRIMENTO_MAXIMO_NOME,
            ],

            'proximo_nomeado_id' => [
                'bail',
                'nullable',
                'integer',
                Rule::exists(
                    Utilizador::class,
                    'id',
                ),
            ],

            'seccoes' => [
                'bail',
                'present',
                'array',
                'list',
                'max:'.self::NUMERO_MAXIMO_SECCOES,
            ],

            'seccoes.*' => [
                'bail',
                'array:id,tipo_seccao_id,titulo,descricao,banda_id,ligacao,tipo_incorporacao,ano',
            ],

            'seccoes.*.id' => [
                'nullable',
                'prohibited',
            ],

            'seccoes.*.tipo_seccao_id' => [
                'bail',
                'nullable',
                'integer',
                Rule::exists(
                    TipoSeccao::class,
                    'id',
                ),
            ],

            'seccoes.*.titulo' => [
                'bail',
                'nullable',
                'string',
                $this->criarRegraTextoLinha(
                    'O título da secção contém texto inválido.',
                    'O título da secção contém caracteres inválidos.',
                ),
                'max:'.SeccaoMetalThursday::COMPRIMENTO_MAXIMO_TITULO,
            ],

            'seccoes.*.descricao' => [
                'bail',
                'nullable',
                'string',
                $this->criarRegraTextoMultilinha(
                    'A descrição da secção contém texto inválido.',
                    'A descrição da secção contém caracteres inválidos.',
                ),
                'max:'.SeccaoMetalThursday::COMPRIMENTO_MAXIMO_DESCRICAO,
            ],

            'seccoes.*.banda_id' => [
                'bail',
                'nullable',
                'integer',
                Rule::exists(
                    Banda::class,
                    'id',
                )->whereNull(
                    'deleted_at',
                ),
            ],

            'seccoes.*.ligacao' => [
                'bail',
                'nullable',
                'string',
                $this->criarRegraTextoLinha(
                    'A ligação da secção contém texto inválido.',
                    'A ligação da secção contém caracteres inválidos.',
                ),
                'max:'.SeccaoMetalThursday::COMPRIMENTO_MAXIMO_LIGACAO,
            ],

            'seccoes.*.tipo_incorporacao' => [
                'bail',
                'nullable',
                Rule::enum(
                    TipoIncorporacao::class,
                ),
            ],

            'seccoes.*.ano' => [
                'bail',
                'nullable',
                'integer',
            ],
        ];
    }

    /**
     * Obtém as mensagens específicas da validação do rascunho.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 2.0.0
     */
    public function messages(): array
    {
        return [
            'nome.string' => 'O nome deve ser uma sequência de caracteres.',
            'nome.max' => sprintf(
                'O nome não pode ter mais de %d caracteres.',
                MetalThursday::COMPRIMENTO_MAXIMO_NOME,
            ),

            'proximo_nomeado_id.integer' => 'O próximo nomeado selecionado não é válido.',

            'proximo_nomeado_id.exists' => 'O próximo nomeado selecionado não existe.',

            'seccoes.present' => 'Não foi possível determinar as secções do rascunho.',

            'seccoes.array' => 'As secções devem ser enviadas numa lista.',

            'seccoes.list' => 'A lista de secções não tem um formato válido.',

            'seccoes.max' => sprintf(
                'Um rascunho não pode possuir mais de %d secções.',
                self::NUMERO_MAXIMO_SECCOES,
            ),

            'seccoes.*.array' => 'Uma das secções não tem um formato válido.',

            'seccoes.*.id.prohibited' => 'Um rascunho de uma nova MetalThursday não pode referenciar secções existentes.',

            'seccoes.*.tipo_seccao_id.integer' => 'O tipo de uma das secções não é válido.',

            'seccoes.*.tipo_seccao_id.exists' => 'O tipo de uma das secções não existe.',

            'seccoes.*.titulo.string' => 'O título da secção deve ser uma sequência de caracteres.',

            'seccoes.*.titulo.max' => sprintf(
                'O título da secção não pode ter mais de %d caracteres.',
                SeccaoMetalThursday::COMPRIMENTO_MAXIMO_TITULO,
            ),

            'seccoes.*.descricao.string' => 'A descrição da secção deve ser uma sequência de caracteres.',

            'seccoes.*.descricao.max' => sprintf(
                'A descrição da secção não pode ter mais de %d caracteres.',
                SeccaoMetalThursday::COMPRIMENTO_MAXIMO_DESCRICAO,
            ),

            'seccoes.*.banda_id.integer' => 'A banda selecionada não é válida.',

            'seccoes.*.banda_id.exists' => 'A banda selecionada não existe ou não está disponível.',

            'seccoes.*.ligacao.string' => 'A ligação da secção não é válida.',

            'seccoes.*.ligacao.max' => sprintf(
                'A ligação da secção não pode ter mais de %d caracteres.',
                SeccaoMetalThursday::COMPRIMENTO_MAXIMO_LIGACAO,
            ),

            'seccoes.*.tipo_incorporacao.enum' => 'O tipo de incorporação selecionado não é válido.',

            'seccoes.*.ano.integer' => 'O ano deve ser um número inteiro.',
        ];
    }

    /**
     * Normaliza as secções recebidas.
     *
     * Campos desconhecidos são preservados nesta fase para que a regra de
     * validação da estrutura os possa rejeitar explicitamente.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Secções normalizadas ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarSeccoes(
        mixed $valor,
    ): mixed {
        if (! is_array($valor)) {
            return $valor;
        }

        $seccoes = [];

        foreach (array_values($valor) as $seccao) {
            if (! is_array($seccao)) {
                $seccoes[] =
                    $seccao;

                continue;
            }

            $seccao['id'] = $this->normalizarIdentificador(
                $seccao['id']
                    ?? null,
            );

            $seccao['tipo_seccao_id'] = $this->normalizarIdentificador(
                $seccao['tipo_seccao_id']
                    ?? null,
            );

            $seccao['titulo'] = $this->normalizarTextoLinhaOpcional(
                $seccao['titulo']
                    ?? null,
            );

            $seccao['descricao'] = $this->normalizarTextoMultilinha(
                $seccao['descricao']
                    ?? null,
            );

            $seccao['banda_id'] = $this->normalizarIdentificador(
                $seccao['banda_id']
                    ?? null,
            );

            $seccao['ligacao'] = $this->normalizarTextoOpcional(
                $seccao['ligacao']
                    ?? null,
            );

            $seccao['tipo_incorporacao'] = $this->normalizarTextoOpcional(
                $seccao['tipo_incorporacao']
                    ?? null,
            );

            $seccao['ano'] = $this->normalizarIdentificador(
                $seccao['ano']
                    ?? null,
            );

            $seccoes[] =
                $seccao;
        }

        return $seccoes;
    }

    /**
     * Normaliza um identificador ou valor inteiro.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Valor normalizado ou original.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificador(
        mixed $valor,
    ): mixed {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (
            is_string($valor)
            && ctype_digit($valor)
        ) {
            return (int) $valor;
        }

        return $valor;
    }

    /**
     * Normaliza um texto opcional de uma única linha.
     *
     * Caracteres de controlo são preservados para poderem ser rejeitados pela
     * validação, em vez de serem removidos silenciosamente.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Texto normalizado ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarTextoLinhaOpcional(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        if (
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $valor,
            ) === 1
        ) {
            return $valor;
        }

        $texto = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                $valor,
            ),
        );

        if (! is_string($texto)) {
            return $valor;
        }

        return $texto !== ''
            ? $texto
            : null;
    }

    /**
     * Normaliza um texto opcional.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Texto normalizado ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarTextoOpcional(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        $texto = trim(
            $valor,
            ' ',
        );

        return $texto !== ''
            ? $texto
            : null;
    }

    /**
     * Normaliza um texto com várias linhas.
     *
     * Tabulações e quebras de linha são preservadas. Os restantes caracteres
     * de controlo permanecem disponíveis para a regra de validação.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Texto normalizado ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarTextoMultilinha(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $valor,
            ) === 1
        ) {
            return $valor;
        }

        $texto = trim(
            str_replace(
                [
                    "\r\n",
                    "\r",
                ],
                "\n",
                $valor,
            ),
            " \t\n",
        );

        return $texto !== ''
            ? $texto
            : null;
    }

    /**
     * Cria uma regra para texto de uma única linha.
     *
     * @param  string  $mensagemTextoInvalido  Mensagem para UTF-8 inválido.
     * @param  string  $mensagemCaracteresInvalidos  Mensagem para caracteres
     *                                               de controlo.
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     */
    private function criarRegraTextoLinha(
        string $mensagemTextoInvalido,
        string $mensagemCaracteresInvalidos,
    ): Closure {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ) use (
            $mensagemTextoInvalido,
            $mensagemCaracteresInvalidos,
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
                    $mensagemTextoInvalido,
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
                    $mensagemCaracteresInvalidos,
                );
            }
        };
    }

    /**
     * Cria uma regra para texto com várias linhas.
     *
     * Tabulações e quebras de linha são permitidas.
     *
     * @param  string  $mensagemTextoInvalido  Mensagem para UTF-8 inválido.
     * @param  string  $mensagemCaracteresInvalidos  Mensagem para caracteres
     *                                               de controlo.
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     */
    private function criarRegraTextoMultilinha(
        string $mensagemTextoInvalido,
        string $mensagemCaracteresInvalidos,
    ): Closure {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ) use (
            $mensagemTextoInvalido,
            $mensagemCaracteresInvalidos,
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
                    $mensagemTextoInvalido,
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
                    $mensagemCaracteresInvalidos,
                );
            }
        };
    }
}
