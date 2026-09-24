<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Enumeracoes\TipoLancamento;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\FaixaLancamento;
use App\Models\Musica\Lancamento;
use App\Models\Musica\Musica;
use InvalidArgumentException;

/**
 * Normaliza e sincroniza os dados editáveis de um lançamento numa secção.
 *
 * A sincronização altera apenas o lançamento selecionado e as suas ocorrências
 * de tracklist. As músicas que deixam de constar da tracklist não são
 * eliminadas do catálogo e nenhuma associação a artistas é modificada.
 *
 * @since 2.0.0
 */
final class ServicoPersistenciaLancamentoSecao
{
    /** Número máximo de faixas aceite numa sincronização. */
    private const NUMERO_MAXIMO_FAIXAS = 250;

    /** Comprimento máximo da posição textual devolvida pelo Discogs. */
    private const COMPRIMENTO_MAXIMO_POSICAO = 100;

    /** Primeiro ano aceite pelo domínio musical atual. */
    private const ANO_MINIMO = 1900;

    /** Último ano aceite pelo domínio musical atual. */
    private const ANO_MAXIMO = 2155;

    /**
     * Normaliza os dados estruturados recebidos para um lançamento.
     *
     * @param  mixed  $dados  Dados recebidos.
     * @param  string  $campo  Caminho do campo para mensagens de erro.
     * @return array<string, mixed>|null Dados normalizados ou nulo.
     *
     * @since 2.0.0
     */
    public function normalizarDados(
        mixed $dados,
        string $campo,
    ): ?array {
        if ($dados === null) {
            return null;
        }

        if (! is_array($dados)) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s deve conter os dados do lançamento.',
                    $campo,
                ),
            );
        }

        $faixasRecebidas =
            $dados['faixas']
            ?? null;

        if (
            ! is_array($faixasRecebidas)
            || ! array_is_list($faixasRecebidas)
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s.faixas deve ser uma lista.',
                    $campo,
                ),
            );
        }

        if (count($faixasRecebidas) > self::NUMERO_MAXIMO_FAIXAS) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s.faixas excede o número máximo de faixas.',
                    $campo,
                ),
            );
        }

        $faixas = [];

        foreach ($faixasRecebidas as $indice => $faixa) {
            $campoFaixa =
                sprintf(
                    '%s.faixas.%d',
                    $campo,
                    $indice,
                );

            if (! is_array($faixa)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'O campo %s não contém uma faixa válida.',
                        $campoFaixa,
                    ),
                );
            }

            $faixas[] = [
                'id' => $this->normalizarIdentificadorOpcional(
                    $faixa['id']
                        ?? null,
                    $campoFaixa.'.id',
                ),
                'musica_id' => $this->normalizarIdentificadorOpcional(
                    $faixa['musica_id']
                        ?? null,
                    $campoFaixa.'.musica_id',
                ),
                'titulo' => $this->normalizarTextoObrigatorio(
                    $faixa['titulo']
                        ?? null,
                    $campoFaixa.'.titulo',
                    Musica::COMPRIMENTO_MAXIMO_TITULO,
                ),
                'posicao' => $this->normalizarTextoOpcional(
                    $faixa['posicao']
                        ?? null,
                    $campoFaixa.'.posicao',
                    self::COMPRIMENTO_MAXIMO_POSICAO,
                ),
            ];
        }

        return [
            'titulo' => $this->normalizarTextoObrigatorio(
                $dados['titulo']
                    ?? null,
                $campo.'.titulo',
                Lancamento::COMPRIMENTO_MAXIMO_TITULO,
            ),
            'tipo' => $this->normalizarTipo(
                $dados['tipo']
                    ?? null,
                $campo.'.tipo',
            ),
            'ano_original' => $this->normalizarAnoOpcional(
                $dados['ano_original']
                    ?? null,
                $campo.'.ano_original',
            ),
            'faixas' => $faixas,
        ];
    }

    /**
     * Cria ou sincroniza o lançamento de uma secção canónica de lançamento.
     *
     * @param  array<string, mixed>  $dadosSecao  Dados normalizados da secção.
     * @param  TipoSeccao  $tipoSeccao  Tipo associado.
     * @return Lancamento Lançamento sincronizado.
     *
     * @since 2.0.0
     */
    public function sincronizar(
        array $dadosSecao,
        TipoSeccao $tipoSeccao,
    ): Lancamento {
        if ($tipoSeccao->identificador !== 'lancamento') {
            throw new InvalidArgumentException(
                'Só uma secção de lançamento pode alterar dados de um lançamento.',
            );
        }

        $identificadorLancamento =
            $dadosSecao['lancamento_id']
            ?? null;

        $dadosLancamento =
            $dadosSecao['lancamento']
            ?? null;

        if (! is_array($dadosLancamento)) {
            throw new InvalidArgumentException(
                'Uma secção de lançamento exige os respetivos dados editáveis.',
            );
        }

        if ($identificadorLancamento === null) {
            $lancamento = new Lancamento;
        } else {
            if (
                ! is_int($identificadorLancamento)
                || $identificadorLancamento < 1
            ) {
                throw new InvalidArgumentException(
                    'Foi indicado um lançamento inválido.',
                );
            }

            $lancamento = Lancamento::withTrashed()
                ->whereKey(
                    $identificadorLancamento,
                )
                ->lockForUpdate()
                ->first();

            if (! $lancamento instanceof Lancamento) {
                throw new InvalidArgumentException(
                    'Foi indicado um lançamento inexistente ou indisponível.',
                );
            }
        }

        $lancamento->titulo =
            $dadosLancamento['titulo'];

        $lancamento->tipo =
            $dadosLancamento['tipo'];

        $lancamento->ano_original =
            $dadosLancamento['ano_original'];

        $lancamento->saveOrFail();

        $this->sincronizarFaixas(
            $lancamento,
            $dadosLancamento['faixas'],
        );

        return $lancamento;
    }

    /**
     * Garante que um tipo canónico sem lançamento não recebeu estado residual.
     *
     * @param  array<string, mixed>  $dadosSecao  Dados normalizados da secção.
     * @param  TipoSeccao  $tipoSeccao  Tipo associado.
     *
     * @since 2.0.0
     */
    public function garantirAusencia(
        array $dadosSecao,
        TipoSeccao $tipoSeccao,
    ): void {
        if (! in_array($tipoSeccao->identificador, ['texto', 'musica'], true)) {
            return;
        }

        if (
            ($dadosSecao['lancamento_id'] ?? null) !== null
            || ($dadosSecao['lancamento'] ?? null) !== null
        ) {
            throw new InvalidArgumentException(
                'O tipo de secção selecionado não permite associar um lançamento.',
            );
        }
    }

    /**
     * Sincroniza as ocorrências da tracklist sem eliminar músicas do catálogo.
     *
     * @param  Lancamento  $lancamento  Lançamento bloqueado.
     * @param  list<array<string, mixed>>  $faixasRecebidas  Tracklist revista.
     *
     * @since 2.0.0
     */
    private function sincronizarFaixas(
        Lancamento $lancamento,
        array $faixasRecebidas,
    ): void {
        $faixasExistentes = FaixaLancamento::query()
            ->where(
                'lancamento_id',
                $lancamento->getKey(),
            )
            ->orderBy(
                'id',
            )
            ->lockForUpdate()
            ->get()
            ->keyBy(
                static fn (
                    FaixaLancamento $faixa,
                ): int => (int) $faixa->getKey(),
            );

        $identificadoresMantidos = [];

        /*
         * Valida os identificadores antes de libertar as ordens atuais. Assim,
         * pedidos inválidos são rejeitados sem alterações intermédias quando o
         * serviço é utilizado isoladamente.
         */
        foreach ($faixasRecebidas as $dadosFaixa) {
            $identificadorFaixa =
                $dadosFaixa['id'];

            if ($identificadorFaixa === null) {
                if ($dadosFaixa['musica_id'] !== null) {
                    throw new InvalidArgumentException(
                        'Uma faixa nova não pode reutilizar uma música por um identificador enviado pelo formulário.',
                    );
                }

                continue;
            }

            if (isset($identificadoresMantidos[$identificadorFaixa])) {
                throw new InvalidArgumentException(
                    'A mesma faixa foi enviada mais do que uma vez.',
                );
            }

            $faixa =
                $faixasExistentes->get(
                    $identificadorFaixa,
                );

            if (! $faixa instanceof FaixaLancamento) {
                throw new InvalidArgumentException(
                    'Foi indicada uma faixa que não pertence ao lançamento editado.',
                );
            }

            if (
                $dadosFaixa['musica_id'] !== null
                && $dadosFaixa['musica_id'] !== (int) $faixa->musica_id
            ) {
                throw new InvalidArgumentException(
                    'A música associada a uma faixa existente não pode ser substituída por identificador.',
                );
            }

            $identificadoresMantidos[$identificadorFaixa] =
                true;
        }

        /*
         * A ordem é única por lançamento. Libertam-se primeiro as posições
         * conhecidas para evitar colisões transitórias quando duas faixas
         * trocam de posição ou uma nova ocupa a posição de outra removida.
         * O MariaDB permite vários valores NULL nesta restrição única.
         */
        FaixaLancamento::query()
            ->where(
                'lancamento_id',
                $lancamento->getKey(),
            )
            ->whereNotNull(
                'ordem',
            )
            ->update([
                'ordem' => null,
            ]);

        $faixasExistentes->each(
            static function (FaixaLancamento $faixa): void {
                $faixa->ordem = null;

                $faixa->syncOriginalAttribute(
                    'ordem',
                );
            },
        );

        foreach ($faixasRecebidas as $indice => $dadosFaixa) {
            $identificadorFaixa =
                $dadosFaixa['id'];

            if ($identificadorFaixa === null) {
                $musica = new Musica;
                $faixa = new FaixaLancamento;

                $faixa
                    ->lancamento()
                    ->associate(
                        $lancamento,
                    );
            } else {
                $faixa =
                    $faixasExistentes->get(
                        $identificadorFaixa,
                    );

                if (! $faixa instanceof FaixaLancamento) {
                    throw new InvalidArgumentException(
                        'Foi indicada uma faixa que deixou de estar disponível durante a sincronização.',
                    );
                }

                $musica = Musica::withTrashed()
                    ->whereKey(
                        $faixa->musica_id,
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $musica instanceof Musica) {
                    throw new InvalidArgumentException(
                        'A música associada a uma das faixas deixou de estar disponível.',
                    );
                }
            }

            $musica->titulo =
                $dadosFaixa['titulo'];

            $musica->saveOrFail();

            $faixa
                ->musica()
                ->associate(
                    $musica,
                );

            $faixa->posicao =
                $dadosFaixa['posicao'];

            $faixa->ordem =
                $indice + 1;

            $faixa->saveOrFail();
        }

        $identificadoresRemover = $faixasExistentes
            ->keys()
            ->reject(
                static fn (
                    mixed $identificador,
                ): bool => isset(
                    $identificadoresMantidos[(int) $identificador],
                ),
            )
            ->values()
            ->all();

        if ($identificadoresRemover !== []) {
            FaixaLancamento::query()
                ->whereKey(
                    $identificadoresRemover,
                )
                ->delete();
        }
    }

    /** Normaliza um identificador opcional positivo. */
    private function normalizarIdentificadorOpcional(
        mixed $valor,
        string $campo,
    ): ?int {
        if ($valor === null || $valor === '') {
            return null;
        }

        $normalizado = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ],
        );

        if ($normalizado === false) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s deve conter um identificador válido.',
                    $campo,
                ),
            );
        }

        return (int) $normalizado;
    }

    /** Normaliza texto obrigatório numa única linha. */
    private function normalizarTextoObrigatorio(
        mixed $valor,
        string $campo,
        int $comprimentoMaximo,
    ): string {
        $normalizado =
            $this->normalizarTextoOpcional(
                $valor,
                $campo,
                $comprimentoMaximo,
            );

        if ($normalizado === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s é obrigatório.',
                    $campo,
                ),
            );
        }

        return $normalizado;
    }

    /** Normaliza texto opcional numa única linha. */
    private function normalizarTextoOpcional(
        mixed $valor,
        string $campo,
        int $comprimentoMaximo,
    ): ?string {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (
            ! is_string($valor)
            || ! mb_check_encoding($valor, 'UTF-8')
            || preg_match('/[\x00-\x1F\x7F]/', $valor) === 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s contém texto inválido.',
                    $campo,
                ),
            );
        }

        $normalizado = preg_replace(
            '/\s+/u',
            ' ',
            trim($valor),
        );

        if (
            ! is_string($normalizado)
            || $normalizado === ''
        ) {
            return null;
        }

        if (mb_strlen($normalizado) > $comprimentoMaximo) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s excede o comprimento máximo permitido.',
                    $campo,
                ),
            );
        }

        return $normalizado;
    }

    /** Normaliza o tipo opcional do lançamento. */
    private function normalizarTipo(
        mixed $valor,
        string $campo,
    ): ?TipoLancamento {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof TipoLancamento) {
            return $valor;
        }

        if (is_string($valor)) {
            $tipo = TipoLancamento::tryFrom($valor);

            if ($tipo instanceof TipoLancamento) {
                return $tipo;
            }
        }

        throw new InvalidArgumentException(
            sprintf(
                'O campo %s contém um tipo de lançamento inválido.',
                $campo,
            ),
        );
    }

    /** Normaliza o ano original opcional. */
    private function normalizarAnoOpcional(
        mixed $valor,
        string $campo,
    ): ?int {
        if ($valor === null || $valor === '') {
            return null;
        }

        $ano = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
        );

        if (
            $ano === false
            || $ano < self::ANO_MINIMO
            || $ano > self::ANO_MAXIMO
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O campo %s contém um ano inválido.',
                    $campo,
                ),
            );
        }

        return (int) $ano;
    }
}
