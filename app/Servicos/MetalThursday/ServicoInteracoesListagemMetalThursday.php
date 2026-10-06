<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Carrega os dados mínimos das interações apresentados na listagem.
 *
 * A listagem não necessita de hidratar modelos Eloquent de avaliações,
 * audições e respetivos utilizadores. Duas consultas planas em lote são
 * suficientes para preparar os contadores, médias, estados do utilizador
 * autenticado e conteúdos dos indicadores.
 *
 * @phpstan-type AvaliacaoApresentacao array{
 *     nome: string|null,
 *     pontuacao: float
 * }
 * @phpstan-type DadosInteracoesListagem array{
 *     pontuacaoUtilizador: float,
 *     ouvido: bool,
 *     quantidadeAudicoes: int,
 *     quantidadeAvaliacoes: int,
 *     mediaAvaliacoes: float,
 *     audicoes: list<string|null>,
 *     avaliacoes: list<AvaliacaoApresentacao>
 * }
 * @phpstan-type MapaInteracoesListagem array{
 *     metal-thursday: array<int, DadosInteracoesListagem>,
 *     seccao-metal-thursday: array<int, DadosInteracoesListagem>
 * }
 * @phpstan-type RegistoAvaliacao array{
 *     tipo: string,
 *     identificador: int,
 *     utilizador: int,
 *     nome: string|null,
 *     pontuacao: float
 * }
 * @phpstan-type RegistoAudicao array{
 *     tipo: string,
 *     identificador: int,
 *     utilizador: int,
 *     nome: string|null
 * }
 *
 * @since 2.0.0
 */
final class ServicoInteracoesListagemMetalThursday
{
    /**
     * Obtém os dados das interações das entidades apresentadas na página.
     *
     * @param  list<int>  $identificadoresMetalThursdays
     * @param  list<int>  $identificadoresSeccoes
     * @return MapaInteracoesListagem
     *
     * @throws LogicException Quando são encontrados dados persistidos inválidos.
     *
     * @since 2.0.0
     */
    public function obter(
        array $identificadoresMetalThursdays,
        array $identificadoresSeccoes,
        int $identificadorUtilizador,
    ): array {
        if ($identificadorUtilizador < 1) {
            throw new LogicException(
                'O utilizador da listagem deve possuir um identificador válido.',
            );
        }

        /** @var MapaInteracoesListagem $mapa */
        $mapa = [
            TipoEntidadeInteracao::MetalThursday->value => $this->criarEntidadesVazias(
                $identificadoresMetalThursdays,
            ),

            TipoEntidadeInteracao::SeccaoMetalThursday->value => $this->criarEntidadesVazias(
                $identificadoresSeccoes,
            ),
        ];

        if (
            $identificadoresMetalThursdays === []
            && $identificadoresSeccoes === []
        ) {
            return $mapa;
        }

        foreach (
            $this->obterAvaliacoes(
                $identificadoresMetalThursdays,
                $identificadoresSeccoes,
            ) as $registo
        ) {
            $avaliacao =
                $this->normalizarAvaliacao(
                    $registo,
                );

            $tipo =
                $this->obterTipoPublico(
                    $avaliacao['tipo'],
                );

            $identificador =
                $avaliacao['identificador'];

            if (! isset($mapa[$tipo][$identificador])) {
                throw new LogicException(
                    'Foi encontrada uma avaliação fora das entidades da listagem.',
                );
            }

            $mapa[$tipo][$identificador]['avaliacoes'][] = [
                'nome' => $avaliacao['nome'],

                'pontuacao' => $avaliacao['pontuacao'],
            ];

            if (
                $avaliacao['utilizador']
                === $identificadorUtilizador
            ) {
                $mapa[$tipo][$identificador]['pontuacaoUtilizador'] =
                    $avaliacao['pontuacao'];
            }
        }

        foreach (
            $this->obterAudicoes(
                $identificadoresMetalThursdays,
                $identificadoresSeccoes,
            ) as $registo
        ) {
            $audicao =
                $this->normalizarAudicao(
                    $registo,
                );

            $tipo =
                $this->obterTipoPublico(
                    $audicao['tipo'],
                );

            $identificador =
                $audicao['identificador'];

            if (! isset($mapa[$tipo][$identificador])) {
                throw new LogicException(
                    'Foi encontrada uma audição fora das entidades da listagem.',
                );
            }

            $mapa[$tipo][$identificador]['audicoes'][] =
                $audicao['nome'];

            if (
                $audicao['utilizador']
                === $identificadorUtilizador
            ) {
                $mapa[$tipo][$identificador]['ouvido'] =
                    true;
            }
        }

        foreach ($mapa as &$entidades) {
            foreach ($entidades as &$dados) {
                $dados['quantidadeAvaliacoes'] =
                    count(
                        $dados['avaliacoes'],
                    );

                $dados['quantidadeAudicoes'] =
                    count(
                        $dados['audicoes'],
                    );

                if ($dados['quantidadeAvaliacoes'] === 0) {
                    continue;
                }

                $somaPontuacoes =
                    array_sum(
                        array_map(
                            static fn (
                                array $avaliacao,
                            ): float => $avaliacao['pontuacao'],
                            $dados['avaliacoes'],
                        ),
                    );

                $dados['mediaAvaliacoes'] =
                    $somaPontuacoes
                    / $dados['quantidadeAvaliacoes'];
            }

            unset($dados);
        }

        unset($entidades);

        return $mapa;
    }

