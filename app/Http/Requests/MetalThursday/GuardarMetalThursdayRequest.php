<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Enumeracoes\TipoIncorporacao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Servicos\MetalThursday\ServicoReservasMetalThursday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder as ConstrutorEloquent;
use Illuminate\Database\Query\Builder as ConstrutorConsulta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use LogicException;

/**
 * Valida os dados necessários para criar ou atualizar uma MetalThursday.
 *
 * Valida também a estrutura das secções, a pertença das secções existentes à
 * MetalThursday atual, a edição determinada pela data e os campos exigidos
 * por cada tipo de secção.
 *
 * A validação HTTP apresenta mensagens adequadas ao utilizador. Os modelos e
 * o serviço de persistência mantêm validações próprias antes da escrita na
 * base de dados.
 *
 * @since 1.0.0
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
     */
    private const NUMERO_MAXIMO_SECCOES = 50;

    /**
     * Indica se o parâmetro da rota já foi resolvido.
     *
     * A flag permite distinguir uma rota de criação, cujo resultado válido é
     * nulo, de um parâmetro ainda não consultado.
     *
     * @since 2.0.0
     */
    private bool $metalThursdayDaRotaResolvida = false;

    /**
     * MetalThursday resolvida através do parâmetro da rota.
     *
     * @since 2.0.0
     */
    private ?MetalThursday $metalThursdayDaRota = null;

    /**
     * Determina se o utilizador autenticado pode executar a operação.
     *
     * A criação utiliza a capacidade da classe. A atualização utiliza a
     * instância resolvida pela rota. Esta verificação ocorre antes da
     * construção das regras e das consultas adicionais de validação.
     *
     * @return bool Verdadeiro quando a política permite a operação.
     *
     * @throws LogicException Quando existe um parâmetro de rota inválido.
     *
     * @since 1.0.0
     */
    public function authorize(): bool
    {
        $utilizador = $this->user(
            'sessao',
        );
        if (! $utilizador instanceof Utilizador) {
            return false;
        }
        $metalThursday =
            $this->obterMetalThursdayDaRota();
        if ($metalThursday instanceof MetalThursday) {
            return $utilizador->can(
                'update',
                $metalThursday,
            );
        }

        return $utilizador->can(
            'create',
            MetalThursday::class,
        );
    }

    /**
     * Normaliza os dados antes da validação.
     *
     * Os identificadores numéricos são convertidos para inteiros, os textos
     * opcionais vazios são convertidos para nulo e as secções são
     * reindexadas pela respetiva ordem no formulário.
     *
     * O identificador da edição recebido do cliente é normalizado nesta fase,
     * mas é sempre substituído pela edição determinada pela data antes da
     * construção das regras de validação.
     *
     * @since 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $dadosNormalizados = [
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
            'seccoes' => $this->normalizarSeccoes(
                $this->input(
                    'seccoes',
                    [],
                ),
            ),
        ];

        if (
            ! $this->obterMetalThursdayDaRota()
                instanceof MetalThursday
        ) {
            $dadosNormalizados['proximo_nomeado_id'] =
                $this->normalizarIdentificador(
                    $this->input(
                        'proximo_nomeado_id',
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
     * A edição é sempre determinada no servidor a partir da data normalizada,
     * ignorando qualquer identificador de edição recebido do cliente.
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
     */
    public function rules(): array
    {
        $this->determinarEdicaoPelaData();
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

        $regrasProximoNomeado =
            $metalThursday instanceof MetalThursday
            || $this->existeReservaSeguinteParaCriacao()
            ? [
                'bail',
                'nullable',
                'integer',
            ]
            : [
                'bail',
                'required',
                'integer',
                'different:autor_id',
                $this->criarRegraElegibilidadeNomeacao(),
            ];

        return [
            'edicao_id' => [
                'bail',
                'nullable',
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
                )->where(
                    static fn (
                        ConstrutorConsulta $construtor,
                    ): ConstrutorConsulta => $construtor
                        ->where(
                            'papel',
                            '!=',
                            PapelUtilizador::SuperAdministrador->value,
                        )
                        ->whereNull(
                            'suspenso_em',
                        ),
                ),
            ],
            'proximo_nomeado_id' => $regrasProximoNomeado,
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
                'array:id,tipo_seccao_id,titulo,descricao,artista_id,ligacao,tipo_incorporacao,ano',
            ],
            'seccoes.*.id' => $regrasIdentificadorSeccao,
            'seccoes.*.tipo_seccao_id' => [
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
            'seccoes.*.artista_id' => [
                'bail',
                'nullable',
                'integer',
                $this->criarRegraArtistaSecao(),
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
     */
    public function after(): array
    {
        return [
            function (
                Validator $validador,
            ): void {
                $this->validarDataPermitida(
                    $validador,
                );
                $this->validarEdicaoDeterminadaPelaData(
                    $validador,
                );
                $this->validarAutorPermitido(
                    $validador,
                );
                $this->validarPreservacaoProximoNomeado(
                    $validador,
                );
                $this->validarCompatibilidadeReservaDaData(
                    $validador,
                );
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
     */
    public function messages(): array
    {
        return [
            'edicao_id.integer' => 'A edição determinada não é válida.',
            'edicao_id.exists' => 'A edição determinada não existe ou não está disponível.',
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
            'autor_id.exists' => 'O autor selecionado não existe ou não está disponível.',
            'proximo_nomeado_id.required' => 'Por favor, seleciona o próximo nomeado.',
            'proximo_nomeado_id.integer' => 'O próximo nomeado selecionado não é válido.',
            'proximo_nomeado_id.different' => 'O próximo nomeado deve ser diferente do autor.',
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
            'seccoes.*.tipo_seccao_id.required' => 'Por favor, seleciona o tipo da secção.',
            'seccoes.*.tipo_seccao_id.integer' => 'O tipo de uma das secções não é válido.',
            'seccoes.*.tipo_seccao_id.exists' => 'O tipo de uma das secções não existe.',
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
            'seccoes.*.artista_id.integer' => 'O artista selecionado não é válido.',
            'seccoes.*.artista_id.exists' => 'O artista selecionado não existe ou não está disponível.',
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
            'seccoes.*.tipo_seccao_id' => 'tipo da secção',
            'seccoes.*.titulo' => 'título da secção',
            'seccoes.*.descricao' => 'descrição da secção',
            'seccoes.*.artista_id' => 'artista da secção',
            'seccoes.*.ligacao' => 'ligação da secção',
            'seccoes.*.tipo_incorporacao' => 'tipo de incorporação',
            'seccoes.*.ano' => 'ano da secção',
        ];
    }

    /**
     * Determina no servidor a edição correspondente à data recebida.
     *
     * O identificador enviado pelo cliente nunca é utilizado para decidir a
     * associação. Uma data pode corresponder no máximo a uma edição. Mais do
     * que uma correspondência representa uma quebra da integridade temporal
     * das edições e interrompe o pedido.
     *
     * @throws LogicException Quando mais do que uma edição inclui a data.
     *
     * @since 2.0.0
     */
    private function determinarEdicaoPelaData(): void
    {
        $data =
            $this->input(
                'data',
            );
        if (
            ! is_string($data)
            || preg_match(
                '/^\d{4}-\d{2}-\d{2}$/D',
                $data,
            ) !== 1
        ) {
            $this->merge([
                'edicao_id' => null,
            ]);

            return;
        }
        $identificadores = Edicao::query()
            ->where(
                'data_inicio',
                '<=',
                $data,
            )
            ->where(
                static function (
                    ConstrutorEloquent $construtor,
                ) use (
                    $data,
                ): void {
                    $construtor
                        ->whereNull(
                            'data_fim',
                        )
                        ->orWhere(
                            'data_fim',
                            '>=',
                            $data,
                        );
                },
            )
            ->orderBy(
                'data_inicio',
            )
            ->orderBy(
                'id',
            )
            ->limit(
                2,
            )
            ->pluck(
                'id',
            );
        if ($identificadores->count() > 1) {
            throw new LogicException(
                'Existe mais do que uma edição para a data indicada.',
            );
        }
        $identificador =
            $identificadores->first();
        $this->merge([
            'edicao_id' => is_numeric(
                $identificador,
            )
                ? (int) $identificador
                : null,
        ]);
    }

    /**
     * Impede utilizadores sem privilégios administrativos de definirem uma
     * data diferente da permitida.
     *
     * Na criação, a data corresponde obrigatoriamente à reserva pendente do
     * utilizador, mesmo quando esta já pertence ao passado. Durante uma
     * atualização, a data existente continua a ter de ser preservada.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 2.0.0
     */
    private function validarDataPermitida(
        Validator $validador,
    ): void {
        if ($validador->errors()->has('data')) {
            return;
        }
        $utilizador =
            $this->user(
                'sessao',
            );
        if (
            ! $utilizador instanceof Utilizador
            || $utilizador->possuiPrivilegiosAdministrativos()
        ) {
            return;
        }
        $metalThursday =
            $this->obterMetalThursdayDaRota();
        if ($metalThursday instanceof MetalThursday) {
            $dataPermitida =
                $metalThursday->data->format(
                    'Y-m-d',
                );
            $dataRecebida =
                $this->input(
                    'data',
                );
            if (
                is_string($dataRecebida)
                && $dataRecebida === $dataPermitida
            ) {
                return;
            }
            $validador
                ->errors()
                ->add(
                    'data',
                    'Não tens permissão para alterar a data da MetalThursday.',
                );

            return;
        }
        $reserva =
            app(
                ServicoReservasMetalThursday::class,
            )->obterReservaPendenteDoUtilizador(
                $utilizador,
            );
        if (! $reserva instanceof ReservaMetalThursday) {
            $validador
                ->errors()
                ->add(
                    'data',
                    'Não tens nenhuma reserva pendente para publicar.',
                );

            return;
        }
        $dataReserva =
            $reserva->data;
        if (! $dataReserva instanceof CarbonInterface) {
            throw new LogicException(
                'A reserva pendente não possui uma data válida.',
            );
        }
        $dataRecebida =
            $this->input(
                'data',
            );
        if (
            is_string($dataRecebida)
            && $dataRecebida === $dataReserva->format(
                'Y-m-d',
            )
        ) {
            return;
        }
        $validador
            ->errors()
            ->add(
                'data',
                'A data da MetalThursday deve corresponder à data da tua reserva pendente.',
            );
    }

    /**
     * Impede a alteração da nomeação depois da publicação.
     *
     * O formulário de edição já não envia o campo. Clientes antigos podem
     * continuar a reenviar o identificador persistido, mas qualquer tentativa
     * de o substituir, remover ou acrescentar é rejeitada.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 2.0.0
     */
    private function validarPreservacaoProximoNomeado(
        Validator $validador,
    ): void {
        $metalThursday =
            $this->obterMetalThursdayDaRota();

        if (
            ! $metalThursday instanceof MetalThursday
            || ! $this->exists(
                'proximo_nomeado_id',
            )
            || $validador
                ->errors()
                ->has(
                    'proximo_nomeado_id',
                )
        ) {
            return;
        }

        $identificadorPersistido =
            $metalThursday->proximo_nomeado_id;

        $identificadorRecebido =
            $this->input(
                'proximo_nomeado_id',
            );

        $persistidoNormalizado =
            is_numeric(
                $identificadorPersistido,
            )
            ? (int) $identificadorPersistido
            : null;

        $recebidoNormalizado =
            is_numeric(
                $identificadorRecebido,
            )
            ? (int) $identificadorRecebido
            : null;

        if (
            $persistidoNormalizado
            === $recebidoNormalizado
        ) {
            return;
        }

        $validador
            ->errors()
            ->add(
                'proximo_nomeado_id',
                'O próximo nomeado não pode ser alterado depois da publicação.',
            );
    }

    /**
     * Impede que uma criação ocupe um slot reservado a outro utilizador.
     *
     * Um slot sem responsável pode ser tratado excepcionalmente por um
     * administrador. Quando existe responsável, o autor tem de coincidir com
     * esse utilizador.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 2.0.0
     */
    private function validarCompatibilidadeReservaDaData(
        Validator $validador,
    ): void {
        if (
            $this->obterMetalThursdayDaRota()
            instanceof MetalThursday
        ) {
            return;
        }
        if (
            $validador
                ->errors()
                ->hasAny([
                    'data',
                    'autor_id',
                ])
        ) {
            return;
        }
        $data =
            $this->input(
                'data',
            );
        $identificadorAutor =
            $this->input(
                'autor_id',
            );
        if (
            ! is_string($data)
            || ! is_int($identificadorAutor)
        ) {
            return;
        }
        $reserva = ReservaMetalThursday::query()
            ->where(
                'data',
                $data,
            )
            ->whereNull(
                'metal_thursday_id',
            )
            ->first();
        if (
            ! $reserva instanceof ReservaMetalThursday
            || $reserva->responsavel_id === null
            || $reserva->responsavel_id === $identificadorAutor
        ) {
            return;
        }
        $validador
            ->errors()
            ->add(
                'autor_id',
                'O autor deve corresponder ao responsável da reserva desta data.',
            );
    }

    /**
     * Confirma que foi possível determinar uma edição para a data válida.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 2.0.0
     */
    private function validarEdicaoDeterminadaPelaData(
        Validator $validador,
    ): void {
        if (
            $validador
                ->errors()
                ->has(
                    'data',
                )
        ) {
            return;
        }
        if (
            ! $validador
                ->errors()
                ->has(
                    'edicao_id',
                )
            && is_int(
                $this->input(
                    'edicao_id',
                ),
            )
        ) {
            return;
        }
        $validador
            ->errors()
            ->add(
                'data',
                'Não existe nenhuma edição que inclua a data selecionada.',
            );
    }

    /**
     * Impede utilizadores sem privilégios administrativos de alterarem o autor.
     *
     * Na criação, o autor tem de corresponder ao utilizador autenticado.
     * Durante uma atualização, o autor existente tem de ser preservado.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 2.0.0
     */
    private function validarAutorPermitido(
        Validator $validador,
    ): void {
        if ($validador->errors()->has('autor_id')) {
            return;
        }
        $utilizador =
            $this->user(
                'sessao',
            );
        if (
            ! $utilizador instanceof Utilizador
            || $utilizador->possuiPrivilegiosAdministrativos()
        ) {
            return;
        }
        $metalThursday =
            $this->obterMetalThursdayDaRota();
        $autorPermitido =
            $metalThursday instanceof MetalThursday
            ? $metalThursday->autor_id
            : $utilizador->getKey();
        $autorRecebido =
            $this->input(
                'autor_id',
            );
        if (
            ! is_numeric($autorPermitido)
            || ! is_numeric($autorRecebido)
            || (int) $autorPermitido !== (int) $autorRecebido
        ) {
            $validador->errors()->add(
                'autor_id',
                'Não tens permissão para definir este autor.',
            );
        }
    }

    /**
     * Confirma que a data pertence ao intervalo da edição.
     *
     * Esta validação permanece como defesa adicional depois de a edição ter
     * sido determinada automaticamente.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 2.0.0
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
     * Os tipos que exigem detalhes requerem título, artista, ligação, tipo de
     * incorporação e ano. Os restantes tipos não podem guardar esses campos.
     * A descrição é obrigatória em todas as secções.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 2.0.0
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
                    $seccao['tipo_seccao_id']
                        ?? null,
                )
            ) {
                $identificadoresTipos[] =
                    $seccao['tipo_seccao_id'];
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
                $seccao['tipo_seccao_id']
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
     */
    private function validarDetalhesObrigatorios(
        Validator $validador,
        string $prefixo,
        array $seccao,
    ): void {
        $campos = [
            'titulo' => 'Por favor, insere o título da secção.',
            'artista_id' => 'Por favor, seleciona o artista da secção.',
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
     */
    private function validarAusenciaDeDetalhes(
        Validator $validador,
        string $prefixo,
        array $seccao,
    ): void {
        foreach (
            [
                'titulo',
                'artista_id',
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
     * Determina se já existe uma reserva para a quinta-feira seguinte.
     *
     * Durante uma criação, uma reserva seguinte previamente existente é
     * autoritativa. Nessa situação, o próximo nomeado recebido no pedido deixa
     * de ser obrigatório porque a persistência preserva o responsável efetivo
     * dessa reserva, incluindo a possibilidade de ainda estar por atribuir.
     *
     * A data da reserva seguinte é calculada com a mesma regra da persistência:
     * procura-se explicitamente a próxima quinta-feira. Isto mantém o fluxo
     * administrativo correto quando a MetalThursday é criada numa data que não
     * corresponde a uma quinta-feira.
     *
     * Uma data inválida não é interpretada nesta fase. As regras próprias do
     * campo `data` continuam responsáveis por rejeitar esse pedido.
     *
     * @return bool Verdadeiro quando a slot seguinte já existe.
     *
     * @since 2.0.0
     */
    private function existeReservaSeguinteParaCriacao(): bool
    {
        if (
            $this->obterMetalThursdayDaRota()
            instanceof MetalThursday
        ) {
            return false;
        }

        $data = $this->input(
            'data',
        );

        if (
            ! is_string($data)
            || ! CarbonImmutable::canBeCreatedFromFormat(
                $data,
                'Y-m-d',
            )
        ) {
            return false;
        }

        $dataCriacao = CarbonImmutable::createFromFormat(
            'Y-m-d',
            $data,
        );

        if (! $dataCriacao instanceof CarbonImmutable) {
            return false;
        }

        $dataReservaSeguinte = $dataCriacao
            ->next(
                CarbonImmutable::THURSDAY,
            )
            ->startOfDay();

        return ReservaMetalThursday::query()
            ->where(
                'data',
                $dataReservaSeguinte->toDateString(),
            )
            ->exists();
    }

    /**
     * Cria a regra que valida o artista associado a uma secção.
     *
     * Artistas ativos podem ser utilizados normalmente. Durante a atualização,
     * uma secção existente pode ainda conservar o próprio artista que tenha sido
     * entretanto eliminado logicamente.
     *
     * Um artista eliminado não pode ser associado a uma secção nova nem
     * transferido para outra secção.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     */
    private function criarRegraArtistaSecao(): Closure
    {
        return function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ): void {
            if (
                ! is_int($valor)
                || $valor < 1
            ) {
                return;
            }

            if (
                Artista::query()
                    ->whereKey(
                        $valor,
                    )
                    ->exists()
            ) {
                return;
            }

            $metalThursday =
                $this->obterMetalThursdayDaRota();

            if (! $metalThursday instanceof MetalThursday) {
                $falhar(
                    'O artista selecionado não existe ou não está disponível.',
                );

                return;
            }

            if (
                preg_match(
                    '/^seccoes\.(\d+)\.artista_id$/D',
                    $atributo,
                    $correspondencias,
                ) !== 1
            ) {
                $falhar(
                    'O artista selecionado não existe ou não está disponível.',
                );

                return;
            }

            $indice =
                (int) $correspondencias[1];

            $identificadorSeccao =
                $this->input(
                    "seccoes.{$indice}.id",
                );

            if (
                ! is_int($identificadorSeccao)
                || $identificadorSeccao < 1
            ) {
                $falhar(
                    'O artista selecionado não existe ou não está disponível.',
                );

                return;
            }

            $artistaEliminadoExiste = Artista::withTrashed()
                ->whereKey(
                    $valor,
                )
                ->whereNotNull(
                    'deleted_at',
                )
                ->exists();

            if (! $artistaEliminadoExiste) {
                $falhar(
                    'O artista selecionado não existe ou não está disponível.',
                );

                return;
            }

            $artistaJaPertenceASecao = SeccaoMetalThursday::query()
                ->whereKey(
                    $identificadorSeccao,
                )
                ->where(
                    'metal_thursday_id',
                    $metalThursday->getKey(),
                )
                ->where(
                    'artista_id',
                    $valor,
                )
                ->whereNull(
                    'deleted_at',
                )
                ->exists();

            if ($artistaJaPertenceASecao) {
                return;
            }

            $falhar(
                'O artista selecionado não existe ou não está disponível.',
            );
        };
    }

    /**
     * Cria a regra que valida a elegibilidade de uma nova nomeação.
     *
     * A regra é utilizada apenas durante a criação. Depois da publicação, a
     * reserva seguinte é a fonte de verdade e a nomeação deixa de ser editável.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     */
    private function criarRegraElegibilidadeNomeacao(): Closure
    {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ): void {
            if (
                ! is_int(
                    $valor,
                )
                || $valor < 1
            ) {
                return;
            }

            if (
                Utilizador::query()
                    ->elegiveisParaNomeacao()
                    ->whereKey(
                        $valor,
                    )
                    ->exists()
            ) {
                return;
            }

            $falhar(
                'O próximo nomeado selecionado não existe ou não está disponível.',
            );
        };
    }

    /**
     * Normaliza as secções recebidas.
     *
     * Os identificadores são preservados durante a criação para que a regra
     * de proibição possa rejeitar explicitamente referências a secções
     * existentes.
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
            $seccao['id'] =
                $this->normalizarIdentificador(
                    $seccao['id']
                        ?? null,
                );
            $seccao['tipo_seccao_id'] =
                $this->normalizarIdentificador(
                    $seccao['tipo_seccao_id']
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
            $seccao['artista_id'] =
                $this->normalizarIdentificador(
                    $seccao['artista_id']
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
     */
    private function obterMetalThursdayDaRota(): ?MetalThursday
    {
        if ($this->metalThursdayDaRotaResolvida) {
            return $this->metalThursdayDaRota;
        }
        $metalThursday =
            $this->route(
                'metalThursday',
            );
        if (
            $metalThursday !== null
            && ! $metalThursday instanceof MetalThursday
        ) {
            throw new LogicException(
                'A rota não contém uma MetalThursday válida.',
            );
        }
        $this->metalThursdayDaRota =
            $metalThursday;
        $this->metalThursdayDaRotaResolvida =
            true;

        return $this->metalThursdayDaRota;
    }

    /**
     * Normaliza um identificador.
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
     * Caracteres de controlo permanecem inalterados para que a regra de
     * validação os possa rejeitar em vez de os normalizar silenciosamente.
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
     * Apenas espaços ASCII exteriores são removidos. Os restantes caracteres
     * permanecem disponíveis para as regras específicas de cada campo.
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
     * Tabulações e quebras de linha são permitidas. Os restantes caracteres
     * de controlo permanecem inalterados para que a validação os rejeite.
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
     * São permitidas tabulações e quebras de linha. Os restantes caracteres
     * de controlo são rejeitados.
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
     */
    private function valorEstaVazio(
        mixed $valor,
    ): bool {
        return $valor === null
            || $valor === '';
    }
}
