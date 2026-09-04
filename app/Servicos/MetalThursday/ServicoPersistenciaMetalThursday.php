<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Resultados\MetalThursday\MetalThursdayCriada;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as ColecaoEloquent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Throwable;

/**
 * Gere a criação e a atualização transacional de MetalThursdays.
 *
 * Os dados principais e as respetivas secções são normalizados, validados e
 * persistidos atomicamente. Durante uma atualização, a MetalThursday e as
 * secções existentes são bloqueadas para impedir alterações concorrentes.
 *
 * @since 2.0.0
 */
final class ServicoPersistenciaMetalThursday
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Cria o serviço com a gestão de reservas necessária ao encadeamento.
     *
     * @param  ServicoReservasMetalThursday  $servicoReservas  Serviço de
     *                                                         reservas.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoReservasMetalThursday $servicoReservas,
    ) {}

    /**
     * Cria uma MetalThursday e as respetivas secções.
     *
     * @param  array<string, mixed>  $dados  Dados recebidos.
     * @return MetalThursday MetalThursday criada.
     *
     * @throws InvalidArgumentException Quando os dados ou as relações não são
     *                                  válidos.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     */
    public function criar(
        array $dados,
    ): MetalThursday {
        return $this
            ->criarComResultado(
                $dados,
            )
            ->obterMetalThursday();
    }

    /**
     * Cria uma MetalThursday e devolve o resultado completo da operação.
     *
     * O resultado identifica também a reserva seguinte efectivamente criada. Se
     * o slot seguinte já existia, essa reserva é nula.
     *
     * @param  array<string, mixed>  $dados  Dados recebidos.
     * @return MetalThursdayCriada Resultado da criação.
     *
     * @throws InvalidArgumentException Quando os dados ou as relações não são
     *                                  válidos.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     */
    public function criarComResultado(
        array $dados,
    ): MetalThursdayCriada {
        $dadosNormalizados = $this->normalizarDados(
            $dados,
        );

        return DB::transaction(
            function () use (
                $dadosNormalizados,
            ): MetalThursdayCriada {
                $this->bloquearEdicaoPrincipal(
                    $dadosNormalizados,
                );

                $reserva =
                    $this->obterReservaDaDataParaCriacao(
                        $dadosNormalizados,
                    );

                $reservaSeguinte =
                    $this->criarReservaSeguinte(
                        $dadosNormalizados,
                    );

                $reservaSeguinteEfetiva =
                    $reservaSeguinte instanceof ReservaMetalThursday
                    ? $reservaSeguinte
                    : $this->obterReservaSeguinteEfetiva(
                        $dadosNormalizados,
                    );

                $dadosPersistencia =
                    $this->substituirNomeadoPeloResponsavelEfetivo(
                        $dadosNormalizados,
                        $reservaSeguinteEfetiva,
                    );

                $this->bloquearUtilizadoresPrincipais(
                    $dadosPersistencia,
                );

                $tiposSeccao = $this->obterTiposSeccao(
                    $dadosNormalizados['seccoes'],
                );

                $this->bloquearArtistasUtilizados(
                    $dadosNormalizados['seccoes'],
                    $tiposSeccao,
                );

                $metalThursday = new MetalThursday;

                $this->preencherMetalThursday(
                    $metalThursday,
                    $dadosPersistencia,
                );

                $metalThursday->saveOrFail();

                $identificadorMetalThursday =
                    $this->obterIdentificadorPersistido(
                        $metalThursday,
                    );

                foreach (
                    $dadosNormalizados['seccoes'] as $indice => $dadosSeccao
                ) {
                    $tipoSeccao = $this->obterTipoSeccaoDaColecao(
                        $tiposSeccao,
                        $dadosSeccao['tipo_seccao_id'],
                    );

                    $this->criarSeccao(
                        $identificadorMetalThursday,
                        $dadosSeccao,
                        $tipoSeccao,
                        $indice + SeccaoMetalThursday::ORDEM_MINIMA,
                    );
                }

                $this->cumprirReserva(
                    $reserva,
                    $metalThursday,
                    $dadosNormalizados['autor_id'],
                );

                $metalThursdayCriada = $metalThursday
                    ->refresh()
                    ->load(
                        'seccoes',
                    );

                return new MetalThursdayCriada(
                    $metalThursdayCriada,
                    $reservaSeguinte,
                );
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Atualiza uma MetalThursday e sincroniza as respetivas secções.
     *
     * Antes de aplicar a ordem final, as secções existentes recebem posições
     * temporárias exclusivas. Esta reserva evita violações transitórias da
     * restrição única quando duas secções trocam de posição.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday atualizada.
     * @param  array<string, mixed>  $dados  Dados recebidos.
     * @return MetalThursday MetalThursday atualizada.
     *
     * @throws InvalidArgumentException Quando os dados ou as relações não são
     *                                  válidos.
     * @throws ModelNotFoundException Quando a MetalThursday deixou de existir.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     */
    public function atualizar(
        MetalThursday $metalThursday,
        array $dados,
    ): MetalThursday {
        $identificadorMetalThursday =
            $this->obterIdentificadorPersistido(
                $metalThursday,
            );

        $dadosNormalizados = $this->normalizarDados(
            $dados,
        );

        return DB::transaction(
            function () use (
                $identificadorMetalThursday,
                $dadosNormalizados,
            ): MetalThursday {
                $metalThursdayBloqueada = MetalThursday::query()
                    ->whereKey(
                        $identificadorMetalThursday,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $dadosPersistencia =
                    $this->preservarNomeadoPersistidoNaAtualizacao(
                        $dadosNormalizados,
                        $metalThursdayBloqueada,
                    );

                $seccoesExistentes = SeccaoMetalThursday::query()
                    ->where(
                        'metal_thursday_id',
                        $identificadorMetalThursday,
                    )
                    ->orderBy(
                        'id',
                    )
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(
                        static fn (
                            SeccaoMetalThursday $seccao,
                        ): int => (int) $seccao->getKey(),
                    );

                $this->garantirIdentificadoresSeccoesValidos(
                    $dadosNormalizados['seccoes'],
                    $seccoesExistentes,
                );

                $this->bloquearEdicaoPrincipal(
                    $dadosPersistencia,
                );

                $this->bloquearUtilizadoresPrincipais(
                    $dadosPersistencia,
                );

                $tiposSeccao = $this->obterTiposSeccao(
                    $dadosNormalizados['seccoes'],
                );

                $this->bloquearArtistasUtilizados(
                    $dadosNormalizados['seccoes'],
                    $tiposSeccao,
                    $seccoesExistentes,
                );

                $this->reservarOrdensTemporarias(
                    $seccoesExistentes,
                    count(
                        $dadosNormalizados['seccoes'],
                    ),
                );

                $this->preencherMetalThursday(
                    $metalThursdayBloqueada,
                    $dadosPersistencia,
                );

                $metalThursdayBloqueada->saveOrFail();

                $this->eliminarSeccoesAusentes(
                    $dadosNormalizados['seccoes'],
                    $seccoesExistentes,
                );

                foreach (
                    $dadosNormalizados['seccoes'] as $indice => $dadosSeccao
                ) {
                    $tipoSeccao = $this->obterTipoSeccaoDaColecao(
                        $tiposSeccao,
                        $dadosSeccao['tipo_seccao_id'],
                    );

                    $ordem =
                        $indice
                        + SeccaoMetalThursday::ORDEM_MINIMA;

                    $identificadorSeccao =
                        $dadosSeccao['id'];

                    if ($identificadorSeccao === null) {
                        $this->criarSeccao(
                            $identificadorMetalThursday,
                            $dadosSeccao,
                            $tipoSeccao,
                            $ordem,
                        );

                        continue;
                    }

                    $seccao = $seccoesExistentes->get(
                        $identificadorSeccao,
                    );

                    if (! $seccao instanceof SeccaoMetalThursday) {
                        throw new LogicException(
                            'Não foi possível obter uma secção previamente validada.',
                        );
                    }

                    $this->preencherSeccao(
                        $seccao,
                        $dadosSeccao,
                        $tipoSeccao,
                        $ordem,
                    );

                    $seccao->saveOrFail();
                }

                return $metalThursdayBloqueada
                    ->refresh()
                    ->load(
                        'seccoes',
                    );
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Normaliza os dados principais e as secções.
     *
     * Apenas os nomes finais em português são aceites.
     *
     * @param  array<string, mixed>  $dados  Dados recebidos.
     * @return array{
     *     edicao_id: int,
     *     data: string,
     *     nome: string|null,
     *     autor_id: int|null,
     *     proximo_nomeado_id: int|null,
     *     seccoes: list<array{
     *         id: int|null,
     *         tipo_seccao_id: int,
     *         artista_id: int|null,
     *         titulo: string|null,
     *         ligacao: string|null,
     *         tipo_incorporacao: TipoIncorporacao|null,
     *         ano: int|null,
     *         descricao: string
     *     }>
     * } Dados normalizados.
     *
     * @throws InvalidArgumentException Quando algum valor não é válido.
     *
     * @since 2.0.0
     */
    private function normalizarDados(
        array $dados,
    ): array {
        $seccoesRecebidas =
            $dados['seccoes']
            ?? null;

        if (
            ! is_array($seccoesRecebidas)
            || ! array_is_list(
                $seccoesRecebidas,
            )
        ) {
            throw new InvalidArgumentException(
                'As secções devem ser enviadas numa lista.',
            );
        }

        if ($seccoesRecebidas === []) {
            throw new InvalidArgumentException(
                'Deve ser enviada pelo menos uma secção.',
            );
        }

        $seccoes = [];

        foreach ($seccoesRecebidas as $indice => $dadosSeccao) {
            if (! is_array($dadosSeccao)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'A secção na posição %d não é válida.',
                        $indice + 1,
                    ),
                );
            }

            $prefixoCampo = sprintf(
                'seccoes.%d',
                $indice,
            );

            $seccoes[] = [
                'id' => $this->normalizarIdentificadorOpcional(
                    $dadosSeccao['id']
                        ?? null,
                    $prefixoCampo.'.id',
                ),
                'tipo_seccao_id' => $this->normalizarIdentificadorObrigatorio(
                    $dadosSeccao['tipo_seccao_id']
                        ?? null,
                    $prefixoCampo.'.tipo_seccao_id',
                ),
                'artista_id' => $this->normalizarIdentificadorOpcional(
                    $dadosSeccao['artista_id']
                        ?? null,
                    $prefixoCampo.'.artista_id',
                ),
                'titulo' => $this->normalizarTextoLinhaOpcional(
                    $dadosSeccao['titulo']
                        ?? null,
                    $prefixoCampo.'.titulo',
                    SeccaoMetalThursday::COMPRIMENTO_MAXIMO_TITULO,
                ),
                'ligacao' => $this->normalizarLigacao(
                    $dadosSeccao['ligacao']
                        ?? null,
                    $prefixoCampo.'.ligacao',
                ),
                'tipo_incorporacao' => $this->normalizarTipoIncorporacao(
                    $dadosSeccao['tipo_incorporacao']
                        ?? null,
                    $prefixoCampo.'.tipo_incorporacao',
                ),
                'ano' => $this->normalizarAno(
                    $dadosSeccao['ano']
                        ?? null,
                    $prefixoCampo.'.ano',
                ),
                'descricao' => $this->normalizarDescricaoObrigatoria(
                    $dadosSeccao['descricao']
                        ?? null,
                    $prefixoCampo.'.descricao',
                ),
            ];
        }

        return [
            'edicao_id' => $this->normalizarIdentificadorObrigatorio(
                $dados['edicao_id']
                    ?? null,
                'edicao_id',
            ),
            'data' => $this->normalizarData(
                $dados['data']
                    ?? null,
            ),
            'nome' => $this->normalizarTextoLinhaOpcional(
                $dados['nome']
                    ?? null,
                'nome',
                MetalThursday::COMPRIMENTO_MAXIMO_NOME,
            ),
            'autor_id' => $this->normalizarIdentificadorOpcional(
                $dados['autor_id']
                    ?? null,
                'autor_id',
            ),
            'proximo_nomeado_id' => $this->normalizarIdentificadorOpcional(
                $dados['proximo_nomeado_id']
                    ?? null,
                'proximo_nomeado_id',
            ),
            'seccoes' => $seccoes,
        ];
    }

    /**
     * Preenche os atributos principais de uma MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  Modelo a preencher.
     * @param  array<string, mixed>  $dados  Dados normalizados.
     *
     * @since 2.0.0
     */
    private function preencherMetalThursday(
        MetalThursday $metalThursday,
        array $dados,
    ): void {
        $metalThursday
            ->edicao()
            ->associate(
                $dados['edicao_id'],
            );

        $metalThursday->data =
            $dados['data'];

        $metalThursday->nome =
            $dados['nome'];

        if ($dados['autor_id'] === null) {
            $metalThursday
                ->autor()
                ->dissociate();
        } else {
            $metalThursday
                ->autor()
                ->associate(
                    $dados['autor_id'],
                );
        }

        if ($dados['proximo_nomeado_id'] === null) {
            $metalThursday
                ->proximoNomeado()
                ->dissociate();
        } else {
            $metalThursday
                ->proximoNomeado()
                ->associate(
                    $dados['proximo_nomeado_id'],
                );
        }
    }

    /**
     * Cria uma secção associada à MetalThursday.
     *
     * @param  int  $identificadorMetalThursday  Identificador da
     *                                           MetalThursday.
     * @param  array<string, mixed>  $dados  Dados normalizados.
     * @param  TipoSeccao  $tipoSeccao  Tipo da secção.
     * @param  int  $ordem  Posição da secção.
     *
     * @since 2.0.0
     */
    private function criarSeccao(
        int $identificadorMetalThursday,
        array $dados,
        TipoSeccao $tipoSeccao,
        int $ordem,
    ): void {
        $seccao =
            new SeccaoMetalThursday;

        $seccao
            ->metalThursday()
            ->associate(
                $identificadorMetalThursday,
            );

        $this->preencherSeccao(
            $seccao,
            $dados,
            $tipoSeccao,
            $ordem,
        );

        $seccao->saveOrFail();
    }

    /**
     * Preenche os atributos persistíveis de uma secção.
     *
     * Tipos detalhados exigem todos os campos musicais. Tipos simples
     * rejeitam esses campos e removem relações detalhadas que existissem numa
     * versão anterior da secção. A descrição permanece sempre obrigatória.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção a preencher.
     * @param  array<string, mixed>  $dados  Dados normalizados.
     * @param  TipoSeccao  $tipoSeccao  Tipo da secção.
     * @param  int  $ordem  Posição da secção.
     *
     * @throws InvalidArgumentException Quando a combinação da incorporação não
     *                                  é válida.
     *
     * @since 2.0.0
     */
    private function preencherSeccao(
        SeccaoMetalThursday $seccao,
        array $dados,
        TipoSeccao $tipoSeccao,
        int $ordem,
    ): void {
        $seccao
            ->tipoSeccao()
            ->associate(
                $tipoSeccao,
            );

        $seccao->ordem =
            $ordem;

        $seccao->descricao =
            $dados['descricao'];

        if (! $tipoSeccao->exige_detalhes) {
            $this->garantirAusenciaDetalhes(
                $dados,
            );

            $seccao
                ->artista()
                ->dissociate();

            $seccao->titulo = null;

            $seccao->ligacao = null;

            $seccao->tipo_incorporacao = null;

            $seccao->ano = null;

            return;
        }

        $this->garantirDetalhesObrigatorios(
            $dados,
        );

        $seccao
            ->artista()
            ->associate(
                $dados['artista_id'],
            );

        $seccao->titulo =
            $dados['titulo'];

        $seccao->ligacao =
            $dados['ligacao'];

        $seccao->tipo_incorporacao =
            $this->resolverTipoIncorporacao(
                $dados['ligacao'],
                $dados['tipo_incorporacao'],
            );

        $seccao->ano =
            $dados['ano'];
    }

    /**
     * Resolve o tipo de incorporação persistido.
     *
     * Sem ligação não pode existir um tipo de incorporação. Quando existe uma
     * ligação, o tipo deve ser indicado explicitamente.
     *
     * @param  string|null  $ligacao  Ligação recebida.
     * @param  TipoIncorporacao|null  $tipoRecebido  Tipo recebido.
     * @return TipoIncorporacao|null Tipo persistível ou nulo.
     *
     * @throws InvalidArgumentException Quando a combinação não é válida.
     *
     * @since 2.0.0
     */
    private function resolverTipoIncorporacao(
        ?string $ligacao,
        ?TipoIncorporacao $tipoRecebido,
    ): ?TipoIncorporacao {
        if ($ligacao === null) {
            if ($tipoRecebido !== null) {
                throw new InvalidArgumentException(
                    'Não pode ser indicado um tipo de incorporação sem uma ligação.',
                );
            }

            return null;
        }

        if (! $tipoRecebido instanceof TipoIncorporacao) {
            throw new InvalidArgumentException(
                'Uma ligação exige um tipo de incorporação explícito.',
            );
        }

        return $tipoRecebido;
    }

    /**
     * Bloqueia e confirma a edição principal.
     *
     * @param  array<string, mixed>  $dados  Dados normalizados.
     *
     * @throws InvalidArgumentException Quando a edição não existe ou a data não
     *                                  lhe pertence.
     *
     * @since 2.0.0
     */
    private function bloquearEdicaoPrincipal(
        array $dados,
    ): void {
        $edicao = Edicao::query()
            ->whereKey(
                $dados['edicao_id'],
            )
            ->lockForUpdate()
            ->first();

        if (! $edicao instanceof Edicao) {
            throw new InvalidArgumentException(
                'A edição indicada não existe ou não está disponível.',
            );
        }

        $this->garantirDataDentroDaEdicao(
            $dados['data'],
            $edicao,
        );
    }

    /**
     * Bloqueia e confirma os utilizadores das relações principais.
     *
     * @param  array<string, mixed>  $dados  Dados normalizados.
     *
     * @throws InvalidArgumentException Quando algum utilizador não existe.
     *
     * @since 2.0.0
     */
    private function bloquearUtilizadoresPrincipais(
        array $dados,
    ): void {
        $identificadoresUtilizadores = array_values(
            array_unique(
                array_filter(
                    [
                        $dados['autor_id'],
                        $dados['proximo_nomeado_id'],
                    ],
                    static fn (
                        mixed $identificador,
                    ): bool => is_int($identificador),
                ),
            ),
        );

        if ($identificadoresUtilizadores === []) {
            return;
        }

        sort(
            $identificadoresUtilizadores,
            SORT_NUMERIC,
        );

        $identificadoresExistentes = Utilizador::query()
            ->whereKey(
                $identificadoresUtilizadores,
            )
            ->orderBy(
                'id',
            )
            ->lockForUpdate()
            ->pluck(
                'id',
            )
            ->map(
                static fn (
                    mixed $identificador,
                ): int => (int) $identificador,
            )
            ->all();

        if (
            $identificadoresExistentes
            !== $identificadoresUtilizadores
        ) {
            throw new InvalidArgumentException(
                'Foi indicado um autor ou próximo nomeado inexistente.',
            );
        }
    }

    /**
     * Confirma que a data pertence ao período da edição.
     *
     * @param  string  $data  Data normalizada no formato AAAA-MM-DD.
     * @param  Edicao  $edicao  Edição bloqueada.
     *
     * @throws InvalidArgumentException Quando a data não pertence à edição.
     *
     * @since 2.0.0
     */
    private function garantirDataDentroDaEdicao(
        string $data,
        Edicao $edicao,
    ): void {
        $dataInicio =
            $edicao->data_inicio;

        if (
            $dataInicio instanceof CarbonInterface
            && $data < $dataInicio->format(
                'Y-m-d',
            )
        ) {
            throw new InvalidArgumentException(
                'A data da MetalThursday não pode ser anterior ao início da edição.',
            );
        }

        $dataFim =
            $edicao->data_fim;

        if (
            $dataFim instanceof CarbonInterface
            && $data > $dataFim->format(
                'Y-m-d',
            )
        ) {
            throw new InvalidArgumentException(
                'A data da MetalThursday não pode ser posterior ao fim da edição.',
            );
        }
    }

    /**
     * Confirma os detalhes obrigatórios de uma secção detalhada.
     *
     * @param  array<string, mixed>  $dados  Dados normalizados da secção.
     *
     * @throws InvalidArgumentException Quando falta algum detalhe obrigatório.
     *
     * @since 2.0.0
     */
    private function garantirDetalhesObrigatorios(
        array $dados,
    ): void {
        $camposObrigatorios = [
            'titulo' => 'O título é obrigatório numa secção detalhada.',
            'artista_id' => 'O artista é obrigatório numa secção detalhada.',
            'ligacao' => 'A ligação é obrigatória numa secção detalhada.',
            'tipo_incorporacao' => 'O tipo de incorporação é obrigatório numa secção detalhada.',
            'ano' => 'O ano é obrigatório numa secção detalhada.',
        ];

        foreach ($camposObrigatorios as $campo => $mensagem) {
            if ($dados[$campo] !== null) {
                continue;
            }

            throw new InvalidArgumentException(
                $mensagem,
            );
        }
    }

    /**
     * Confirma que um tipo simples não recebeu detalhes musicais.
     *
     * @param  array<string, mixed>  $dados  Dados normalizados da secção.
     *
     * @throws InvalidArgumentException Quando existe um detalhe incompatível.
     *
     * @since 2.0.0
     */
    private function garantirAusenciaDetalhes(
        array $dados,
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
            if ($dados[$campo] === null) {
                continue;
            }

            throw new InvalidArgumentException(
                'Uma secção sem detalhes não pode conter informação musical detalhada.',
            );
        }
    }

    /**
     * Obtém e bloqueia os tipos utilizados pelas secções.
     *
     * @param  list<array<string, mixed>>  $seccoes  Secções normalizadas.
     * @return ColecaoEloquent<int, TipoSeccao> Tipos indexados pelo
     *                                          identificador.
     *
     * @throws InvalidArgumentException Quando algum tipo não existe.
     *
     * @since 2.0.0
     */
    private function obterTiposSeccao(
        array $seccoes,
    ): ColecaoEloquent {
        $identificadores = array_values(
            array_unique(
                array_map(
                    static fn (
                        array $seccao,
                    ): int => $seccao['tipo_seccao_id'],
                    $seccoes,
                ),
            ),
        );

        sort(
            $identificadores,
            SORT_NUMERIC,
        );

        $tipos = TipoSeccao::query()
            ->whereKey(
                $identificadores,
            )
            ->orderBy(
                'id',
            )
            ->lockForUpdate()
            ->get()
            ->keyBy(
                static fn (
                    TipoSeccao $tipoSeccao,
                ): int => (int) $tipoSeccao->getKey(),
            );

        if (
            $tipos->count()
            !== count(
                $identificadores,
            )
        ) {
            throw new InvalidArgumentException(
                'Foi indicado um tipo de secção inexistente.',
            );
        }

        return $tipos;
    }

    /**
     * Obtém um tipo de secção previamente carregado.
     *
     * @param  ColecaoEloquent<int, TipoSeccao>  $tipos  Tipos disponíveis.
     * @param  int  $identificador  Identificador pretendido.
     * @return TipoSeccao Tipo encontrado.
     *
     * @throws LogicException Quando um tipo previamente validado não é
     *                        encontrado.
     *
     * @since 2.0.0
     */
    private function obterTipoSeccaoDaColecao(
        ColecaoEloquent $tipos,
        int $identificador,
    ): TipoSeccao {
        $tipoSeccao = $tipos->get(
            $identificador,
        );

        if ($tipoSeccao instanceof TipoSeccao) {
            return $tipoSeccao;
        }

        throw new LogicException(
            'Não foi possível obter um tipo de secção previamente validado.',
        );
    }

    /**
     * Bloqueia os artistas utilizados pelas secções.
     *
     * Na criação apenas artistas ativos são aceites. Durante uma atualização,
     * uma secção existente pode conservar o próprio artista que tenha sido
     * entretanto eliminado logicamente.
     *
     * Um artista eliminado não pode ser associado a uma secção nova nem
     * transferido para outra secção.
     *
     * @param  list<array<string, mixed>>  $seccoes  Secções recebidas.
     * @param  ColecaoEloquent<int, TipoSeccao>  $tiposSeccao  Tipos utilizados.
     * @param  ColecaoEloquent<int, SeccaoMetalThursday>|null  $seccoesExistentes
     *                                                                             Secções atuais.
     *
     * @throws InvalidArgumentException Quando algum artista não existe ou não está
     *                                  disponível.
     *
     * @since 2.0.0
     */
    private function bloquearArtistasUtilizados(
        array $seccoes,
        ColecaoEloquent $tiposSeccao,
        ?ColecaoEloquent $seccoesExistentes = null,
    ): void {
        $identificadores = [];
        $associacoes = [];

        foreach ($seccoes as $seccao) {
            $tipoSeccao = $this->obterTipoSeccaoDaColecao(
                $tiposSeccao,
                $seccao['tipo_seccao_id'],
            );

            if (
                ! $tipoSeccao->exige_detalhes
                || $seccao['artista_id'] === null
            ) {
                continue;
            }

            $identificadorArtista =
                $seccao['artista_id'];

            $identificadores[] =
                $identificadorArtista;

            $associacoes[] = [
                'artista_id' => $identificadorArtista,
                'seccao_id' => $seccao['id'],
            ];
        }

        $identificadores = array_values(
            array_unique(
                $identificadores,
            ),
        );

        sort(
            $identificadores,
            SORT_NUMERIC,
        );

        if ($identificadores === []) {
            return;
        }

        $artistas = Artista::withTrashed()
            ->whereKey(
                $identificadores,
            )
            ->orderBy(
                'id',
            )
            ->lockForUpdate()
            ->get([
                'id',
                'deleted_at',
            ])
            ->keyBy(
                static fn (
                    Artista $artista,
                ): int => (int) $artista->getKey(),
            );

        foreach ($associacoes as $associacao) {
            $identificadorArtista =
                $associacao['artista_id'];

            $artista =
                $artistas->get(
                    $identificadorArtista,
                );

            if (! $artista instanceof Artista) {
                throw new InvalidArgumentException(
                    'Foi indicado um artista inexistente ou indisponível.',
                );
            }

            if (! $artista->trashed()) {
                continue;
            }

            if (! $seccoesExistentes instanceof ColecaoEloquent) {
                throw new InvalidArgumentException(
                    'Foi indicado um artista inexistente ou indisponível.',
                );
            }

            $identificadorSeccao =
                $associacao['seccao_id'];

            if (
                ! is_int($identificadorSeccao)
                || $identificadorSeccao < 1
            ) {
                throw new InvalidArgumentException(
                    'Foi indicado um artista inexistente ou indisponível.',
                );
            }

            $seccaoExistente =
                $seccoesExistentes->get(
                    $identificadorSeccao,
                );

            if (
                ! $seccaoExistente instanceof SeccaoMetalThursday
                || ! is_numeric(
                    $seccaoExistente->artista_id,
                )
                || (int) $seccaoExistente->artista_id
                !== $identificadorArtista
            ) {
                throw new InvalidArgumentException(
                    'Foi indicado um artista inexistente ou indisponível.',
                );
            }
        }
    }

    /**
     * Confirma que as secções recebidas pertencem à MetalThursday.
     *
     * @param  list<array<string, mixed>>  $seccoes  Secções recebidas.
     * @param  ColecaoEloquent<int, SeccaoMetalThursday>  $existentes  Secções
     *                                                                 atuais.
     *
     * @throws InvalidArgumentException Quando um identificador é repetido ou
     *                                  pertence a outra MetalThursday.
     *
     * @since 2.0.0
     */
    private function garantirIdentificadoresSeccoesValidos(
        array $seccoes,
        ColecaoEloquent $existentes,
    ): void {
        $identificadoresRecebidos = [];

        foreach ($seccoes as $seccao) {
            $identificador =
                $seccao['id'];

            if ($identificador === null) {
                continue;
            }

            if (
                array_key_exists(
                    $identificador,
                    $identificadoresRecebidos,
                )
            ) {
                throw new InvalidArgumentException(
                    'Uma secção foi enviada mais do que uma vez.',
                );
            }

            if (! $existentes->has($identificador)) {
                throw new InvalidArgumentException(
                    'Foi indicada uma secção que não pertence à MetalThursday.',
                );
            }

            $identificadoresRecebidos[$identificador] =
                true;
        }
    }

    /**
     * Reserva ordens temporárias exclusivas para as secções existentes.
     *
     * A operação evita colisões na coluna gerada `ordem_ativa` quando a ordem
     * final troca as posições de dois registos ativos.
     *
     * @param  ColecaoEloquent<int, SeccaoMetalThursday>  $seccoes  Secções
     *                                                              bloqueadas.
     * @param  int  $numeroSecoesRecebidas  Número de posições finais.
     *
     * @since 2.0.0
     */
    private function reservarOrdensTemporarias(
        ColecaoEloquent $seccoes,
        int $numeroSecoesRecebidas,
    ): void {
        if ($seccoes->isEmpty()) {
            return;
        }

        $maiorOrdemExistente = $seccoes
            ->pluck(
                'ordem',
            )
            ->map(
                static fn (
                    mixed $ordem,
                ): int => (int) $ordem,
            )
            ->max();

        $primeiraOrdemTemporaria =
            max(
                is_int($maiorOrdemExistente)
                    ? $maiorOrdemExistente
                    : 0,
                $numeroSecoesRecebidas,
            )
            + SeccaoMetalThursday::ORDEM_MINIMA;

        foreach (
            $seccoes
                ->sortKeys()
                ->values() as $indice => $seccao
        ) {
            $seccao->ordem =
                $primeiraOrdemTemporaria
                + $indice;

            $seccao->saveOrFail();
        }
    }

    /**
     * Elimina logicamente as secções que deixaram de ser enviadas.
     *
     * @param  list<array<string, mixed>>  $seccoes  Secções recebidas.
     * @param  ColecaoEloquent<int, SeccaoMetalThursday>  $existentes  Secções
     *                                                                 atuais.
     *
     * @since 2.0.0
     */
    private function eliminarSeccoesAusentes(
        array $seccoes,
        ColecaoEloquent $existentes,
    ): void {
        $identificadoresRecebidos = array_values(
            array_filter(
                array_map(
                    static fn (
                        array $seccao,
                    ): ?int => $seccao['id'],
                    $seccoes,
                ),
                static fn (
                    ?int $identificador,
                ): bool => $identificador !== null,
            ),
        );

        $identificadoresRemover = $existentes
            ->keys()
            ->diff(
                $identificadoresRecebidos,
            )
            ->values()
            ->all();

        if ($identificadoresRemover === []) {
            return;
        }

        SeccaoMetalThursday::query()
            ->whereKey(
                $identificadoresRemover,
            )
            ->delete();
    }

    /**
     * Obtém o identificador inteiro de uma MetalThursday persistida.
     *
     * @param  MetalThursday  $modelo  Modelo recebido.
     * @return int Identificador do modelo.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorPersistido(
        MetalThursday $modelo,
    ): int {
        if (! $modelo->exists) {
            throw new InvalidArgumentException(
                'A MetalThursday deve estar persistida.',
            );
        }

        $identificador =
            $modelo->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (! is_string($identificador)) {
            throw new InvalidArgumentException(
                'A MetalThursday deve possuir um identificador válido.',
            );
        }

        $identificadorNormalizado = trim(
            $identificador,
        );

        if (
            $identificadorNormalizado === ''
            || ! ctype_digit(
                $identificadorNormalizado,
            )
            || (int) $identificadorNormalizado < 1
        ) {
            throw new InvalidArgumentException(
                'A MetalThursday deve possuir um identificador válido.',
            );
        }

        return (int) $identificadorNormalizado;
    }

    /**
     * Normaliza um identificador obrigatório.
     *
     * @param  mixed  $valor  Valor recebido.
     * @param  string  $campo  Nome do campo.
     * @return int Identificador normalizado.
     *
     * @throws InvalidArgumentException Quando o identificador não é válido.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificadorObrigatorio(
        mixed $valor,
        string $campo,
    ): int {
        $identificador = $this->normalizarIdentificadorOpcional(
            $valor,
            $campo,
        );

        if ($identificador !== null) {
            return $identificador;
        }

        throw new InvalidArgumentException(
            sprintf(
                'O campo %s é obrigatório.',
                $campo,
            ),
        );
    }

    /**
     * Normaliza um identificador opcional.
     *
     * @param  mixed  $valor  Valor recebido.
     * @param  string  $campo  Nome do campo.
     * @return int|null Identificador normalizado ou nulo.
     *
     * @throws InvalidArgumentException Quando o valor não é válido.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificadorOpcional(
        mixed $valor,
        string $campo,
    ): ?int {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (
            is_int($valor)
            && $valor > 0
        ) {
            return $valor;
        }

        if (is_string($valor)) {
            $valorNormalizado = trim(
                $valor,
            );

            if (
                $valorNormalizado !== ''
                && ctype_digit(
                    $valorNormalizado,
                )
                && (int) $valorNormalizado > 0
            ) {
                return (int) $valorNormalizado;
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'O campo %s não contém um identificador válido.',
                $campo,
            ),
        );
    }

    /**
     * Normaliza uma data no formato AAAA-MM-DD.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string Data normalizada.
     *
     * @throws InvalidArgumentException Quando a data não é válida.
     *
     * @since 2.0.0
     */
    private function normalizarData(
        mixed $valor,
    ): string {
        if (! is_string($valor)) {
            throw new InvalidArgumentException(
                'A data da MetalThursday não é válida.',
            );
        }

        $data = trim(
            $valor,
        );

        if (
            preg_match(
                '/\A\d{4}-\d{2}-\d{2}\z/',
                $data,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'A data da MetalThursday não é válida.',
            );
        }

        [
            $ano,
            $mes,
            $dia,
        ] = array_map(
            static fn (
                string $parte,
            ): int => (int) $parte,
            explode(
                '-',
                $data,
            ),
        );

        if (
            ! checkdate(
                $mes,
                $dia,
                $ano,
            )
        ) {
            throw new InvalidArgumentException(
                'A data da MetalThursday não existe.',
            );
        }

        return sprintf(
            '%04d-%02d-%02d',
            $ano,
            $mes,
            $dia,
        );
    }

    /**
     * Normaliza um texto opcional de uma única linha.
     *
     * @param  mixed  $valor  Valor recebido.
     * @param  string  $campo  Nome do campo.
     * @param  int  $comprimentoMaximo  Comprimento máximo.
     * @return string|null Texto normalizado ou nulo.
     *
     * @throws InvalidArgumentException Quando o texto não é válido.
     *
     * @since 2.0.0
     */
    private function normalizarTextoLinhaOpcional(
        mixed $valor,
        string $campo,
        int $comprimentoMaximo,
    ): ?string {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (
            ! is_string($valor)
            || preg_match(
                '//u',
                $valor,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s deve conter texto válido.',
                    $campo,
                ),
            );
        }

        if (
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $valor,
            ) === 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s contém caracteres inválidos.',
                    $campo,
                ),
            );
        }

        $texto = preg_replace(
            '/\s+/u',
            ' ',
            $valor,
        );

        if (! is_string($texto)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Não foi possível normalizar o campo %s.',
                    $campo,
                ),
            );
        }

        $texto = trim(
            $texto,
        );

        if ($texto === '') {
            return null;
        }

        if (
            mb_strlen(
                $texto,
            ) > $comprimentoMaximo
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s não pode exceder %d caracteres.',
                    $campo,
                    $comprimentoMaximo,
                ),
            );
        }

        return $texto;
    }

    /**
     * Normaliza a descrição obrigatória de uma secção.
     *
     * @param  mixed  $valor  Valor recebido.
     * @param  string  $campo  Nome do campo.
     * @return string Descrição normalizada.
     *
     * @throws InvalidArgumentException Quando a descrição não é válida.
     *
     * @since 2.0.0
     */
    private function normalizarDescricaoObrigatoria(
        mixed $valor,
        string $campo,
    ): string {
        if (
            ! is_string($valor)
            || preg_match(
                '//u',
                $valor,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s deve conter texto válido.',
                    $campo,
                ),
            );
        }

        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $valor,
            ) === 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s contém caracteres inválidos.',
                    $campo,
                ),
            );
        }

        $descricao = trim(
            str_replace(
                [
                    "\r\n",
                    "\r",
                ],
                "\n",
                $valor,
            ),
        );

        if ($descricao === '') {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s é obrigatório.',
                    $campo,
                ),
            );
        }

        if (
            mb_strlen(
                $descricao,
            ) > SeccaoMetalThursday::COMPRIMENTO_MAXIMO_DESCRICAO
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s não pode exceder %d caracteres.',
                    $campo,
                    SeccaoMetalThursday::COMPRIMENTO_MAXIMO_DESCRICAO,
                ),
            );
        }

        return $descricao;
    }

    /**
     * Normaliza e valida uma ligação HTTP ou HTTPS.
     *
     * @param  mixed  $valor  Valor recebido.
     * @param  string  $campo  Nome do campo.
     * @return string|null Ligação válida ou nula.
     *
     * @throws InvalidArgumentException Quando a ligação não é válida.
     *
     * @since 2.0.0
     */
    private function normalizarLigacao(
        mixed $valor,
        string $campo,
    ): ?string {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (
            ! is_string($valor)
            || preg_match(
                '//u',
                $valor,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s deve conter uma ligação válida.',
                    $campo,
                ),
            );
        }

        $ligacao = trim(
            $valor,
            ' ',
        );

        if ($ligacao === '') {
            return null;
        }

        if (
            mb_strlen(
                $ligacao,
            ) > SeccaoMetalThursday::COMPRIMENTO_MAXIMO_LIGACAO
            || str_contains(
                $ligacao,
                '\\',
            )
            || preg_match(
                '/[\x00-\x20\x7F]/',
                $ligacao,
            ) === 1
            || filter_var(
                $ligacao,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s não contém uma ligação válida.',
                    $campo,
                ),
            );
        }

        $componentes = parse_url(
            $ligacao,
        );

        if (
            ! is_array($componentes)
            || ! isset(
                $componentes['scheme'],
                $componentes['host'],
            )
            || isset(
                $componentes['user'],
            )
            || isset(
                $componentes['pass'],
            )
            || trim(
                (string) $componentes['host'],
            ) === ''
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s não contém uma ligação válida.',
                    $campo,
                ),
            );
        }

        $esquema = mb_strtolower(
            (string) $componentes['scheme'],
        );

        if (
            ! in_array(
                $esquema,
                [
                    'http',
                    'https',
                ],
                true,
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s deve utilizar HTTP ou HTTPS.',
                    $campo,
                ),
            );
        }

        return $ligacao;
    }

    /**
     * Normaliza o tipo de incorporação recebido.
     *
     * Apenas os valores finais persistidos pela enumeração são aceites.
     *
     * @param  mixed  $valor  Valor recebido.
     * @param  string  $campo  Nome do campo.
     * @return TipoIncorporacao|null Tipo normalizado ou nulo.
     *
     * @throws InvalidArgumentException Quando o valor não é válido.
     *
     * @since 2.0.0
     */
    private function normalizarTipoIncorporacao(
        mixed $valor,
        string $campo,
    ): ?TipoIncorporacao {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if ($valor instanceof TipoIncorporacao) {
            return $valor;
        }

        if (! is_string($valor)) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s não contém um tipo de incorporação válido.',
                    $campo,
                ),
            );
        }

        $tipo = TipoIncorporacao::tryFrom(
            trim(
                $valor,
            ),
        );

        if ($tipo instanceof TipoIncorporacao) {
            return $tipo;
        }

        throw new InvalidArgumentException(
            sprintf(
                'O campo %s não contém um tipo de incorporação válido.',
                $campo,
            ),
        );
    }

    /**
     * Normaliza o ano de uma secção.
     *
     * @param  mixed  $valor  Valor recebido.
     * @param  string  $campo  Nome do campo.
     * @return int|null Ano normalizado ou nulo.
     *
     * @throws InvalidArgumentException Quando o ano não é válido.
     *
     * @since 2.0.0
     */
    private function normalizarAno(
        mixed $valor,
        string $campo,
    ): ?int {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (
            is_int($valor)
            && $valor >= SeccaoMetalThursday::ANO_MINIMO
            && $valor <= SeccaoMetalThursday::ANO_MAXIMO
        ) {
            return $valor;
        }

        if (is_string($valor)) {
            $valorNormalizado = trim(
                $valor,
            );

            if (
                $valorNormalizado !== ''
                && ctype_digit(
                    $valorNormalizado,
                )
            ) {
                $ano =
                    (int) $valorNormalizado;

                if (
                    $ano >= SeccaoMetalThursday::ANO_MINIMO
                    && $ano <= SeccaoMetalThursday::ANO_MAXIMO
                ) {
                    return $ano;
                }
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'O campo %s deve estar compreendido entre %d e %d.',
                $campo,
                SeccaoMetalThursday::ANO_MINIMO,
                SeccaoMetalThursday::ANO_MAXIMO,
            ),
        );
    }

    /**
     * Cria a reserva da quinta-feira seguinte para o nomeado indicado.
     *
     * Se o slot seguinte já existir, o serviço de reservas preserva-o sem
     * qualquer alteração. Assim, uma publicação tardia não consegue substituir
     * uma decisão já tomada pelo fallback.
     *
     * @param  array<string, mixed>  $dados  Dados normalizados.
     * @return ReservaMetalThursday|null Reserva criada ou nulo quando não foi
     *                                   criado um novo slot.
     *
     * @since 2.0.0
     */
    private function criarReservaSeguinte(
        array $dados,
    ): ?ReservaMetalThursday {
        $identificadorNomeado =
            $dados['proximo_nomeado_id'];

        if ($identificadorNomeado === null) {
            return null;
        }

        return $this
            ->servicoReservas
            ->criarReservaParaNomeado(
                $this->obterDataReservaSeguinte(
                    $dados['data'],
                ),
                $identificadorNomeado,
            );
    }

    /**
     * Obtém e bloqueia a reserva seguinte efetivamente válida.
     *
     * A consulta é necessária quando a tentativa de criação devolve nulo, quer
     * porque o slot já existia, quer porque não foi proposto um nomeado. Dessa
     * forma, o campo legado da MetalThursday pode espelhar a decisão persistida
     * em `reservas_metal_thursday`.
     *
     * @param  array<string, mixed>  $dados  Dados normalizados.
     * @return ReservaMetalThursday|null Reserva efetiva ou nulo quando o slot
     *                                   ainda não existe.
     *
     * @since 2.0.0
     */
    private function obterReservaSeguinteEfetiva(
        array $dados,
    ): ?ReservaMetalThursday {
        return ReservaMetalThursday::query()
            ->where(
                'data',
                $this
                    ->obterDataReservaSeguinte(
                        $dados['data'],
                    )
                    ->toDateString(),
            )
            ->lockForUpdate()
            ->first();
    }

    /**
     * Substitui a proposta recebida pelo responsável da reserva efetiva.
     *
     * `proximo_nomeado_id` permanece temporariamente na MetalThursday apenas
     * como espelho de compatibilidade. A reserva seguinte é sempre a fonte de
     * verdade, incluindo quando o fallback já tinha decidido outro responsável
     * ou quando o slot existe sem responsável atribuído.
     *
     * @param  array<string, mixed>  $dados  Dados normalizados.
     * @param  ReservaMetalThursday|null  $reservaSeguinte  Reserva efetiva.
     * @return array<string, mixed> Dados preparados para persistência.
     *
     * @since 2.0.0
     */
    private function substituirNomeadoPeloResponsavelEfetivo(
        array $dados,
        ?ReservaMetalThursday $reservaSeguinte,
    ): array {
        $dados['proximo_nomeado_id'] =
            $reservaSeguinte instanceof ReservaMetalThursday
            && is_numeric(
                $reservaSeguinte->responsavel_id,
            )
            ? (int) $reservaSeguinte->responsavel_id
            : null;

        return $dados;
    }

    /**
     * Preserva durante a edição o espelho da nomeação já persistida.
     *
     * A atualização de conteúdo não pode alterar uma decisão que já produziu
     * uma reserva real. A camada HTTP deixará também de apresentar este campo
     * como editável, mas o serviço mantém esta proteção independentemente do
     * pedido recebido.
     *
     * @param  array<string, mixed>  $dados  Dados normalizados.
     * @param  MetalThursday  $metalThursday  Registo bloqueado.
     * @return array<string, mixed> Dados preparados para persistência.
     *
     * @since 2.0.0
     */
    private function preservarNomeadoPersistidoNaAtualizacao(
        array $dados,
        MetalThursday $metalThursday,
    ): array {
        $dados['proximo_nomeado_id'] =
            is_numeric(
                $metalThursday->proximo_nomeado_id,
            )
            ? (int) $metalThursday->proximo_nomeado_id
            : null;

        return $dados;
    }

    /**
     * Calcula a quinta-feira seguinte à data da MetalThursday.
     *
     * A criação administrativa pode utilizar uma data que não seja quinta-feira,
     * pelo que o cálculo procura explicitamente a próxima quinta-feira em vez de
     * adicionar simplesmente uma semana.
     *
     * @param  string  $data  Data normalizada da MetalThursday.
     * @return CarbonImmutable Quinta-feira seguinte no início do dia.
     *
     * @since 2.0.0
     */
    private function obterDataReservaSeguinte(
        string $data,
    ): CarbonImmutable {
        return CarbonImmutable::parse(
            $data,
        )
            ->next(
                CarbonImmutable::THURSDAY,
            )
            ->startOfDay();
    }

    /**
     * Obtém e bloqueia a reserva correspondente à data criada.
     *
     * Quando existe um responsável atribuído, o autor tem obrigatoriamente de
     * coincidir com esse utilizador. Um slot sem responsável pode ser tratado
     * administrativamente.
     *
     * @param  array<string, mixed>  $dados  Dados normalizados.
     * @return ReservaMetalThursday|null Reserva encontrada ou nulo.
     *
     * @throws InvalidArgumentException Quando a reserva não pode ser cumprida
     *                                  pela criação recebida.
     *
     * @since 2.0.0
     */
    private function obterReservaDaDataParaCriacao(
        array $dados,
    ): ?ReservaMetalThursday {
        $reserva = ReservaMetalThursday::query()
            ->where(
                'data',
                $dados['data'],
            )
            ->lockForUpdate()
            ->first();

        if (! $reserva instanceof ReservaMetalThursday) {
            return null;
        }

        if (! $reserva->estaPendente()) {
            throw new InvalidArgumentException(
                'A reserva da data indicada já se encontra cumprida.',
            );
        }

        $identificadorResponsavel =
            $reserva->responsavel_id;

        $identificadorAutor =
            $dados['autor_id'];

        if (
            $identificadorResponsavel !== null
            && $identificadorResponsavel
            !== $identificadorAutor
        ) {
            throw new InvalidArgumentException(
                'O autor não corresponde ao responsável da reserva.',
            );
        }

        return $reserva;
    }

    /**
     * Marca uma reserva como cumprida pela MetalThursday criada.
     *
     * Num slot originalmente sem responsável, o autor passa a representar o
     * responsável que efectivamente tratou da publicação.
     *
     * @param  ReservaMetalThursday|null  $reserva  Reserva encontrada.
     * @param  MetalThursday  $metalThursday  MetalThursday criada.
     * @param  int|null  $identificadorAutor  Autor persistido.
     *
     * @since 2.0.0
     */
    private function cumprirReserva(
        ?ReservaMetalThursday $reserva,
        MetalThursday $metalThursday,
        ?int $identificadorAutor,
    ): void {
        if (! $reserva instanceof ReservaMetalThursday) {
            return;
        }

        if (
            $reserva->responsavel_id === null
            && $identificadorAutor !== null
        ) {
            $reserva->responsavel_id =
                $identificadorAutor;
        }

        $reserva
            ->metalThursday()
            ->associate(
                $metalThursday,
            );

        $reserva->saveOrFail();
    }
}
