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
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Valida os dados necessários para criar ou atualizar uma MetalThursday.
 *
 * Valida também a estrutura das secções, a pertença das secções existentes à
 * MetalThursday atual e os campos exigidos pelos tipos de secção que possuem
 * detalhes.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class GuardarMetalThursdayRequest extends FormRequest
{
    /**
     * Número máximo de secções permitido por MetalThursday.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const MAXIMO_SECCOES = 50;

    /**
     * Comprimento máximo da descrição de uma secção.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const LIMITE_DESCRICAO = 20000;

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
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'edicao_id' => $this->normalizarIdentificador(
                $this->input('edicao_id'),
            ),

            'data' => $this->normalizarTextoOpcional(
                $this->input('data'),
            ),

            'nome' => $this->normalizarNome(
                $this->input('nome'),
            ),

            'autor_id' => $this->normalizarIdentificador(
                $this->input('autor_id'),
            ),

            'proximo_nomeado_id' => $this->normalizarIdentificador(
                $this->input('proximo_nomeado_id'),
            ),

            'secoes' => $this->normalizarSecoes(
                $this->input(
                    'secoes',
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
     * @return array<string, array<int, mixed>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
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

        $regrasIdentificadorSecao = [
            'nullable',
            'integer',
        ];

        if ($metalThursday instanceof MetalThursday) {
            $regrasIdentificadorSecao[] = Rule::exists(
                SeccaoMetalThursday::class,
                'id',
            )
                ->where(
                    static fn ($consulta) => $consulta
                        ->where(
                            'metal_thursday_id',
                            $metalThursday->getKey(),
                        )
                        ->whereNull(
                            'deleted_at',
                        ),
                );
        } else {
            $regrasIdentificadorSecao[] =
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
                'max:255',
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

            'secoes' => [
                'bail',
                'required',
                'array',
                'list',
                'min:1',
                'max:'.self::MAXIMO_SECCOES,
            ],

            'secoes.*' => [
                'bail',
                'required',
                'array:id,tipo_secao_id,titulo,descricao,banda_id,ligacao,tipo_incorporacao,ano',
            ],

            'secoes.*.id' => $regrasIdentificadorSecao,

            'secoes.*.tipo_secao_id' => [
                'bail',
                'required',
                'integer',

                Rule::exists(
                    TipoSeccao::class,
                    'id',
                ),
            ],

            'secoes.*.titulo' => [
                'bail',
                'nullable',
                'string',
                'max:255',
            ],

            'secoes.*.descricao' => [
                'bail',
                'required',
                'string',
                'max:'.self::LIMITE_DESCRICAO,
            ],

            'secoes.*.banda_id' => [
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

            'secoes.*.ligacao' => [
                'bail',
                'nullable',
                'string',
                'url:http,https',
                'max:2048',
            ],

            'secoes.*.tipo_incorporacao' => [
                'bail',
                'nullable',
                Rule::enum(
                    TipoIncorporacao::class,
                ),
            ],

            'secoes.*.ano' => [
                'bail',
                'nullable',
                'integer',
                'min:1900',
                'max:'.date('Y'),
            ],
        ];
    }

    /**
     * Obtém as validações executadas depois das regras principais.
     *
     * @return array<int, callable(Validator): void> Validações adicionais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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

                $this->validarSecoes(
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
     * @version 2.0.0
     */
    public function messages(): array
    {
        return [
            'edicao_id.required' => 'Por favor, seleciona uma edição.',

            'edicao_id.integer' => 'A edição selecionada não é válida.',

            'edicao_id.exists' => 'A edição selecionada não existe.',

            'data.required' => 'Por favor, insere a data da MetalThursday.',

            'data.date_format' => 'A data da MetalThursday deve ser uma data válida.',

            'data.unique' => 'Já existe uma MetalThursday na data selecionada.',

            'nome.string' => 'O nome deve ser uma sequência de caracteres.',

            'nome.max' => 'O nome não pode ter mais de 255 caracteres.',

            'autor_id.required' => 'Por favor, seleciona o autor.',

            'autor_id.integer' => 'O autor selecionado não é válido.',

            'autor_id.exists' => 'O autor selecionado não existe.',

            'proximo_nomeado_id.required' => 'Por favor, seleciona o próximo nomeado.',

            'proximo_nomeado_id.integer' => 'O próximo nomeado selecionado não é válido.',

            'proximo_nomeado_id.exists' => 'O próximo nomeado selecionado não existe.',

            'secoes.required' => 'Por favor, insere pelo menos uma secção.',

            'secoes.array' => 'As secções devem ser enviadas numa lista.',

            'secoes.list' => 'A lista de secções não tem um formato válido.',

            'secoes.min' => 'Por favor, insere pelo menos uma secção.',

            'secoes.max' => 'Uma MetalThursday não pode possuir mais de 50 secções.',

            'secoes.*.required' => 'Uma das secções não foi recebida corretamente.',

            'secoes.*.array' => 'Uma das secções não tem um formato válido.',

            'secoes.*.id.integer' => 'O identificador de uma das secções não é válido.',

            'secoes.*.id.exists' => 'Uma das secções não existe ou não pertence a esta MetalThursday.',

            'secoes.*.id.prohibited' => 'Uma nova MetalThursday não pode receber secções existentes.',

            'secoes.*.tipo_secao_id.required' => 'Por favor, seleciona o tipo da secção.',

            'secoes.*.tipo_secao_id.integer' => 'O tipo de uma das secções não é válido.',

            'secoes.*.tipo_secao_id.exists' => 'O tipo de uma das secções não existe.',

            'secoes.*.titulo.string' => 'O título da secção deve ser uma sequência de caracteres.',

            'secoes.*.titulo.max' => 'O título da secção não pode ter mais de 255 caracteres.',

            'secoes.*.descricao.required' => 'Por favor, insere a descrição da secção.',

            'secoes.*.descricao.string' => 'A descrição da secção deve ser uma sequência de caracteres.',

            'secoes.*.descricao.max' => 'A descrição da secção é demasiado extensa.',

            'secoes.*.banda_id.integer' => 'A banda selecionada não é válida.',

            'secoes.*.banda_id.exists' => 'A banda selecionada não existe.',

            'secoes.*.ligacao.string' => 'A ligação da secção não é válida.',

            'secoes.*.ligacao.url' => 'A ligação da secção deve ser um endereço HTTP ou HTTPS válido.',

            'secoes.*.ligacao.max' => 'A ligação da secção não pode ter mais de 2048 caracteres.',

            'secoes.*.tipo_incorporacao.enum' => 'O tipo de incorporação selecionado não é válido.',

            'secoes.*.ano.integer' => 'O ano deve ser um número inteiro.',

            'secoes.*.ano.min' => 'O ano não pode ser anterior a 1900.',

            'secoes.*.ano.max' => 'O ano não pode ser posterior a '.date('Y').'.',
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
            'edicao_id' => 'edição',

            'data' => 'data',

            'nome' => 'nome',

            'autor_id' => 'autor',

            'proximo_nomeado_id' => 'próximo nomeado',

            'secoes' => 'secções',

            'secoes.*.id' => 'identificador da secção',

            'secoes.*.tipo_secao_id' => 'tipo da secção',

            'secoes.*.titulo' => 'título da secção',

            'secoes.*.descricao' => 'descrição da secção',

            'secoes.*.banda_id' => 'banda da secção',

            'secoes.*.ligacao' => 'ligação da secção',

            'secoes.*.tipo_incorporacao' => 'tipo de incorporação',

            'secoes.*.ano' => 'ano da secção',
        ];
    }

    /**
     * Confirma que a data pertence ao intervalo da edição.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
            $this->input('edicao_id');

        $data =
            $this->input('data');

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
            && $data < $dataInicio->format('Y-m-d')
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
            && $data > $dataFim->format('Y-m-d')
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
     * Valida regras dependentes do tipo de cada secção.
     *
     * Os tipos com detalhes exigem título, banda, ligação, tipo de
     * incorporação e ano. Os tipos sem detalhes não podem guardar esses
     * campos.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarSecoes(
        Validator $validador,
    ): void {
        $secoes =
            $this->input('secoes');

        if (! is_array($secoes)) {
            return;
        }

        $identificadoresTipos = [];

        foreach ($secoes as $secao) {
            if (
                is_array($secao)
                && is_int(
                    $secao['tipo_secao_id']
                        ?? null,
                )
            ) {
                $identificadoresTipos[] =
                    $secao['tipo_secao_id'];
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

        $identificadoresSecoes = [];

        foreach ($secoes as $indice => $secao) {
            if (! is_array($secao)) {
                continue;
            }

            $prefixo =
                'secoes.'.$indice;

            $identificadorSecao =
                $secao['id']
                ?? null;

            if (is_int($identificadorSecao)) {
                if (
                    isset(
                        $identificadoresSecoes[$identificadorSecao],
                    )
                ) {
                    $validador
                        ->errors()
                        ->add(
                            $prefixo.'.id',
                            'A mesma secção foi enviada mais do que uma vez.',
                        );
                }

                $identificadoresSecoes[$identificadorSecao] = true;
            }

            $identificadorTipo =
                $secao['tipo_secao_id']
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

            if ((bool) $tipo->tem_detalhes) {
                $this->validarDetalhesObrigatorios(
                    $validador,
                    $prefixo,
                    $secao,
                );

                continue;
            }

            $this->validarAusenciaDeDetalhes(
                $validador,
                $prefixo,
                $secao,
            );
        }
    }

    /**
     * Valida os detalhes obrigatórios de uma secção.
     *
     * @param  Validator  $validador  Validador do pedido.
     * @param  string  $prefixo  Prefixo dos atributos da secção.
     * @param  array<string, mixed>  $secao  Dados da secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarDetalhesObrigatorios(
        Validator $validador,
        string $prefixo,
        array $secao,
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
                    $secao[$campo]
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
     * @param  array<string, mixed>  $secao  Dados da secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarAusenciaDeDetalhes(
        Validator $validador,
        string $prefixo,
        array $secao,
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
                    $secao[$campo]
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
     * @version 1.0.0
     */
    private function normalizarSecoes(
        mixed $valor,
    ): mixed {
        if (! is_array($valor)) {
            return $valor;
        }

        $metalThursday =
            $this->obterMetalThursdayDaRota();

        $secoes = [];

        foreach (array_values($valor) as $secao) {
            if (! is_array($secao)) {
                $secoes[] =
                    $secao;

                continue;
            }

            if ($metalThursday instanceof MetalThursday) {
                $secao['id'] =
                    $this->normalizarIdentificador(
                        $secao['id']
                            ?? null,
                    );
            } else {
                unset(
                    $secao['id'],
                );
            }

            $secao['tipo_secao_id'] =
                $this->normalizarIdentificador(
                    $secao['tipo_secao_id']
                        ?? null,
                );

            $secao['titulo'] =
                $this->normalizarNome(
                    $secao['titulo']
                        ?? null,
                );

            $secao['descricao'] =
                $this->normalizarTextoMultilinha(
                    $secao['descricao']
                        ?? null,
                );

            $secao['banda_id'] =
                $this->normalizarIdentificador(
                    $secao['banda_id']
                        ?? null,
                );

            $secao['ligacao'] =
                $this->normalizarTextoOpcional(
                    $secao['ligacao']
                        ?? null,
                );

            $secao['tipo_incorporacao'] =
                $this->normalizarTextoOpcional(
                    $secao['tipo_incorporacao']
                        ?? null,
                );

            $secao['ano'] =
                $this->normalizarIdentificador(
                    $secao['ano']
                        ?? null,
                );

            $secoes[] =
                $secao;
        }

        return $secoes;
    }

    /**
     * Obtém a MetalThursday associada ao parâmetro da rota.
     *
     * @return MetalThursday|null MetalThursday atual ou nulo numa criação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterMetalThursdayDaRota(): ?MetalThursday
    {
        $metalThursday =
            $this->route(
                'metalThursday',
            );

        return $metalThursday instanceof MetalThursday
            ? $metalThursday
            : null;
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
     * Normaliza um nome ou título.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Texto normalizado ou valor original.
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

        $texto = preg_replace(
            '/\s+/u',
            ' ',
            trim($valor),
        );

        if (! is_string($texto)) {
            return trim($valor);
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
            trim($valor);

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
     * @version 1.0.0
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