    /**
     * Cria os dados vazios para os identificadores recebidos.
     *
     * @param  list<int>  $identificadores
     * @return array<int, DadosInteracoesListagem>
     *
     * @throws LogicException Quando existe um identificador inválido.
     *
     * @since 2.0.0
     */
    private function criarEntidadesVazias(
        array $identificadores,
    ): array {
        $entidades = [];

        foreach ($identificadores as $identificador) {
            if ($identificador < 1) {
                throw new LogicException(
                    'Uma entidade da listagem possui um identificador inválido.',
                );
            }

            $entidades[$identificador] = [
                'pontuacaoUtilizador' => 0.0,

                'ouvido' => false,

                'quantidadeAudicoes' => 0,

                'quantidadeAvaliacoes' => 0,

                'mediaAvaliacoes' => 0.0,

                'audicoes' => [],

                'avaliacoes' => [],
            ];
        }

        return $entidades;
    }

    /**
     * Obtém as avaliações das entidades da página.
     *
     * @param  list<int>  $identificadoresMetalThursdays
     * @param  list<int>  $identificadoresSeccoes
     * @return iterable<int, object>
     *
     * @since 2.0.0
     */
    private function obterAvaliacoes(
        array $identificadoresMetalThursdays,
        array $identificadoresSeccoes,
    ): iterable {
        $construtor =
            DB::table(
                'avaliacoes as interacao',
            )
                ->leftJoin(
                    'utilizadores as utilizador',
                    'utilizador.id',
                    '=',
                    'interacao.utilizador_id',
                )
                ->select([
                    'interacao.id',
                    'interacao.utilizador_id',
                    'interacao.tipo_avaliavel',
                    'interacao.avaliavel_id',
                    'interacao.pontuacao',
                    'utilizador.nome as nome_utilizador',
                ]);

        $this->aplicarFiltroEntidades(
            $construtor,
            'interacao.tipo_avaliavel',
            'interacao.avaliavel_id',
            $identificadoresMetalThursdays,
            $identificadoresSeccoes,
        );

        return $construtor
            ->orderBy(
                'interacao.id',
            )
            ->get();
    }

    /**
     * Obtém as audições das entidades da página.
     *
     * @param  list<int>  $identificadoresMetalThursdays
     * @param  list<int>  $identificadoresSeccoes
     * @return iterable<int, object>
     *
     * @since 2.0.0
     */
    private function obterAudicoes(
        array $identificadoresMetalThursdays,
        array $identificadoresSeccoes,
    ): iterable {
        $construtor =
            DB::table(
                'audicoes as interacao',
            )
                ->leftJoin(
                    'utilizadores as utilizador',
                    'utilizador.id',
                    '=',
                    'interacao.utilizador_id',
                )
                ->select([
                    'interacao.id',
                    'interacao.utilizador_id',
                    'interacao.tipo_audivel',
                    'interacao.audivel_id',
                    'utilizador.nome as nome_utilizador',
                ]);

        $this->aplicarFiltroEntidades(
            $construtor,
            'interacao.tipo_audivel',
            'interacao.audivel_id',
            $identificadoresMetalThursdays,
            $identificadoresSeccoes,
        );

        return $construtor
            ->orderBy(
                'interacao.id',
            )
            ->get();
    }

