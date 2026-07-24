<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Gere a criação e a atualização transacional de MetalThursdays.
 *
 * Os dados principais e as respetivas secções são validados, normalizados e
 * persistidos atomicamente. Durante uma atualização, a MetalThursday e as
 * secções existentes são bloqueadas para impedir alterações concorrentes.
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class ServicoPersistenciaMetalThursday
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Comprimento máximo do nome de uma MetalThursday.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_NOME = 255;

    /**
     * Comprimento máximo do título de uma secção.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_TITULO = 255;

    /**
     * Comprimento máximo de uma ligação.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_LIGACAO = 2048;

    /**
     * Comprimento máximo do tipo de incorporação.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_TIPO_INCORPORACAO = 32;

    /**
     * Comprimento máximo da descrição de uma secção.
     *
     * Corresponde ao limite da coluna SQL do tipo TEXT.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_DESCRICAO = 65535;

    /**
     * Primeiro ano válido numa coluna YEAR do MySQL.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ANO_MINIMO = 1901;

    /**
     * Último ano válido numa coluna YEAR do MySQL.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ANO_MAXIMO = 2155;

    /**
     * Cria uma MetalThursday e as respetivas secções.
     *
     * @param  array<string, mixed>  $dados  Dados validados.
     * @return MetalThursday MetalThursday criada.
     *
     * @throws InvalidArgumentException Quando os dados ou relações não são
     *                                  válidos.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function criar(
        array $dados,
    ): MetalThursday {
        $dadosNormalizados =
            $this->normalizarDados(
                $dados,
            );

        return DB::transaction(
            function () use (
                $dadosNormalizados,
            ): MetalThursday {
                $this->garantirRelacoesPrincipaisExistentes(
                    $dadosNormalizados,
                );

                $tiposSecao =
                    $this->obterTiposSecao(
                        $dadosNormalizados['seccoes'],
                    );

                $this->garantirBandasExistentes(
                    $dadosNormalizados['seccoes'],
                );

                $metalThursday =
                    new MetalThursday;

                $this->preencherMetalThursday(
                    $metalThursday,
                    $dadosNormalizados,
                );

                $metalThursday->saveOrFail();

                $identificadorMetalThursday =
                    $this->obterIdentificadorPersistido(
                        $metalThursday,
                        'MetalThursday',
                    );

                foreach (
                    $dadosNormalizados['seccoes'] as $indice => $dadosSeccao
                ) {
                    $tipoSecao =
                        $this->obterTipoSecaoDaColecao(
                            $tiposSecao,
                            $dadosSeccao['tipo_secao_id'],
                        );

                    $this->criarSeccao(
                        $identificadorMetalThursday,
                        $dadosSeccao,
                        $tipoSecao,
                        $indice + 1,
                    );
                }

                return $metalThursday
                    ->refresh()
                    ->load(
                        'seccoes',
                    );
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Atualiza uma MetalThursday e sincroniza as respetivas secções.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday atualizada.
     * @param  array<string, mixed>  $dados  Dados validados.
     * @return MetalThursday MetalThursday atualizada.
     *
     * @throws InvalidArgumentException Quando os dados ou relações não são
     *                                  válidos.
     * @throws ModelNotFoundException Quando a MetalThursday deixou de existir.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function atualizar(
        MetalThursday $metalThursday,
        array $dados,
    ): MetalThursday {
        $identificadorMetalThursday =
            $this->obterIdentificadorPersistido(
                $metalThursday,
                'MetalThursday',
            );

        $dadosNormalizados =
            $this->normalizarDados(
                $dados,
            );

        return DB::transaction(
            function () use (
                $identificadorMetalThursday,
                $dadosNormalizados,
            ): MetalThursday {
                $metalThursdayBloqueada =
                    MetalThursday::query()
                        ->whereKey(
                            $identificadorMetalThursday,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $seccoesExistentes =
                    SeccaoMetalThursday::query()
                        ->where(
                            'metal_thursday_id',
                            $identificadorMetalThursday,
                        )
                        ->lockForUpdate()
                        ->get()
                        ->keyBy(
                            static fn (
                                SeccaoMetalThursday $seccao,
                            ): int => (int) $seccao->getKey(),
                        );

                $this->garantirIdentificadoresSecoesValidos(
                    $dadosNormalizados['seccoes'],
                    $seccoesExistentes,
                );

                $this->garantirRelacoesPrincipaisExistentes(
                    $dadosNormalizados,
                );

                $tiposSecao =
                    $this->obterTiposSecao(
                        $dadosNormalizados['seccoes'],
                    );

                $this->garantirBandasExistentes(
                    $dadosNormalizados['seccoes'],
                );

                $this->preencherMetalThursday(
                    $metalThursdayBloqueada,
                    $dadosNormalizados,
                );

                $metalThursdayBloqueada->saveOrFail();

                $this->eliminarSecoesAusentes(
                    $identificadorMetalThursday,
                    $dadosNormalizados['seccoes'],
                    $seccoesExistentes,
                );

                foreach (
                    $dadosNormalizados['seccoes'] as $indice => $dadosSeccao
                ) {
                    $tipoSecao =
                        $this->obterTipoSecaoDaColecao(
                            $tiposSecao,
                            $dadosSeccao['tipo_secao_id'],
                        );

                    $identificadorSeccao =
                        $dadosSeccao['id'];

                    if ($identificadorSeccao === null) {
                        $this->criarSeccao(
                            $identificadorMetalThursday,
                            $dadosSeccao,
                            $tipoSecao,
                            $indice + 1,
                        );

                        continue;
                    }

                    $seccao =
                        $seccoesExistentes->get(
                            $identificadorSeccao,
                        );

                    if (
                        ! $seccao
                            instanceof SeccaoMetalThursday
                    ) {
                        throw new InvalidArgumentException(
                            'Foi indicada uma secção inválida.',
                        );
                    }

                    $this->preencherSeccao(
                        $seccao,
                        $dadosSeccao,
                        $tipoSecao,
                        $indice + 1,
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
     *         tipo_secao_id: int,
     *         banda_id: int|null,
     *         titulo: string|null,
     *         ligacao: string|null,
     *         tipo_incorporacao: string|null,
     *         ano: int|null,
     *         descricao: string|null
     *     }>
     * } Dados normalizados.
     *
     * @throws InvalidArgumentException Quando algum valor não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
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

        foreach ($seccoesRecebidas as $dadosSeccao) {
            if (! is_array($dadosSeccao)) {
                throw new InvalidArgumentException(
                    'Foi recebida uma secção inválida.',
                );
            }

            $seccoes[] = [
                'id' => $this->normalizarIdentificadorOpcional(
                    $dadosSeccao['id']
                        ?? null,
                    'seccoes.id',
                ),

                'tipo_secao_id' => $this->normalizarIdentificadorObrigatorio(
                    $dadosSeccao['tipo_secao_id']
                        ?? null,
                    'seccoes.tipo_secao_id',
                ),

                'banda_id' => $this->normalizarIdentificadorOpcional(
                    $dadosSeccao['banda_id']
                        ?? null,
                    'seccoes.banda_id',
                ),

                'titulo' => $this->normalizarTextoLinhaOpcional(
                    $dadosSeccao['titulo']
                        ?? null,
                    'seccoes.titulo',
                    self::COMPRIMENTO_MAXIMO_TITULO,
                ),

                'ligacao' => $this->normalizarLigacao(
                    $dadosSeccao['ligacao']
                        ?? null,
                ),

                'tipo_incorporacao' => $this->normalizarTipoIncorporacaoRecebido(
                    $dadosSeccao['tipo_incorporacao']
                        ?? null,
                ),

                'ano' => $this->normalizarAno(
                    $dadosSeccao['ano']
                        ?? null,
                ),

                'descricao' => $this->normalizarDescricao(
                    $dadosSeccao['descricao']
                        ?? null,
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
                self::COMPRIMENTO_MAXIMO_NOME,
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
     * @param array{
     *     edicao_id: int,
     *     data: string,
     *     nome: string|null,
     *     autor_id: int|null,
     *     proximo_nomeado_id: int|null,
     *     seccoes: array<mixed>
     * } $dados Dados normalizados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function preencherMetalThursday(
        MetalThursday $metalThursday,
        array $dados,
    ): void {
        $metalThursday->edicao_id =
            $dados['edicao_id'];

        $metalThursday->data =
            $dados['data'];

        $metalThursday->nome =
            $dados['nome'];

        $metalThursday->autor_id =
            $dados['autor_id'];

        $metalThursday->proximo_nomeado_id =
            $dados['proximo_nomeado_id'];
    }

    /**
     * Cria uma secção associada à MetalThursday.
     *
     * @param  int  $identificadorMetalThursday  Identificador da MetalThursday.
     * @param  array<string, mixed>  $dados  Dados normalizados.
     * @param  TipoSeccao  $tipoSecao  Tipo da secção.
     * @param  int  $ordem  Posição da secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarSeccao(
        int $identificadorMetalThursday,
        array $dados,
        TipoSeccao $tipoSecao,
        int $ordem,
    ): void {
        $seccao =
            new SeccaoMetalThursday;

        $seccao->metal_thursday_id =
            $identificadorMetalThursday;

        $this->preencherSeccao(
            $seccao,
            $dados,
            $tipoSecao,
            $ordem,
        );

        $seccao->saveOrFail();
    }

    /**
     * Preenche os atributos persistíveis de uma secção.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção a preencher.
     * @param  array<string, mixed>  $dados  Dados normalizados.
     * @param  TipoSeccao  $tipoSecao  Tipo da secção.
     * @param  int  $ordem  Posição da secção.
     *
     * @throws InvalidArgumentException Quando a incorporação não é válida.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function preencherSeccao(
        SeccaoMetalThursday $seccao,
        array $dados,
        TipoSeccao $tipoSecao,
        int $ordem,
    ): void {
        $seccao->tipo_secao_id =
            (int) $tipoSecao->getKey();

        $seccao->ordem =
            $ordem;

        if (! $tipoSecao->tem_detalhes) {
            $seccao->banda_id =
                null;

            $seccao->titulo =
                null;

            $seccao->ligacao =
                null;

            $seccao->tipo_incorporacao =
                null;

            $seccao->ano =
                null;

            $seccao->descricao =
                $dados['descricao'];

            return;
        }

        $seccao->banda_id =
            $dados['banda_id'];

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

        $seccao->descricao =
            $dados['descricao'];
    }

    /**
     * Resolve o tipo de incorporação persistido.
     *
     * Sem ligação não pode existir um tipo de incorporação. Quando existe uma
     * ligação mas o tipo não foi indicado, é utilizada uma ligação externa.
     *
     * @param  string|null  $ligacao  Ligação recebida.
     * @param  string|null  $tipoRecebido  Tipo recebido.
     * @return string|null Valor persistível.
     *
     * @throws InvalidArgumentException Quando a combinação não é válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function resolverTipoIncorporacao(
        ?string $ligacao,
        ?string $tipoRecebido,
    ): ?string {
        if ($ligacao === null) {
            if ($tipoRecebido !== null) {
                throw new InvalidArgumentException(
                    'Não pode ser indicado um tipo de incorporação sem uma ligação.',
                );
            }

            return null;
        }

        if ($tipoRecebido === null) {
            return TipoIncorporacao::Ligacao->value;
        }

        $tipo =
            TipoIncorporacao::tentarCriar(
                $tipoRecebido,
            );

        if (! $tipo instanceof TipoIncorporacao) {
            throw new InvalidArgumentException(
                'O tipo de incorporação indicado não é válido.',
            );
        }

        return $tipo->value;
    }

    /**
     * Obtém os tipos utilizados pelas secções.
     *
     * @param  list<array<string, mixed>>  $seccoes  Secções normalizadas.
     * @return Collection<int, TipoSeccao> Tipos encontrados.
     *
     * @throws InvalidArgumentException Quando algum tipo não existe.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterTiposSecao(
        array $seccoes,
    ): Collection {
        $identificadores =
            array_values(
                array_unique(
                    array_map(
                        static fn (
                            array $seccao,
                        ): int => (int) $seccao['tipo_secao_id'],
                        $seccoes,
                    ),
                ),
            );

        $tipos =
            TipoSeccao::query()
                ->whereKey(
                    $identificadores,
                )
                ->get()
                ->keyBy(
                    static fn (
                        TipoSeccao $tipoSecao,
                    ): int => (int) $tipoSecao->getKey(),
                );

        if (
            $tipos->count()
            !== count($identificadores)
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
     * @param  Collection<int, TipoSeccao>  $tipos  Tipos disponíveis.
     * @param  int  $identificador  Identificador pretendido.
     * @return TipoSeccao Tipo encontrado.
     *
     * @throws InvalidArgumentException Quando o tipo não foi encontrado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterTipoSecaoDaColecao(
        Collection $tipos,
        int $identificador,
    ): TipoSeccao {
        $tipoSecao =
            $tipos->get(
                $identificador,
            );

        if ($tipoSecao instanceof TipoSeccao) {
            return $tipoSecao;
        }

        throw new InvalidArgumentException(
            'Foi indicado um tipo de secção inexistente.',
        );
    }

    /**
     * Confirma a existência das relações principais.
     *
     * @param  array<string, mixed>  $dados  Dados normalizados.
     *
     * @throws InvalidArgumentException Quando alguma relação não existe.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function garantirRelacoesPrincipaisExistentes(
        array $dados,
    ): void {
        if (
            ! Edicao::query()
                ->whereKey(
                    $dados['edicao_id'],
                )
                ->exists()
        ) {
            throw new InvalidArgumentException(
                'A edição indicada não existe ou não está disponível.',
            );
        }

        $identificadoresUtilizadores =
            array_values(
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

        $numeroExistentes =
            Utilizador::query()
                ->whereKey(
                    $identificadoresUtilizadores,
                )
                ->count();

        if (
            $numeroExistentes
            !== count(
                $identificadoresUtilizadores,
            )
        ) {
            throw new InvalidArgumentException(
                'Foi indicado um autor ou próximo nomeado inexistente.',
            );
        }
    }

    /**
     * Confirma a existência das bandas utilizadas.
     *
     * @param  list<array<string, mixed>>  $seccoes  Secções normalizadas.
     *
     * @throws InvalidArgumentException Quando alguma banda não existe.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function garantirBandasExistentes(
        array $seccoes,
    ): void {
        $identificadores =
            array_values(
                array_unique(
                    array_filter(
                        array_map(
                            static fn (
                                array $seccao,
                            ): mixed => $seccao['banda_id'],
                            $seccoes,
                        ),
                        static fn (
                            mixed $identificador,
                        ): bool => is_int($identificador),
                    ),
                ),
            );

        if ($identificadores === []) {
            return;
        }

        $numeroExistentes =
            Banda::query()
                ->whereKey(
                    $identificadores,
                )
                ->count();

        if (
            $numeroExistentes
            !== count(
                $identificadores,
            )
        ) {
            throw new InvalidArgumentException(
                'Foi indicada uma banda inexistente ou indisponível.',
            );
        }
    }

    /**
     * Confirma que as secções recebidas pertencem à MetalThursday.
     *
     * @param  list<array<string, mixed>>  $seccoes  Secções recebidas.
     * @param  Collection<int, SeccaoMetalThursday>  $existentes  Secções atuais.
     *
     * @throws InvalidArgumentException Quando um identificador é repetido ou
     *                                  pertence a outra MetalThursday.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function garantirIdentificadoresSecoesValidos(
        array $seccoes,
        Collection $existentes,
    ): void {
        $identificadoresRecebidos = [];

        foreach ($seccoes as $seccao) {
            $identificador =
                $seccao['id'];

            if ($identificador === null) {
                continue;
            }

            if (
                isset(
                    $identificadoresRecebidos[$identificador],
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
     * Elimina logicamente as secções que deixaram de ser enviadas.
     *
     * @param  int  $identificadorMetalThursday  Identificador da MetalThursday.
     * @param  list<array<string, mixed>>  $seccoes  Secções recebidas.
     * @param  Collection<int, SeccaoMetalThursday>  $existentes  Secções atuais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function eliminarSecoesAusentes(
        int $identificadorMetalThursday,
        array $seccoes,
        Collection $existentes,
    ): void {
        $identificadoresRecebidos =
            array_values(
                array_filter(
                    array_map(
                        static fn (
                            array $seccao,
                        ): mixed => $seccao['id'],
                        $seccoes,
                    ),
                    static fn (
                        mixed $identificador,
                    ): bool => is_int($identificador),
                ),
            );

        $identificadoresRemover =
            $existentes
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
            ->where(
                'metal_thursday_id',
                $identificadorMetalThursday,
            )
            ->whereKey(
                $identificadoresRemover,
            )
            ->delete();
    }

    /**
     * Obtém o identificador inteiro de um modelo persistido.
     *
     * @param  MetalThursday  $modelo  Modelo recebido.
     * @param  string  $designacao  Designação utilizada na mensagem.
     * @return int Identificador do modelo.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorPersistido(
        MetalThursday $modelo,
        string $designacao,
    ): int {
        $identificador =
            $modelo->getKey();

        if (
            ! $modelo->exists
            || ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'A %s deve estar persistida.',
                    $designacao,
                ),
            );
        }

        return (int) $identificador;
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
     *
     * @version 1.0.0
     */
    private function normalizarIdentificadorObrigatorio(
        mixed $valor,
        string $campo,
    ): int {
        $identificador =
            $this->normalizarIdentificadorOpcional(
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
     *
     * @version 1.1.0
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
            $valorNormalizado =
                trim(
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
     *
     * @version 1.0.0
     */
    private function normalizarData(
        mixed $valor,
    ): string {
        if (
            ! is_string($valor)
            || preg_match(
                '/^\d{4}-\d{2}-\d{2}$/D',
                $valor,
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
                $valor,
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
     * Normaliza um texto de uma única linha.
     *
     * @param  mixed  $valor  Valor recebido.
     * @param  string  $campo  Nome do campo.
     * @param  int  $comprimentoMaximo  Comprimento máximo.
     * @return string|null Texto normalizado ou nulo.
     *
     * @throws InvalidArgumentException Quando o texto não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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

        if (! is_string($valor)) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s deve ser um texto.',
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

        $texto =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $valor,
                ),
            );

        if (! is_string($texto)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Não foi possível normalizar o campo %s.',
                    $campo,
                ),
            );
        }

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
     * Normaliza a descrição de uma secção.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Descrição normalizada ou nula.
     *
     * @throws InvalidArgumentException Quando o valor não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarDescricao(
        mixed $valor,
    ): ?string {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (! is_string($valor)) {
            throw new InvalidArgumentException(
                'A descrição da secção deve ser um texto.',
            );
        }

        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $valor,
            ) === 1
        ) {
            throw new InvalidArgumentException(
                'A descrição da secção contém caracteres inválidos.',
            );
        }

        $descricao =
            trim(
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
            return null;
        }

        if (
            mb_strlen(
                $descricao,
            ) > self::COMPRIMENTO_MAXIMO_DESCRICAO
        ) {
            throw new InvalidArgumentException(
                'A descrição da secção é demasiado longa.',
            );
        }

        return $descricao;
    }

    /**
     * Normaliza e valida uma ligação.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Ligação válida ou nula.
     *
     * @throws InvalidArgumentException Quando a ligação não é válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarLigacao(
        mixed $valor,
    ): ?string {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (! is_string($valor)) {
            throw new InvalidArgumentException(
                'A ligação da secção deve ser um texto.',
            );
        }

        $ligacao =
            trim(
                $valor,
            );

        if ($ligacao === '') {
            return null;
        }

        if (
            mb_strlen(
                $ligacao,
            ) > self::COMPRIMENTO_MAXIMO_LIGACAO
            || preg_match(
                '/[\x00-\x1F\x7F]/',
                $ligacao,
            ) === 1
            || filter_var(
                $ligacao,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            throw new InvalidArgumentException(
                'A ligação da secção não é válida.',
            );
        }

        $componentes =
            parse_url(
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
                $componentes['pass'],
            )
        ) {
            throw new InvalidArgumentException(
                'A ligação da secção não é válida.',
            );
        }

        $esquema =
            mb_strtolower(
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
                'A ligação deve utilizar HTTP ou HTTPS.',
            );
        }

        return $ligacao;
    }

    /**
     * Normaliza o tipo de incorporação recebido.
     *
     * A existência na enum é validada posteriormente em conjunto com a
     * ligação.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Valor normalizado.
     *
     * @throws InvalidArgumentException Quando o valor não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarTipoIncorporacaoRecebido(
        mixed $valor,
    ): ?string {
        if ($valor instanceof TipoIncorporacao) {
            return $valor->value;
        }

        return $this->normalizarTextoLinhaOpcional(
            $valor,
            'seccoes.tipo_incorporacao',
            self::COMPRIMENTO_MAXIMO_TIPO_INCORPORACAO,
        );
    }

    /**
     * Normaliza o ano de uma secção.
     *
     * A coluna SQL YEAR aceita valores entre 1901 e 2155.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return int|null Ano normalizado ou nulo.
     *
     * @throws InvalidArgumentException Quando o ano não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function normalizarAno(
        mixed $valor,
    ): ?int {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (
            is_int($valor)
            && $valor >= self::ANO_MINIMO
            && $valor <= self::ANO_MAXIMO
        ) {
            return $valor;
        }

        if (is_string($valor)) {
            $valorNormalizado =
                trim(
                    $valor,
                );

            if (
                ctype_digit(
                    $valorNormalizado,
                )
            ) {
                $ano =
                    (int) $valorNormalizado;

                if (
                    $ano >= self::ANO_MINIMO
                    && $ano <= self::ANO_MAXIMO
                ) {
                    return $ano;
                }
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'O ano deve estar compreendido entre %d e %d.',
                self::ANO_MINIMO,
                self::ANO_MAXIMO,
            ),
        );
    }
}
