<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;
use LogicException;

/**
 * Define a validação comum dos pedidos de criação e atualização de edições.
 *
 * A validação HTTP produz mensagens adequadas para o utilizador. O modelo
 * {@see Edicao} volta a normalizar e validar os atributos antes da
 * persistência, protegendo outros pontos de entrada.
 *
 * A política da edição é aplicada antes da preparação dos dados, construção
 * das regras e execução das consultas de validação.
 *
 * @since 2.0.0
 */
abstract class PedidoEdicaoRequest extends FormRequest
{
    /**
     * Indica se o parâmetro da edição já foi resolvido.
     *
     * A flag permite distinguir uma rota de criação, cujo resultado válido é
     * nulo, de um parâmetro ainda não consultado.
     *
     * @since 2.0.0
     */
    private bool $edicaoDaRotaResolvida = false;

    /**
     * Edição resolvida através do parâmetro da rota.
     *
     * @since 2.0.0
     */
    private ?Edicao $edicaoDaRota = null;

    /**
     * Determina se o utilizador autenticado pode executar a operação.
     *
     * A ausência de uma edição na rota representa uma criação. Quando existe
     * uma edição resolvida, é verificada a capacidade de atualização dessa
     * instância.
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

        $edicao =
            $this->obterEdicaoDaRota();

        if ($edicao instanceof Edicao) {
            return $utilizador->can(
                'update',
                $edicao,
            );
        }

        return $utilizador->can(
            'create',
            Edicao::class,
        );
    }

    /**
     * Normaliza os dados antes da validação.
     *
     * O nome é reduzido a uma única linha, com whitespace consecutivo
     * convertido num único espaço, desde que não contenha caracteres que
     * devam ser rejeitados pela validação.
     *
     * Nas datas são removidos apenas espaços ASCII exteriores. Uma data de
     * fim vazia é convertida para nulo.
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
                $this->validarPeriodoSemSobreposicao(
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
     */
    abstract protected function obterRegraUnicidadeNome(): Unique;

    /**
     * Obtém a edição associada ao parâmetro da rota.
     *
     * Numa rota de criação não existe uma edição e o resultado é nulo. O
     * resultado é guardado para ser reutilizado pela autorização e pelas
     * regras de validação sem voltar a resolver o parâmetro.
     *
     * @return Edicao|null Edição atual ou nulo durante a criação.
     *
     * @throws LogicException Quando existe um parâmetro com tipo inesperado.
     *
     * @since 2.0.0
     */
    final protected function obterEdicaoDaRota(): ?Edicao
    {
        if ($this->edicaoDaRotaResolvida) {
            return $this->edicaoDaRota;
        }

        $edicao = $this->route(
            'edicao',
        );

        if (
            $edicao !== null
            && ! $edicao instanceof Edicao
        ) {
            throw new LogicException(
                'A rota não contém uma edição válida.',
            );
        }

        $this->edicaoDaRota =
            $edicao;

        $this->edicaoDaRotaResolvida =
            true;

        return $this->edicaoDaRota;
    }

    /**
     * Impede a existência de períodos sobrepostos entre edições ativas.
     *
     * As datas inicial e final são inclusivas. Uma edição sem data de fim é
     * considerada aberta indefinidamente.
     *
     * Durante uma atualização, a própria edição é excluída da consulta.
     * Edições eliminadas logicamente são ignoradas pelo âmbito global de
     * SoftDeletes do modelo.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 2.0.0
     */
    private function validarPeriodoSemSobreposicao(
        Validator $validador,
    ): void {
        if (
            $validador
                ->errors()
                ->hasAny([
                    'data_inicio',
                    'data_fim',
                ])
        ) {
            return;
        }

        $dataInicio =
            $this->input(
                'data_inicio',
            );

        $dataFim =
            $this->input(
                'data_fim',
            );

        if (
            ! is_string($dataInicio)
            || (
                $dataFim !== null
                && ! is_string($dataFim)
            )
        ) {
            return;
        }

        $edicaoAtual =
            $this->obterEdicaoDaRota();

        $construtor = Edicao::query()
            ->when(
                $edicaoAtual instanceof Edicao,
                static fn (
                    Builder $consulta,
                ): Builder => $consulta->where(
                    'id',
                    '!=',
                    $edicaoAtual->getKey(),
                ),
            )
            ->where(
                static function (
                    Builder $consulta,
                ) use (
                    $dataInicio,
                ): void {
                    $consulta
                        ->whereNull(
                            'data_fim',
                        )
                        ->orWhere(
                            'data_fim',
                            '>=',
                            $dataInicio,
                        );
                },
            );

        if ($dataFim !== null) {
            $construtor->where(
                'data_inicio',
                '<=',
                $dataFim,
            );
        }

        if (! $construtor->exists()) {
            return;
        }

        $validador
            ->errors()
            ->add(
                'data_inicio',
                'O período da edição sobrepõe-se ao período de outra edição.',
            );
    }

    /**
     * Normaliza o nome da edição.
     *
     * Valores que não sejam strings permanecem inalterados para que as regras
     * de tipo produzam a respetiva mensagem de validação.
     *
     * Texto UTF-8 inválido ou com caracteres de controlo permanece inalterado
     * para ser rejeitado pelas regras de validação. Nos restantes casos é
     * aplicada a mesma normalização utilizada pelo modelo {@see Edicao}.
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
     * Normaliza uma data recebida.
     *
     * Apenas espaços ASCII exteriores são removidos. Uma string composta
     * apenas por espaços é convertida para nulo, permitindo que a regra
     * `nullable` seja aplicada à data de fim.
     *
     * Os restantes caracteres permanecem inalterados para que uma data
     * inválida seja rejeitada pelas regras de validação.
     *
     * Valores que não sejam strings permanecem igualmente inalterados.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Data normalizada ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarData(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        $data = trim(
            $valor,
            ' ',
        );

        return $data !== ''
            ? $data
            : null;
    }
}
