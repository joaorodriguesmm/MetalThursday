<?php

declare(strict_types=1);

namespace App\Filtros;

use App\Enumeracoes\DirecaoOrdenacao;
use App\Enumeracoes\OrdenacaoMetalThursday;
use App\Enumeracoes\RespostaBinaria;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\Musica\Genero;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Aplica filtros e ordenações às consultas de MetalThursdays e secções.
 *
 * Suporta exclusivamente consultas dos modelos `MetalThursday` e
 * `SeccaoMetalThursday`.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class FiltrosMetalThursday
{
    /**
     * Parâmetros de filtro permitidos.
     *
     * A lista impede que parâmetros arbitrários do pedido possam invocar
     * métodos internos da classe.
     *
     * @var array<int, string>
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private const PARAMETROS_FILTROS = [
        'filtro_autor',
        'filtro_banda',
        'filtro_autoria_utilizador',
        'filtro_data_ate',
        'filtro_data_desde',
        'filtro_data',
        'filtro_edicao',
        'filtro_nomeacao',
        'filtro_genero',
        'filtro_avaliacao',
        'filtro_audicao',
    ];

    /**
     * Modelos que podem ser filtrados por esta classe.
     *
     * @var array<int, class-string<Model>>
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private const MODELOS_SUPORTADOS = [
        MetalThursday::class,
        SeccaoMetalThursday::class,
    ];

    /**
     * Alias da classificação média calculada pela consulta.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COLUNA_CLASSIFICACAO_MEDIA =
        'classificacao_media';

    /**
     * Alias da classificação atribuída pelo utilizador autenticado.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COLUNA_CLASSIFICACAO_UTILIZADOR =
        'classificacao_utilizador';

    /**
     * Alias da data da MetalThursday associada a uma secção.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COLUNA_DATA_METAL_THURSDAY =
        'data_metal_thursday';

    /**
     * Pedido HTTP que contém os parâmetros dos filtros e da ordenação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private readonly Request $pedido;

    /**
     * Construtor de consultas ao qual são aplicados os filtros.
     *
     * @var Builder<Model>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private Builder $construtorConsulta;

    /**
     * Nome completo da classe do modelo associado à consulta.
     *
     * @var class-string<Model>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private string $classeModelo;

    /**
     * Cria o serviço de filtragem.
     *
     * @param  Request  $pedido  Pedido HTTP.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function __construct(
        Request $pedido,
    ) {
        $this->pedido = $pedido;
    }

    /**
     * Aplica os filtros e a ordenação à consulta.
     *
     * @param  Builder<Model>  $construtorConsulta  Construtor da consulta.
     * @return Builder<Model> Consulta com filtros e ordenação aplicados.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function aplicar(
        Builder $construtorConsulta,
    ): Builder {
        $this->construtorConsulta =
            $construtorConsulta;

        $this->classeModelo = $construtorConsulta
            ->getModel()::class;

        $this->garantirModeloSuportado();
        $this->aplicarFiltros();
        $this->aplicarOrdenacao();

        return $this->construtorConsulta;
    }

    /**
     * Aplica os filtros explicitamente permitidos.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function aplicarFiltros(): void
    {
        foreach (
            self::PARAMETROS_FILTROS as $parametro
        ) {
            $valor = $this->pedido->query(
                $parametro,
            );

            if (! $this->valorPodeSerAplicado($valor)) {
                continue;
            }

            $this->aplicarFiltroPermitido(
                $parametro,
                $valor,
            );
        }
    }

    /**
     * Aplica um filtro previamente autorizado.
     *
     * @param  string  $parametro  Nome do parâmetro.
     * @param  mixed  $valor  Valor recebido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function aplicarFiltroPermitido(
        string $parametro,
        mixed $valor,
    ): void {
        switch ($parametro) {
            case 'filtro_autor':
                $this->filtrarPorAutor($valor);

                return;

            case 'filtro_banda':
                $this->filtrarPorBanda($valor);

                return;

            case 'filtro_autoria_utilizador':
                $this->filtrarPorAutoriaDoUtilizador(
                    $valor,
                );

                return;

            case 'filtro_data_ate':
                $this->filtrarPorDataAte($valor);

                return;

            case 'filtro_data_desde':
                $this->filtrarPorDataDesde($valor);

                return;

            case 'filtro_data':
                $this->filtrarPorData($valor);

                return;

            case 'filtro_edicao':
                $this->filtrarPorEdicao($valor);

                return;

            case 'filtro_nomeacao':
                $this->filtrarPorNomeacaoDoUtilizador(
                    $valor,
                );

                return;

            case 'filtro_genero':
                $this->filtrarPorGenero($valor);

                return;

            case 'filtro_avaliacao':
                $this->filtrarPorAvaliacaoDoUtilizador(
                    $valor,
                );

                return;

            case 'filtro_audicao':
                $this->filtrarPorAudicaoDoUtilizador(
                    $valor,
                );

                return;
        }
    }

    /**
     * Aplica a ordenação pedida à consulta.
     *
     * São utilizadas a data e a direção descendente quando os valores
     * recebidos não são reconhecidos.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function aplicarOrdenacao(): void
    {
        $ordenacao =
            OrdenacaoMetalThursday::tentarCriar(
                $this->pedido->query(
                    'ordenar_por',
                ),
            )
            ?? OrdenacaoMetalThursday::Data;

        $direcao =
            DirecaoOrdenacao::tentarCriar(
                $this->pedido->query(
                    'direcao_ordenacao',
                ),
            )
            ?? DirecaoOrdenacao::Descendente;

        match ($ordenacao) {
            OrdenacaoMetalThursday::Classificacao => $this->ordenarPorClassificacaoMedia(
                $direcao,
            ),

            OrdenacaoMetalThursday::MinhaClassificacao => $this->ordenarPorClassificacaoDoUtilizador(
                $direcao,
            ),

            OrdenacaoMetalThursday::Data => $this->ordenarPorData(
                $direcao,
            ),
        };
    }

    /**
     * Ordena a consulta pela classificação média.
     *
     * Os registos sem classificação são apresentados no fim.
     *
     * @param  DirecaoOrdenacao  $direcao  Direção pretendida.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function ordenarPorClassificacaoMedia(
        DirecaoOrdenacao $direcao,
    ): void {
        $modelo = $this
            ->construtorConsulta
            ->getModel();

        $subconsulta = DB::table(
            'avaliacoes',
        )
            ->selectRaw(
                'AVG(pontuacao)',
            )
            ->whereColumn(
                'avaliavel_id',
                $modelo->getQualifiedKeyName(),
            )
            ->where(
                'tipo_avaliavel',
                $modelo->getMorphClass(),
            );

        $this->construtorConsulta
            ->addSelect([
                self::COLUNA_CLASSIFICACAO_MEDIA => $subconsulta,
            ])
            ->orderByRaw(
                sprintf(
                    'CASE WHEN %s IS NULL THEN 1 ELSE 0 END ASC',
                    self::COLUNA_CLASSIFICACAO_MEDIA,
                ),
            )
            ->orderBy(
                self::COLUNA_CLASSIFICACAO_MEDIA,
                $direcao->paraSql(),
            );

        $this->adicionarCriterioDesempate(
            $direcao,
        );
    }

    /**
     * Ordena pela classificação atribuída pelo utilizador autenticado.
     *
     * Quando não existe utilizador autenticado, é utilizada a data.
     *
     * @param  DirecaoOrdenacao  $direcao  Direção pretendida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function ordenarPorClassificacaoDoUtilizador(
        DirecaoOrdenacao $direcao,
    ): void {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador();

        if ($identificadorUtilizador === null) {
            $this->ordenarPorData(
                $direcao,
            );

            return;
        }

        $modelo = $this
            ->construtorConsulta
            ->getModel();

        $subconsulta = DB::table(
            'avaliacoes',
        )
            ->select(
                'pontuacao',
            )
            ->whereColumn(
                'avaliavel_id',
                $modelo->getQualifiedKeyName(),
            )
            ->where(
                'tipo_avaliavel',
                $modelo->getMorphClass(),
            )
            ->where(
                'utilizador_id',
                $identificadorUtilizador,
            )
            ->limit(1);

        $this->construtorConsulta
            ->addSelect([
                self::COLUNA_CLASSIFICACAO_UTILIZADOR => $subconsulta,
            ])
            ->orderByRaw(
                sprintf(
                    'CASE WHEN %s IS NULL THEN 1 ELSE 0 END ASC',
                    self::COLUNA_CLASSIFICACAO_UTILIZADOR,
                ),
            )
            ->orderBy(
                self::COLUNA_CLASSIFICACAO_UTILIZADOR,
                $direcao->paraSql(),
            );

        $this->adicionarCriterioDesempate(
            $direcao,
        );
    }

    /**
     * Ordena a consulta pela data da MetalThursday.
     *
     * Nas consultas de secções, a data é obtida através de uma subconsulta à
     * MetalThursday relacionada.
     *
     * @param  DirecaoOrdenacao  $direcao  Direção pretendida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function ordenarPorData(
        DirecaoOrdenacao $direcao,
    ): void {
        if ($this->eConsultaDeMetalThursdays()) {
            $this->construtorConsulta->orderBy(
                $this
                    ->construtorConsulta
                    ->getModel()
                    ->qualifyColumn('data'),
                $direcao->paraSql(),
            );

            $this->adicionarCriterioDesempate(
                $direcao,
            );

            return;
        }

        $subconsulta = DB::table(
            'metal_thursdays',
        )
            ->select(
                'data',
            )
            ->whereColumn(
                'metal_thursdays.id',
                'seccoes_metal_thursday.metal_thursday_id',
            )
            ->limit(1);

        $this->construtorConsulta
            ->addSelect([
                self::COLUNA_DATA_METAL_THURSDAY => $subconsulta,
            ])
            ->orderBy(
                self::COLUNA_DATA_METAL_THURSDAY,
                $direcao->paraSql(),
            );

        $this->adicionarCriterioDesempate(
            $direcao,
        );
    }

    /**
     * Adiciona o identificador como critério de desempate.
     *
     * @param  DirecaoOrdenacao  $direcao  Direção pretendida.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function adicionarCriterioDesempate(
        DirecaoOrdenacao $direcao,
    ): void {
        $this->construtorConsulta->orderBy(
            $this
                ->construtorConsulta
                ->getModel()
                ->getQualifiedKeyName(),
            $direcao->paraSql(),
        );
    }

    /**
     * Filtra os registos por autor.
     *
     * @param  mixed  $valor  Identificador recebido.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function filtrarPorAutor(
        mixed $valor,
    ): void {
        $identificadorAutor =
            $this->converterParaIdentificador(
                $valor,
            );

        if ($identificadorAutor === null) {
            return;
        }

        $this->aplicarRestricaoNaMetalThursday(
            static fn (
                Builder $consulta,
            ): Builder => $consulta->where(
                'autor_id',
                $identificadorAutor,
            ),
        );
    }

    /**
     * Filtra os registos por banda.
     *
     * @param  mixed  $valor  Identificador recebido.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function filtrarPorBanda(
        mixed $valor,
    ): void {
        $identificadorBanda =
            $this->converterParaIdentificador(
                $valor,
            );

        if ($identificadorBanda === null) {
            return;
        }

        if ($this->eConsultaDeMetalThursdays()) {
            $this->construtorConsulta->whereHas(
                'seccoes.banda',
                static fn (
                    Builder $consulta,
                ): Builder => $consulta->whereKey(
                    $identificadorBanda,
                ),
            );

            return;
        }

        $this->construtorConsulta->where(
            'banda_id',
            $identificadorBanda,
        );
    }

    /**
     * Filtra pela autoria do utilizador autenticado.
     *
     * @param  mixed  $valor  Resposta binária recebida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function filtrarPorAutoriaDoUtilizador(
        mixed $valor,
    ): void {
        $deveCoincidir =
            $this->converterParaBooleano(
                $valor,
            );

        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador();

        if (
            $deveCoincidir === null
            || $identificadorUtilizador === null
        ) {
            return;
        }

        $this->aplicarCorrespondenciaNaMetalThursday(
            'autor_id',
            $identificadorUtilizador,
            $deveCoincidir,
        );
    }

    /**
     * Filtra por data anterior ou igual à data recebida.
     *
     * @param  mixed  $valor  Data recebida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function filtrarPorDataAte(
        mixed $valor,
    ): void {
        $data = $this->converterParaData(
            $valor,
        );

        if ($data === null) {
            return;
        }

        $this->aplicarRestricaoNaMetalThursday(
            static fn (
                Builder $consulta,
            ): Builder => $consulta->whereDate(
                'data',
                '<=',
                $data->toDateString(),
            ),
        );
    }

    /**
     * Filtra por data posterior ou igual à data recebida.
     *
     * @param  mixed  $valor  Data recebida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function filtrarPorDataDesde(
        mixed $valor,
    ): void {
        $data = $this->converterParaData(
            $valor,
        );

        if ($data === null) {
            return;
        }

        $this->aplicarRestricaoNaMetalThursday(
            static fn (
                Builder $consulta,
            ): Builder => $consulta->whereDate(
                'data',
                '>=',
                $data->toDateString(),
            ),
        );
    }

    /**
     * Filtra por uma data exata.
     *
     * @param  mixed  $valor  Data recebida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function filtrarPorData(
        mixed $valor,
    ): void {
        $data = $this->converterParaData(
            $valor,
        );

        if ($data === null) {
            return;
        }

        $this->aplicarRestricaoNaMetalThursday(
            static fn (
                Builder $consulta,
            ): Builder => $consulta->whereDate(
                'data',
                $data->toDateString(),
            ),
        );
    }

    /**
     * Filtra os registos por edição.
     *
     * @param  mixed  $valor  Identificador recebido.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function filtrarPorEdicao(
        mixed $valor,
    ): void {
        $identificadorEdicao =
            $this->converterParaIdentificador(
                $valor,
            );

        if ($identificadorEdicao === null) {
            return;
        }

        $this->aplicarRestricaoNaMetalThursday(
            static fn (
                Builder $consulta,
            ): Builder => $consulta->where(
                'edicao_id',
                $identificadorEdicao,
            ),
        );
    }

    /**
     * Filtra pela nomeação do utilizador autenticado.
     *
     * @param  mixed  $valor  Resposta binária recebida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function filtrarPorNomeacaoDoUtilizador(
        mixed $valor,
    ): void {
        $deveCoincidir =
            $this->converterParaBooleano(
                $valor,
            );

        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador();

        if (
            $deveCoincidir === null
            || $identificadorUtilizador === null
        ) {
            return;
        }

        $this->aplicarCorrespondenciaNaMetalThursday(
            'proximo_nomeado_id',
            $identificadorUtilizador,
            $deveCoincidir,
        );
    }

    /**
     * Filtra por género, incluindo os respetivos descendentes.
     *
     * @param  mixed  $valor  Identificador recebido.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function filtrarPorGenero(
        mixed $valor,
    ): void {
        $identificadorGenero =
            $this->converterParaIdentificador(
                $valor,
            );

        if ($identificadorGenero === null) {
            return;
        }

        $genero = Genero::query()->find(
            $identificadorGenero,
        );

        if ($genero === null) {
            return;
        }

        $identificadoresGeneros =
            $genero
                ->obterIdentificadoresComDescendentes();

        if ($identificadoresGeneros === []) {
            return;
        }

        if ($this->eConsultaDeMetalThursdays()) {
            $this->construtorConsulta->whereHas(
                'seccoes.banda.generos',
                static fn (
                    Builder $consulta,
                ): Builder => $consulta->whereKey(
                    $identificadoresGeneros,
                ),
            );

            return;
        }

        $this->construtorConsulta->whereHas(
            'banda.generos',
            static fn (
                Builder $consulta,
            ): Builder => $consulta->whereKey(
                $identificadoresGeneros,
            ),
        );
    }

    /**
     * Filtra pela existência de avaliação do utilizador autenticado.
     *
     * @param  mixed  $valor  Resposta binária recebida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function filtrarPorAvaliacaoDoUtilizador(
        mixed $valor,
    ): void {
        $deveExistir =
            $this->converterParaBooleano(
                $valor,
            );

        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador();

        if (
            $deveExistir === null
            || $identificadorUtilizador === null
        ) {
            return;
        }

        $restricao = static fn (
            Builder $consulta,
        ): Builder => $consulta->where(
            'utilizador_id',
            $identificadorUtilizador,
        );

        if ($deveExistir) {
            $this->construtorConsulta->whereHas(
                'avaliacoes',
                $restricao,
            );

            return;
        }

        $this->construtorConsulta->whereDoesntHave(
            'avaliacoes',
            $restricao,
        );
    }

    /**
     * Filtra pela existência de audição do utilizador autenticado.
     *
     * @param  mixed  $valor  Resposta binária recebida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    private function filtrarPorAudicaoDoUtilizador(
        mixed $valor,
    ): void {
        $deveExistir =
            $this->converterParaBooleano(
                $valor,
            );

        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador();

        if (
            $deveExistir === null
            || $identificadorUtilizador === null
        ) {
            return;
        }

        $restricao = static fn (
            Builder $consulta,
        ): Builder => $consulta->where(
            'utilizador_id',
            $identificadorUtilizador,
        );

        if ($deveExistir) {
            $this->construtorConsulta->whereHas(
                'audicoes',
                $restricao,
            );

            return;
        }

        $this->construtorConsulta->whereDoesntHave(
            'audicoes',
            $restricao,
        );
    }

    /**
     * Aplica uma restrição à MetalThursday.
     *
     * Nas consultas de secções, a restrição é aplicada através da relação
     * `metalThursday`.
     *
     * @param  Closure(Builder<Model>): Builder<Model>  $restricao  Restrição.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function aplicarRestricaoNaMetalThursday(
        Closure $restricao,
    ): void {
        if ($this->eConsultaDeMetalThursdays()) {
            $restricao(
                $this->construtorConsulta,
            );

            return;
        }

        $this->construtorConsulta->whereHas(
            'metalThursday',
            $restricao,
        );
    }

    /**
     * Aplica correspondência ou não correspondência a uma coluna.
     *
     * Quando não deve existir correspondência, os valores nulos também são
     * incluídos.
     *
     * @param  string  $coluna  Nome da coluna.
     * @param  int  $identificador  Identificador a comparar.
     * @param  bool  $deveCoincidir  Estado da correspondência.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function aplicarCorrespondenciaNaMetalThursday(
        string $coluna,
        int $identificador,
        bool $deveCoincidir,
    ): void {
        $this->aplicarRestricaoNaMetalThursday(
            static function (
                Builder $consulta,
            ) use (
                $coluna,
                $identificador,
                $deveCoincidir,
            ): Builder {
                if ($deveCoincidir) {
                    return $consulta->where(
                        $coluna,
                        $identificador,
                    );
                }

                return $consulta->where(
                    static function (
                        Builder $subconsulta,
                    ) use (
                        $coluna,
                        $identificador,
                    ): void {
                        $subconsulta
                            ->whereNull(
                                $coluna,
                            )
                            ->orWhere(
                                $coluna,
                                '!=',
                                $identificador,
                            );
                    },
                );
            },
        );
    }

    /**
     * Converte um valor num identificador inteiro positivo.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return int|null Identificador válido ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function converterParaIdentificador(
        mixed $valor,
    ): ?int {
        $identificador = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ],
        );

        return $identificador === false
            ? null
            : (int) $identificador;
    }

    /**
     * Converte uma resposta binária num valor booleano.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return bool|null Valor convertido ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function converterParaBooleano(
        mixed $valor,
    ): ?bool {
        return RespostaBinaria::tentarCriar(
            $valor,
        )?->paraBooleano();
    }

    /**
     * Converte uma data no formato AAAA-MM-DD.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return CarbonImmutable|null Data válida ou nula.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function converterParaData(
        mixed $valor,
    ): ?CarbonImmutable {
        if (! is_string($valor)) {
            return null;
        }

        $valorNormalizado = trim(
            $valor,
        );

        if ($valorNormalizado === '') {
            return null;
        }

        try {
            $data = CarbonImmutable::createFromFormat(
                '!Y-m-d',
                $valorNormalizado,
            );
        } catch (Throwable) {
            return null;
        }

        if (
            $data === false
            || $data->format('Y-m-d')
            !== $valorNormalizado
        ) {
            return null;
        }

        return $data;
    }

    /**
     * Obtém o identificador do utilizador autenticado.
     *
     * @return int|null Identificador ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterIdentificadorUtilizador(): ?int
    {
        $identificador = $this
            ->pedido
            ->user()
            ?->getAuthIdentifier();

        if (! is_numeric($identificador)) {
            return null;
        }

        $identificadorNormalizado =
            (int) $identificador;

        return $identificadorNormalizado > 0
            ? $identificadorNormalizado
            : null;
    }

    /**
     * Determina se o valor pode ser utilizado como filtro.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return bool Verdadeiro quando o valor pode ser processado.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function valorPodeSerAplicado(
        mixed $valor,
    ): bool {
        if (
            $valor === null
            || is_array($valor)
            || is_object($valor)
        ) {
            return false;
        }

        return ! is_string($valor)
            || trim($valor) !== '';
    }

    /**
     * Confirma que a consulta utiliza um modelo suportado.
     *
     * @throws InvalidArgumentException Quando o modelo não é suportado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function garantirModeloSuportado(): void
    {
        if (
            in_array(
                $this->classeModelo,
                self::MODELOS_SUPORTADOS,
                true,
            )
        ) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'O modelo %s não é suportado por %s.',
                $this->classeModelo,
                self::class,
            ),
        );
    }

    /**
     * Determina se a consulta utiliza o modelo MetalThursday.
     *
     * @return bool Estado da verificação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function eConsultaDeMetalThursdays(): bool
    {
        return $this->classeModelo
            === MetalThursday::class;
    }
}