    /**
     * Limita uma consulta às MetalThursdays e secções da página.
     *
     * @param  Builder  $construtor  Consulta preparada.
     * @param  string  $colunaTipo  Coluna polimórfica do tipo.
     * @param  string  $colunaIdentificador  Coluna polimórfica do identificador.
     * @param  list<int>  $identificadoresMetalThursdays
     * @param  list<int>  $identificadoresSeccoes
     *
     * @since 2.0.0
     */
    private function aplicarFiltroEntidades(
        Builder $construtor,
        string $colunaTipo,
        string $colunaIdentificador,
        array $identificadoresMetalThursdays,
        array $identificadoresSeccoes,
    ): void {
        $aliasMetalThursday =
            TipoEntidadeInteracao::MetalThursday
                ->obterAliasPolimorfico();

        $aliasSeccao =
            TipoEntidadeInteracao::SeccaoMetalThursday
                ->obterAliasPolimorfico();

        if (
            $identificadoresMetalThursdays !== []
            && $identificadoresSeccoes !== []
        ) {
            $construtor->where(
                static function (
                    Builder $grupo,
                ) use (
                    $colunaTipo,
                    $colunaIdentificador,
                    $aliasMetalThursday,
                    $aliasSeccao,
                    $identificadoresMetalThursdays,
                    $identificadoresSeccoes,
                ): void {
                    $grupo
                        ->where(
                            static function (
                                Builder $consultaMetalThursday,
                            ) use (
                                $colunaTipo,
                                $colunaIdentificador,
                                $aliasMetalThursday,
                                $identificadoresMetalThursdays,
                            ): void {
                                $consultaMetalThursday
                                    ->where(
                                        $colunaTipo,
                                        $aliasMetalThursday,
                                    )
                                    ->whereIn(
                                        $colunaIdentificador,
                                        $identificadoresMetalThursdays,
                                    );
                            },
                        )
                        ->orWhere(
                            static function (
                                Builder $consultaSeccoes,
                            ) use (
                                $colunaTipo,
                                $colunaIdentificador,
                                $aliasSeccao,
                                $identificadoresSeccoes,
                            ): void {
                                $consultaSeccoes
                                    ->where(
                                        $colunaTipo,
                                        $aliasSeccao,
                                    )
                                    ->whereIn(
                                        $colunaIdentificador,
                                        $identificadoresSeccoes,
                                    );
                            },
                        );
                },
            );

            return;
        }

        if ($identificadoresMetalThursdays !== []) {
            $construtor
                ->where(
                    $colunaTipo,
                    $aliasMetalThursday,
                )
                ->whereIn(
                    $colunaIdentificador,
                    $identificadoresMetalThursdays,
                );

            return;
        }

        if ($identificadoresSeccoes !== []) {
            $construtor
                ->where(
                    $colunaTipo,
                    $aliasSeccao,
                )
                ->whereIn(
                    $colunaIdentificador,
                    $identificadoresSeccoes,
                );

            return;
        }

        $construtor->whereRaw(
            '1 = 0',
        );
    }

    /**
     * Normaliza um registo plano de avaliação.
     *
     * @return RegistoAvaliacao
     *
     * @throws LogicException Quando os dados persistidos não são válidos.
     *
     * @since 2.0.0
     */
    private function normalizarAvaliacao(
        object $registo,
    ): array {
        $dados =
            (array) $registo;

        $tipo =
            $dados['tipo_avaliavel']
            ?? null;

        $identificador =
            $dados['avaliavel_id']
            ?? null;

        $utilizador =
            $dados['utilizador_id']
            ?? null;

        $nome =
            $dados['nome_utilizador']
            ?? null;

        $pontuacao =
            $dados['pontuacao']
            ?? null;

        if (
            ! is_string($tipo)
            || ! is_numeric($identificador)
            || (int) $identificador < 1
            || ! is_numeric($utilizador)
            || (int) $utilizador < 1
            || (
                $nome !== null
                && ! is_string($nome)
            )
            || ! is_numeric($pontuacao)
        ) {
            throw new LogicException(
                'Uma avaliação da listagem possui dados inválidos.',
            );
        }

        return [
            'tipo' => $tipo,

            'identificador' => (int) $identificador,

            'utilizador' => (int) $utilizador,

            'nome' => $nome,

            'pontuacao' => (float) $pontuacao,
        ];
    }

    /**
     * Normaliza um registo plano de audição.
     *
     * @return RegistoAudicao
     *
     * @throws LogicException Quando os dados persistidos não são válidos.
     *
     * @since 2.0.0
     */
    private function normalizarAudicao(
        object $registo,
    ): array {
        $dados =
            (array) $registo;

        $tipo =
            $dados['tipo_audivel']
            ?? null;

        $identificador =
            $dados['audivel_id']
            ?? null;

        $utilizador =
            $dados['utilizador_id']
            ?? null;

        $nome =
            $dados['nome_utilizador']
            ?? null;

        if (
            ! is_string($tipo)
            || ! is_numeric($identificador)
            || (int) $identificador < 1
            || ! is_numeric($utilizador)
            || (int) $utilizador < 1
            || (
                $nome !== null
                && ! is_string($nome)
            )
        ) {
            throw new LogicException(
                'Uma audição da listagem possui dados inválidos.',
            );
        }

        return [
            'tipo' => $tipo,

            'identificador' => (int) $identificador,

            'utilizador' => (int) $utilizador,

            'nome' => $nome,
        ];
    }

    /**
     * Converte o alias persistido para o slug público da entidade.
     *
     * @throws LogicException Quando o alias não pertence às interações
     *                        suportadas.
     *
     * @since 2.0.0
     */
    private function obterTipoPublico(
        string $alias,
    ): string {
        return match ($alias) {
            TipoEntidadeInteracao::MetalThursday
                ->obterAliasPolimorfico() => TipoEntidadeInteracao::MetalThursday->value,

            TipoEntidadeInteracao::SeccaoMetalThursday
                ->obterAliasPolimorfico() => TipoEntidadeInteracao::SeccaoMetalThursday->value,

            default => throw new LogicException(
                'Foi encontrado um tipo de interação não suportado na listagem.',
            ),
        };
    }
}
