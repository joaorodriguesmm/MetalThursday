<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Query\Builder as ConstrutorConsulta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use LogicException;

/**
 * Valida os dados necessários para criar ou atualizar uma MetalThursday.
 *
 * Valida também a estrutura das secções, a pertença das secções existentes à
 * MetalThursday atual, o intervalo da edição e os campos exigidos por cada
 * tipo de secção.
 *
 * A validação HTTP apresenta mensagens adequadas ao utilizador. Os modelos e
 * o serviço de persistência voltam a proteger os mesmos contratos antes da
 * escrita na base de dados.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class GuardarMetalThursdayRequest extends FormRequest
{
    /**
     * Número máximo de secções aceite numa única submissão.
     *
     * Este limite protege o pedido HTTP e a operação transacional de cargas
     * excessivas. Não representa o limite físico de uma coluna da base de
     * dados.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private const NUMERO_MAXIMO_SECCOES = 50;

    /**
     * Determina se o pedido pode ser processado.
     *
     * A autorização da operação é realizada pelo controlador através da
     * política da MetalThursday.
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
     * Os identificadores numéricos são convertidos para inteiros, os textos
     * opcionais vazios são convertidos para nulo e as secções são
     * reindexadas pela respetiva ordem no formulário.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'edicao_id' => $this->normalizarIdentificador(
                $this->input(
                    'edicao_id',
                ),
            ),

            'data' => $this->normalizarTextoOpcional(
                $this->input(
                    'data',
                ),
            ),

            'nome' => $this->normalizarTextoLinhaOpcional(
                $this->input(
                    'nome',
                ),
            ),

            'autor_id' => $this->normalizarIdentificador(
                $this->input(
                    'autor_id',
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
     * Obtém as regras de validação.
     *
     * A data é única em toda a aplicação. Durante uma atualização, a
     * MetalThursday atual é ignorada nessa verificação.
     *
     * Os identificadores das secções existentes apenas são aceites quando
     * pertencem à MetalThursday que está a ser atualizada.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function rules(): array
    {
        $metalThursday =
            $this->obterMetalThursdayDaRota();

        $regraData = Rule::unique(
            MetalThursday::class,
            'data',
        );

        if ($metalThursday instanceof MetalThursday) {
            $regraData->ignore(
                $metalThursday,
            );
        }

        $regrasIdentificadorSeccao = [
            'nullable',
            'integer',
        ];

        if ($metalThursday instanceof MetalThursday) {
            $regrasIdentificadorSeccao[] = Rule::exists(
                SeccaoMetalThursday::class,
                'id',
            )->where(
                static fn (
                    ConstrutorConsulta $construtor,
                ): ConstrutorConsulta => $construtor
                    ->where(
                        'metal_thursday_id',
                        $metalThursday->getKey(),
                    )
                    ->whereNull(
                        'deleted_at',
                    ),
            );
        } else {
            $regrasIdentificadorSeccao[] =
                'prohibited';
        }

        return [
            'edicao_id' => [
                'bail',
                'required',
                'integer',

                Rule::exists(
                    Edicao::class,
                    'id',
                )->whereNull(
                    'deleted_at',
                ),
            ],

            'data' => [
                'bail',
                'required',
                'date_format:Y-m-d',
                $regraData,
            ],

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

            'autor_id' => [
                'bail',
                'required',
                'integer',

                Rule::exists(
                    Utilizador::class,
                    'id',
                ),
            ],

            'proximo_nomeado_id' => [
                'bail',
                'required',
                'integer',

                Rule::exists(
                    Utilizador::class,
                    'id',
                ),
            ],

            'seccoes' => [
                'bail',
                'required',
                'array',
                'list',
                'min:1',
                'max:'.self::NUMERO_MAXIMO_SECCOES,
            ],

            'seccoes.*' => [
                'bail',
                'required',
                'array:id,tipo_secao_id,titulo,descricao,banda_id,ligacao,tipo_incorporacao,ano',
            ],

            'seccoes.*.id' => $regrasIdentificadorSeccao,

            'seccoes.*.tipo_secao_id' => [
                'bail',
                'required',
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
                'required',
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
                $this->criarRegraLigacao(),
                'url:http,https',
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
                'min:'.SeccaoMetalThursday::ANO_MINIMO,
                'max:'.SeccaoMetalThursday::ANO_MAXIMO,
            ],
        ];
    }

    /**
     * Obtém as validações executadas depois das regras principais.
     *
     * @return list<callable(Validator): void> Validações adicionais.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function after(): array
    {
        return [
            function (
                Validator $validador,
            ): void {
                $this->validarDataDentroDaEdicao(
                    $validador,
                );

                $this->validarSeccoes(
                    $validador,
                );
            },
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
            'edicao_id.required' => 'Por favor, seleciona uma edição.',

            'edicao_id.integer' => 'A edição selecionada não é válida.',

            'edicao_id.exists' => 'A edição selecionada não existe ou não está disponível.',

            'data.required' => 'Por favor, insere a data da MetalThursday.',

            'data.date_format' => 'A data da MetalThursday deve ser válida e utilizar o formato AAAA-MM-DD.',

            'data.unique' => 'Já existe uma MetalThursday na data selecionada.',

            'nome.string' => 'O nome deve ser uma sequência de caracteres.',

            'nome.max' => sprintf(
                'O nome não pode ter mais de %d caracteres.',
                MetalThursday::COMPRIMENTO_MAXIMO_NOME,
            ),

            'autor_id.required' => 'Por favor, seleciona o autor.',

            'autor_id.integer' => 'O autor selecionado não é válido.',

            'autor_id.exists' => 'O autor selecionado não existe.',

            'proximo_nomeado_id.required' => 'Por favor, seleciona o próximo nomeado.',

            'proximo_nomeado_id.integer' => 'O próximo nomeado selecionado não é válido.',

            'proximo_nomeado_id.exists' => 'O próximo nomeado selecionado não existe.',

            'seccoes.required' => 'Por favor, insere pelo menos uma secção.',

            'seccoes.array' => 'As secções devem ser enviadas numa lista.',

            'seccoes.list' => 'A lista de secções não tem um formato válido.',

            'seccoes.min' => 'Por favor, insere pelo menos uma secção.',

            'seccoes.max' => sprintf(
                'Uma MetalThursday não pode possuir mais de %d secções.',
                self::NUMERO_MAXIMO_SECCOES,
            ),

            'seccoes.*.required' => 'Uma das secções não foi recebida corretamente.',

            'seccoes.*.array' => 'Uma das secções não tem um formato válido.',

            'seccoes.*.id.integer' => 'O identificador de uma das secções não é válido.',

            'seccoes.*.id.exists' => 'Uma das secções não existe ou não pertence a esta MetalThursday.',

            'seccoes.*.id.prohibited' => 'Uma nova MetalThursday não pode receber secções existentes.',

            'seccoes.*.tipo_secao_id.required' => 'Por favor, seleciona o tipo da secção.',

            'seccoes.*.tipo_secao_id.integer' => 'O tipo de uma das secções não é válido.',

            'seccoes.*.tipo_secao_id.exists' => 'O tipo de uma das secções não existe.',

            'seccoes.*.titulo.string' => 'O título da secção deve ser uma sequência de caracteres.',

            'seccoes.*.titulo.max' => sprintf(
                'O título da secção não pode ter mais de %d caracteres.',
                SeccaoMetalThursday::COMPRIMENTO_MAXIMO_TITULO,
            ),

            'seccoes.*.descricao.required' => 'Por favor, insere a descrição da secção.',

            'seccoes.*.descricao.string' => 'A descrição da secção deve ser uma sequência de caracteres.',

            'seccoes.*.descricao.max' => sprintf(
                'A descrição da secção não pode ter mais de %d caracteres.',
                SeccaoMetalThursday::COMPRIMENTO_MAXIMO_DESCRICAO,
            ),

            'seccoes.*.banda_id.integer' => 'A banda selecionada não é válida.',

            'seccoes.*.banda_id.exists' => 'A banda selecionada não existe ou não está disponível.',

            'seccoes.*.ligacao.string' => 'A ligação da secção não é válida.',

            'seccoes.*.ligacao.url' => 'A ligação da secção deve ser um endereço HTTP ou HTTPS válido.',

            'seccoes.*.ligacao.max' => sprintf(
                'A ligação da secção não pode ter mais de %d caracteres.',
                SeccaoMetalThursday::COMPRIMENTO_MAXIMO_LIGACAO,
            ),

            'seccoes.*.tipo_incorporacao.enum' => 'O tipo de incorporação selecionado não é válido.',

            'seccoes.*.ano.integer' => 'O ano deve ser um número inteiro.',

            'seccoes.*.ano.min' => sprintf(
                'O ano não pode ser anterior a %d.',
                SeccaoMetalThursday::ANO_MINIMO,
            ),

            'seccoes.*.ano.max' => sprintf(
                'O ano não pode ser posterior a %d.',
                SeccaoMetalThursday::ANO_MAXIMO,
            ),
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function attributes(): array
    {
        return [
            'edicao_id' => 'edição',

            'data' => 'data',

            'nome' => 'nome',

            'autor_id' => 'autor',

            'proximo_nomeado_id' => 'próximo nomeado',

            'seccoes' => 'secções',

            'seccoes.*.id' => 'identificador da secção',

            'seccoes.*.tipo_secao_id' => 'tipo da secção',

            'seccoes.*.titulo' => 'título da secção',

            'seccoes.*.descricao' => 'descrição da secção',

            'seccoes.*.banda_id' => 'banda da secção',

            'seccoes.*.ligacao' => 'ligação da secção',

            'seccoes.*.tipo_incorporacao' => 'tipo de incorporação',

            'seccoes.*.ano' => 'ano da secção',
        ];
    }

    /**
     * Confirma que a data pertence ao intervalo da edição.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function validarDataDentroDaEdicao(
        Validator $validador,
    ): void {
        if (
            $validador
                ->errors()
                ->hasAny([
                    'edicao_id',
                    'data',
                ])
        ) {
            return;
        }

        $identificadorEdicao =
            $this->input(
                'edicao_id',
            );

        $data =
            $this->input(
                'data',
            );

        if (
            ! is_int($identificadorEdicao)
            || ! is_string($data)
        ) {
            return;
        }

        $edicao = Edicao::query()
            ->select([
                'id',
                'data_inicio',
                'data_fim',
            ])
            ->find(
                $identificadorEdicao,
            );

        if (! $edicao instanceof Edicao) {
            return;
        }

        $dataInicio =
            $edicao->data_inicio;

        $dataFim =
            $edicao->data_fim;

        if (
            $dataInicio instanceof CarbonInterface
            && $data < $dataInicio->format(
                'Y-m-d',
            )
        ) {
            $validador
                ->errors()
                ->add(
                    'data',
                    'A data da MetalThursday não pode ser anterior ao início da edição.',
                );

            return;
        }

        if (
            $dataFim instanceof CarbonInterface
            && $data > $dataFim->format(
                'Y-m-d',
            )
        ) {
            $validador
                ->errors()
                ->add(
                    'data',
                    'A data da MetalThursday não pode ser posterior ao fim da edição.',
                );
        }
    }

    /**
     * Valida as regras dependentes do tipo de cada secção.
     *
     * Os tipos que exigem detalhes requerem título, banda, ligação, tipo de
     * incorporação e ano. Os restantes tipos não podem guardar esses campos.
     * A descrição é obrigatória em todas as secções.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function validarSeccoes(
        Validator $validador,
    ): void {
        if (
            $validador
                ->errors()
                ->has(
                    'seccoes',
                )
        ) {
            return;
        }

        $seccoes =
            $this->input(
                'seccoes',
            );

        if (! is_array($seccoes)) {
            return;
        }

        $identificadoresTipos = [];

        foreach ($seccoes as $seccao) {
            if (
                is_array($seccao)
                && is_int(
                    $seccao['tipo_secao_id']
                        ?? null,
                )
            ) {
                $identificadoresTipos[] =
                    $seccao['tipo_secao_id'];
            }
        }

        $tipos = TipoSeccao::query()
            ->whereKey(
                array_values(
                    array_unique(
                        $identificadoresTipos,
                    ),
                ),
            )
            ->get()
            ->keyBy(
                static fn (
                    TipoSeccao $tipo,
                ): int => (int) $tipo->getKey(),
            );

        $identificadoresSeccoes = [];

        foreach ($seccoes as $indice => $seccao) {
            if (! is_array($seccao)) {
                continue;
            }

            $prefixo =
                'seccoes.'.$indice;

            $identificadorSeccao =
                $seccao['id']
                ?? null;

            if (is_int($identificadorSeccao)) {
                if (
                    isset(
                        $identificadoresSeccoes[$identificadorSeccao],
                    )
                ) {
                    $validador
                        ->errors()
                        ->add(
                            $prefixo.'.id',
                            'A mesma secção foi enviada mais do que uma vez.',
                        );
                }

                $identificadoresSeccoes[$identificadorSeccao] =
                    true;
            }

            $identificadorTipo =
                $seccao['tipo_secao_id']
                ?? null;

            if (! is_int($identificadorTipo)) {
                continue;
            }

            $tipo =
                $tipos->get(
                    $identificadorTipo,
                );

            if (! $tipo instanceof TipoSeccao) {
                continue;
            }

            if ($tipo->exige_detalhes) {
                $this->validarDetalhesObrigatorios(
                    $validador,
                    $prefixo,
                    $seccao,
                );

                continue;
            }

            $this->validarAusenciaDeDetalhes(
                $validador,
                $prefixo,
                $seccao,
            );
        }
    }

    /**
     * Valida os detalhes obrigatórios de uma secção.
     *
     * @param  Validator  $validador  Validador do pedido.
     * @param  string  $prefixo  Prefixo dos atributos da secção.
     * @param  array<string, mixed>  $seccao  Dados da secção.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function validarDetalhesObrigatorios(
        Validator $validador,
        string $prefixo,
        array $seccao,
    ): void {
        $campos = [
            'titulo' => 'Por favor, insere o título da secção.',

            'banda_id' => 'Por favor, seleciona a banda da secção.',

            'ligacao' => 'Por favor, insere a ligação da secção.',

            'tipo_incorporacao' => 'Por favor, seleciona o tipo de incorporação da secção.',

            'ano' => 'Por favor, insere o ano da secção.',
        ];

        foreach ($campos as $campo => $mensagem) {
            if (
                ! $this->valorEstaVazio(
                    $seccao[$campo]
                        ?? null,
                )
            ) {
                continue;
            }

            $validador
                ->errors()
                ->add(
                    $prefixo.'.'.$campo,
                    $mensagem,
                );
        }
    }

    /**
     * Impede detalhes em tipos de secção que não os suportam.
     *
     * @param  Validator  $validador  Validador do pedido.
     * @param  string  $prefixo  Prefixo dos atributos da secção.
     * @param  array<string, mixed>  $seccao  Dados da secção.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function validarAusenciaDeDetalhes(
        Validator $validador,
        string $prefixo,
        array $seccao,
    ): void {
        foreach (
            [
                'titulo',
                'banda_id',
                'ligacao',
                'tipo_incorporacao',
                'ano',
            ] as $campo
        ) {
            if (
                $this->valorEstaVazio(
                    $seccao[$campo]
                        ?? null,
                )
            ) {
                continue;
            }

            $validador
                ->errors()
                ->add(
                    $prefixo.'.'.$campo,
                    'O tipo selecionado não permite detalhes adicionais.',
                );
        }
    }

    /**
     * Normaliza as secções recebidas.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Secções normalizadas ou valor original.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function normalizarSeccoes(
        mixed $valor,
    ): mixed {
        if (! is_array($valor)) {
            return $valor;
        }

        $metalThursday =
            $this->obterMetalThursdayDaRota();

        $seccoes = [];

        foreach (array_values($valor) as $seccao) {
            if (! is_array($seccao)) {
                $seccoes[] =
                    $seccao;

                continue;
            }

            if ($metalThursday instanceof MetalThursday) {
                $seccao['id'] =
                    $this->normalizarIdentificador(
                        $seccao['id']
                            ?? null,
                    );
            } else {
                unset(
                    $seccao['id'],
                );
            }

            $seccao['tipo_secao_id'] =
                $this->normalizarIdentificador(
                    $seccao['tipo_secao_id']
                        ?? null,
                );

            $seccao['titulo'] =
                $this->normalizarTextoLinhaOpcional(
                    $seccao['titulo']
                        ?? null,
                );

            $seccao['descricao'] =
                $this->normalizarTextoMultilinha(
                    $seccao['descricao']
                        ?? null,
                );

            $seccao['banda_id'] =
                $this->normalizarIdentificador(
                    $seccao['banda_id']
                        ?? null,
                );

            $seccao['ligacao'] =
                $this->normalizarTextoOpcional(
                    $seccao['ligacao']
                        ?? null,
                );

            $seccao['tipo_incorporacao'] =
                $this->normalizarTextoOpcional(
                    $seccao['tipo_incorporacao']
                        ?? null,
                );

            $seccao['ano'] =
                $this->normalizarIdentificador(
                    $seccao['ano']
                        ?? null,
                );

            $seccoes[] =
                $seccao;
        }

        return $seccoes;
    }

    /**
     * Obtém a MetalThursday associada ao parâmetro da rota.
     *
     * @return MetalThursday|null MetalThursday atual ou nulo numa criação.
     *
     * @throws LogicException Quando existe um parâmetro de rota com um tipo
     *                        inesperado.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function obterMetalThursdayDaRota(): ?MetalThursday
    {
        $metalThursday =
            $this->route(
                'metalThursday',
            );

        if ($metalThursday === null) {
            return null;
        }

        if (! $metalThursday instanceof MetalThursday) {
            throw new LogicException(
                'A rota não contém uma MetalThursday válida.',
            );
        }

        return $metalThursday;
    }

    /**
     * Normaliza um identificador.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Identificador normalizado ou valor original.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Texto normalizado ou valor original.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function normalizarTextoLinhaOpcional(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
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
     *
     * @version 1.0.0
     */
    private function normalizarTextoOpcional(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        $texto =
            trim(
                $valor,
            );

        return $texto !== ''
            ? $texto
            : null;
    }

    /**
     * Normaliza um texto com várias linhas.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Texto normalizado ou valor original.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function normalizarTextoMultilinha(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
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
     *
     * @version 1.0.0
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
     * São permitidas tabulações e quebras de linha. Os restantes caracteres
     * de controlo são rejeitados.
     *
     * @param  string  $mensagemTextoInvalido  Mensagem para UTF-8 inválido.
     * @param  string  $mensagemCaracteresInvalidos  Mensagem para caracteres
     *                                               de controlo.
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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

    /**
     * Cria a regra adicional de segurança para ligações.
     *
     * Além da regra `url`, rejeita caracteres de controlo, espaços interiores,
     * barras invertidas e credenciais incorporadas no endereço.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarRegraLigacao(): Closure
    {
        return static function (
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
                    'A ligação da secção contém texto inválido.',
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
                    'A ligação da secção contém caracteres inválidos.',
                );

                return;
            }

            $componentes =
                parse_url(
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
                    'A ligação da secção deve ser um endereço HTTP ou HTTPS válido.',
                );
            }
        };
    }

    /**
     * Determina se um valor está vazio.
     *
     * O valor inteiro zero não é considerado vazio.
     *
     * @param  mixed  $valor  Valor verificado.
     * @return bool Verdadeiro quando o valor está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function valorEstaVazio(
        mixed $valor,
    ): bool {
        return $valor === null
            || $valor === '';
    }
}
