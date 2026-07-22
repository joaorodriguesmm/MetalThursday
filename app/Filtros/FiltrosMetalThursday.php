<?php

declare(strict_types=1);

namespace App\Filtros;

use App\Models\Genero;
use App\Models\MetalThursday;
use App\Models\MtSection;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Aplica filtros e ordenações às consultas de MetalThursdays e secções.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class FiltrosMetalThursday
{
    /**
     * Mapa dos parâmetros de filtro permitidos e dos métodos responsáveis
     * pela sua aplicação.
     *
     * A existência deste mapa impede que um parâmetro da URL possa executar
     * arbitrariamente outros métodos privados da classe.
     *
     * @var array<string, string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const MAPA_FILTROS = [
        'filtro_autor' => 'filtrarPorAutor',
        'filtro_banda' => 'filtrarPorBanda',
        'filtro_autoria_utilizador' => 'filtrarPorAutoriaDoUtilizador',
        'filtro_data_ate' => 'filtrarPorDataAte',
        'filtro_data_desde' => 'filtrarPorDataDesde',
        'filtro_data' => 'filtrarPorData',
        'filtro_edicao' => 'filtrarPorEdicao',
        'filtro_nomeacao' => 'filtrarPorNomeacaoDoUtilizador',
        'filtro_genero' => 'filtrarPorGenero',
        'filtro_avaliacao' => 'filtrarPorAvaliacaoDoUtilizador',
        'filtro_audicao' => 'filtrarPorAudicaoDoUtilizador',
    ];

    /**
     * Ordenações permitidas.
     *
     * @var array<int, string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ORDENACOES_PERMITIDAS = [
        'data',
        'classificacao',
        'minha_classificacao',
    ];

    /**
     * Mapa entre as direções apresentadas na URL e as direções reconhecidas
     * pelo sistema de base de dados.
     *
     * @var array<string, string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const MAPA_DIRECOES_SQL = [
        'ascendente' => 'asc',
        'descendente' => 'desc',
    ];

    /**
     * Modelos que podem ser filtrados por esta classe.
     *
     * @var array<int, class-string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const MODELOS_SUPORTADOS = [
        MetalThursday::class,
        MtSection::class,
    ];

    /**
     * Pedido HTTP que contém os parâmetros dos filtros e da ordenação.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private readonly Request $pedido;

    /**
     * Construtor de consultas Eloquent ao qual são aplicados os filtros e a
     * ordenação.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private Builder $construtorConsulta;

    /**
     * Nome completo da classe do modelo associado à consulta.
     *
     * @var class-string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private string $classeModelo;

    /**
     * Instancia a classe.
     *
     * @param  Request  $pedido  - Pedido HTTP.
     * @return void
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function __construct(Request $pedido)
    {
        $this->pedido = $pedido;
    }

    /**
     * Aplica os filtros e a ordenação à consulta.
     *
     * @param  Builder  $construtorConsulta  - Construtor de consultas Eloquent.
     * @return Builder - Construtor de consultas com os filtros e a ordenação
     *                 aplicados.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function aplicar(Builder $construtorConsulta): Builder
    {
        $this->construtorConsulta = $construtorConsulta;
        $this->classeModelo = get_class(
            $construtorConsulta->getModel(),
        );

        $this->garantirModeloSuportado();
        $this->aplicarFiltros();
        $this->aplicarOrdenacao();

        return $this->construtorConsulta;
    }

    /**
     * Aplica apenas os filtros explicitamente permitidos.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function aplicarFiltros(): void
    {
        foreach (self::MAPA_FILTROS as $parametro => $metodo) {
            $valor = $this->pedido->query($parametro);

            if (! $this->valorPodeSerAplicado($valor)) {
                continue;
            }

            $this->{$metodo}($valor);
        }
    }

    /**
     * Aplica a ordenação pedida à consulta.
     *
     * Quando a ordenação ou a direção recebida não é válida, são utilizados
     * os valores predefinidos: data e direção descendente.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function aplicarOrdenacao(): void
    {
        $ordenacao = $this->pedido->query(
            'ordenar_por',
            'data',
        );

        $direcaoRecebida = $this->pedido->query(
            'direcao_ordenacao',
            'descendente',
        );

        if (
            ! is_string($ordenacao)
            || ! in_array(
                $ordenacao,
                self::ORDENACOES_PERMITIDAS,
                true,
            )
        ) {
            $ordenacao = 'data';
        }

        if (
            ! is_string($direcaoRecebida)
            || ! array_key_exists(
                $direcaoRecebida,
                self::MAPA_DIRECOES_SQL,
            )
        ) {
            $direcaoRecebida = 'descendente';
        }

        $direcaoSql = self::MAPA_DIRECOES_SQL[$direcaoRecebida];

        match ($ordenacao) {
            'classificacao' => $this->ordenarPorClassificacaoMedia($direcaoSql),
            'minha_classificacao' => $this->ordenarPorClassificacaoDoUtilizador($direcaoSql),
            default => $this->ordenarPorData($direcaoSql),
        };
    }

    /**
     * Ordena a consulta pela classificação média.
     *
     * Os registos sem classificação são sempre apresentados no fim.
     *
     * @param  string  $direcao  - Direção SQL da ordenação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function ordenarPorClassificacaoMedia(
        string $direcao,
    ): void {
        $this->construtorConsulta
            ->orderByRaw(
                'CASE WHEN ratings_avg_rating IS NULL '
                    .'THEN 1 ELSE 0 END ASC',
            )
            ->orderBy(
                'ratings_avg_rating',
                $direcao,
            );

        $this->adicionarCriterioDesempate($direcao);
    }

    /**
     * Ordena a consulta pela classificação atribuída pelo utilizador
     * autenticado.
     *
     * Quando não existe um utilizador autenticado, a consulta é ordenada pela
     * data.
     *
     * @param  string  $direcao  - Direção SQL da ordenação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function ordenarPorClassificacaoDoUtilizador(
        string $direcao,
    ): void {
        $identificadorUtilizador = $this->obterIdentificadorUtilizador();

        if ($identificadorUtilizador === null) {
            $this->ordenarPorData($direcao);

            return;
        }

        $modelo = $this->construtorConsulta->getModel();
        $chaveQualificada = $modelo->getQualifiedKeyName();
        $tipoMorfologico = $modelo->getMorphClass();
        $nomeColuna = 'classificacao_utilizador';

        $subconsulta = DB::table('ratings')
            ->select('rating')
            ->whereColumn(
                'rateable_id',
                $chaveQualificada,
            )
            ->where(
                'rateable_type',
                $tipoMorfologico,
            )
            ->where(
                'user_id',
                $identificadorUtilizador,
            )
            ->limit(1);

        $this->construtorConsulta
            ->addSelect([
                $nomeColuna => $subconsulta,
            ])
            ->orderByRaw(
                "CASE WHEN {$nomeColuna} IS NULL "
                    .'THEN 1 ELSE 0 END ASC',
            )
            ->orderBy(
                $nomeColuna,
                $direcao,
            );

        $this->adicionarCriterioDesempate($direcao);
    }

    /**
     * Ordena a consulta pela data da MetalThursday.
     *
     * Nas consultas de secções é utilizada a coluna calculada `parent_date`,
     * definida pela consulta principal.
     *
     * @param  string  $direcao  - Direção SQL da ordenação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function ordenarPorData(string $direcao): void
    {
        $coluna = $this->eConsultaDeSecoes()
            ? 'parent_date'
            : 'date';

        $this->construtorConsulta->orderBy(
            $coluna,
            $direcao,
        );

        $this->adicionarCriterioDesempate($direcao);
    }

    /**
     * Adiciona um segundo critério de ordenação baseado no identificador do
     * modelo.
     *
     * Este critério torna a ordenação estável quando vários registos possuem
     * o mesmo valor no primeiro critério, evitando resultados repetidos ou
     * deslocados durante a paginação.
     *
     * @param  string  $direcao  - Direção SQL da ordenação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function adicionarCriterioDesempate(
        string $direcao,
    ): void {
        $this->construtorConsulta->orderBy(
            $this->construtorConsulta
                ->getModel()
                ->getQualifiedKeyName(),
            $direcao,
        );
    }

    /**
     * Filtra os registos por autor.
     *
     * @param  mixed  $valor  - Identificador do autor recebido através da URL.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function filtrarPorAutor(mixed $valor): void
    {
        $identificadorAutor = $this->converterParaIdentificador($valor);

        if ($identificadorAutor === null) {
            return;
        }

        $this->aplicarRestricaoNaMetalThursday(
            fn (Builder $consulta): Builder => $consulta->where(
                'author_id',
                $identificadorAutor,
            ),
        );
    }

    /**
     * Filtra os registos por banda.
     *
     * @param  mixed  $valor  - Identificador da banda recebido através da URL.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function filtrarPorBanda(mixed $valor): void
    {
        $identificadorBanda =
            $this->converterParaIdentificador($valor);

        if ($identificadorBanda === null) {
            return;
        }

        if ($this->eConsultaDeMetalThursdays()) {
            $this->construtorConsulta->whereHas(
                'sections.band',
                fn (Builder $consulta): Builder => $consulta->whereKey(
                    $identificadorBanda,
                ),
            );

            return;
        }

        $this->construtorConsulta->where(
            'band_id',
            $identificadorBanda,
        );
    }

    /**
     * Filtra os registos pela autoria do utilizador autenticado.
     *
     * @param  mixed  $valor  - Valor `sim` ou `nao` recebido através da URL.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function filtrarPorAutoriaDoUtilizador(
        mixed $valor,
    ): void {
        $deveCoincidir = $this->converterParaBooleanoSimNao($valor);

        $identificadorUtilizador = $this->obterIdentificadorUtilizador();

        if (
            $deveCoincidir === null
            || $identificadorUtilizador === null
        ) {
            return;
        }

        $this->aplicarCorrespondenciaNaMetalThursday(
            'author_id',
            $identificadorUtilizador,
            $deveCoincidir,
        );
    }

    /**
     * Filtra os registos cuja data seja anterior ou igual à data recebida.
     *
     * @param  mixed  $valor  - Data no formato AAAA-MM-DD.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function filtrarPorDataAte(mixed $valor): void
    {
        $data = $this->converterParaData($valor);

        if ($data === null) {
            return;
        }

        $this->aplicarRestricaoNaMetalThursday(
            fn (Builder $consulta): Builder => $consulta->where(
                'date',
                '<=',
                $data->toDateString(),
            ),
        );
    }

    /**
     * Filtra os registos cuja data seja posterior ou igual à data recebida.
     *
     * @param  mixed  $valor  - Data no formato AAAA-MM-DD.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function filtrarPorDataDesde(mixed $valor): void
    {
        $data = $this->converterParaData($valor);

        if ($data === null) {
            return;
        }

        $this->aplicarRestricaoNaMetalThursday(
            fn (Builder $consulta): Builder => $consulta->where(
                'date',
                '>=',
                $data->toDateString(),
            ),
        );
    }

    /**
     * Filtra os registos por uma data exata.
     *
     * @param  mixed  $valor  - Data no formato AAAA-MM-DD.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function filtrarPorData(mixed $valor): void
    {
        $data = $this->converterParaData($valor);

        if ($data === null) {
            return;
        }

        $this->aplicarRestricaoNaMetalThursday(
            fn (Builder $consulta): Builder => $consulta->where(
                'date',
                $data->toDateString(),
            ),
        );
    }

    /**
     * Filtra os registos por edição.
     *
     * @param  mixed  $valor  - Identificador da edição recebido através da URL.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function filtrarPorEdicao(mixed $valor): void
    {
        $identificadorEdicao =
            $this->converterParaIdentificador($valor);

        if ($identificadorEdicao === null) {
            return;
        }

        $this->aplicarRestricaoNaMetalThursday(
            fn (Builder $consulta): Builder => $consulta->where(
                'edition_id',
                $identificadorEdicao,
            ),
        );
    }

    /**
     * Filtra os registos pela nomeação do utilizador autenticado.
     *
     * @param  mixed  $valor  - Valor `sim` ou `nao` recebido através da URL.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function filtrarPorNomeacaoDoUtilizador(
        mixed $valor,
    ): void {
        $deveCoincidir =
            $this->converterParaBooleanoSimNao($valor);

        $identificadorUtilizador = $this->obterIdentificadorUtilizador();

        if (
            $deveCoincidir === null
            || $identificadorUtilizador === null
        ) {
            return;
        }

        $this->aplicarCorrespondenciaNaMetalThursday(
            'next_nominee_id',
            $identificadorUtilizador,
            $deveCoincidir,
        );
    }

    /**
     * Filtra os registos por género, incluindo todos os seus descendentes.
     *
     * @param  mixed  $valor  - Identificador do género recebido através da URL.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function filtrarPorGenero(mixed $valor): void
    {
        $identificadorGenero = $this->converterParaIdentificador($valor);

        if ($identificadorGenero === null) {
            return;
        }

        $genero = Genero::query()
            ->find($identificadorGenero);

        if ($genero === null) {
            return;
        }

        $identificadoresGeneros =
            $genero->obterIdentificadoresComDescendentes();

        if ($this->eConsultaDeMetalThursdays()) {
            $this->construtorConsulta->whereHas(
                'sections.band.genres',
                fn (Builder $consulta): Builder => $consulta->whereKey(
                    $identificadoresGeneros,
                ),
            );

            return;
        }

        $this->construtorConsulta->whereHas(
            'band.genres',
            fn (Builder $consulta): Builder => $consulta->whereKey(
                $identificadoresGeneros,
            ),
        );
    }

    /**
     * Filtra os registos pela existência de uma avaliação do utilizador
     * autenticado.
     *
     * @param  mixed  $valor  - Valor `sim` ou `nao` recebido através da URL.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function filtrarPorAvaliacaoDoUtilizador(
        mixed $valor,
    ): void {
        $deveExistir = $this->converterParaBooleanoSimNao($valor);

        $identificadorUtilizador = $this->obterIdentificadorUtilizador();

        if (
            $deveExistir === null
            || $identificadorUtilizador === null
        ) {
            return;
        }

        $restricao = fn (Builder $consulta): Builder => $consulta->where(
            'user_id',
            $identificadorUtilizador,
        );

        if ($deveExistir) {
            $this->construtorConsulta->whereHas(
                'ratings',
                $restricao,
            );

            return;
        }

        $this->construtorConsulta->whereDoesntHave(
            'ratings',
            $restricao,
        );
    }

    /**
     * Filtra os registos pela existência de uma audição do utilizador
     * autenticado.
     *
     * @param  mixed  $valor  - Valor `sim` ou `nao` recebido através da URL.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function filtrarPorAudicaoDoUtilizador(
        mixed $valor,
    ): void {
        $deveExistir =
            $this->converterParaBooleanoSimNao($valor);

        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador();

        if (
            $deveExistir === null
            || $identificadorUtilizador === null
        ) {
            return;
        }

        $restricao = fn (Builder $consulta): Builder => $consulta->where(
            'user_id',
            $identificadorUtilizador,
        );

        if ($deveExistir) {
            $this->construtorConsulta->whereHas(
                'listens',
                $restricao,
            );

            return;
        }

        $this->construtorConsulta->whereDoesntHave(
            'listens',
            $restricao,
        );
    }

    /**
     * Aplica uma restrição diretamente à MetalThursday ou através da relação
     * da secção com a respetiva MetalThursday.
     *
     * @param  Closure(Builder): Builder  $restricao  - Restrição a aplicar à
     *                                                consulta da MetalThursday.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function aplicarRestricaoNaMetalThursday(
        Closure $restricao,
    ): void {
        if ($this->eConsultaDeMetalThursdays()) {
            $restricao($this->construtorConsulta);

            return;
        }

        $this->construtorConsulta->whereHas(
            'metalThursday',
            $restricao,
        );
    }

    /**
     * Aplica uma condição de correspondência ou não correspondência a uma
     * coluna da MetalThursday.
     *
     * Quando a condição não deve coincidir, os valores nulos são também
     * incluídos.
     *
     * @param  string  $coluna  - Nome físico atual da coluna.
     * @param  int  $identificador  - Identificador a comparar.
     * @param  bool  $deveCoincidir  - Indica se os valores devem coincidir.
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
            function (Builder $consulta) use (
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
                    function (Builder $subconsulta) use (
                        $coluna,
                        $identificador,
                    ): void {
                        $subconsulta
                            ->whereNull($coluna)
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
     * @param  mixed  $valor  - Valor a converter.
     * @return int|null - Identificador convertido ou null quando o valor não
     *                  é válido.
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
            : $identificador;
    }

    /**
     * Converte os valores `sim` e `nao` num valor booleano.
     *
     * @param  mixed  $valor  - Valor a converter.
     * @return bool|null - Valor booleano ou null quando o valor não é
     *                   reconhecido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function converterParaBooleanoSimNao(
        mixed $valor,
    ): ?bool {
        return match ($valor) {
            'sim' => true,
            'nao' => false,
            default => null,
        };
    }

    /**
     * Converte uma data no formato AAAA-MM-DD numa instância imutável.
     *
     * Datas relativas, formatos alternativos e datas inexistentes são
     * rejeitados.
     *
     * @param  mixed  $valor  - Valor a converter.
     * @return CarbonImmutable|null - Data convertida ou null quando o valor
     *                              não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function converterParaData(
        mixed $valor,
    ): ?CarbonImmutable {
        if (! is_string($valor)) {
            return null;
        }

        try {
            $data = CarbonImmutable::createFromFormat(
                '!Y-m-d',
                $valor,
            );
        } catch (Throwable) {
            return null;
        }

        if (
            $data === false
            || $data->format('Y-m-d') !== $valor
        ) {
            return null;
        }

        return $data;
    }

    /**
     * Obtém o identificador do utilizador autenticado.
     *
     * @return int|null - Identificador do utilizador ou null quando não existe
     *                  um utilizador autenticado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorUtilizador(): ?int
    {
        $identificador = $this->pedido
            ->user()
            ?->getAuthIdentifier();

        if (! is_numeric($identificador)) {
            return null;
        }

        return (int) $identificador;
    }

    /**
     * Determina se o valor de um parâmetro pode ser utilizado como filtro.
     *
     * São rejeitados valores nulos, vazios e estruturas compostas.
     *
     * @param  mixed  $valor  - Valor recebido através da URL.
     * @return bool - Verdadeiro quando o valor pode ser processado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function valorPodeSerAplicado(
        mixed $valor,
    ): bool {
        return ! is_array($valor)
            && $valor !== null
            && $valor !== '';
    }

    /**
     * Confirma que a consulta está associada a um modelo suportado.
     *
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
     * Determina se a consulta está associada ao modelo MetalThursday.
     *
     * @return bool - Verdadeiro quando a consulta é de MetalThursdays.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function eConsultaDeMetalThursdays(): bool
    {
        return $this->classeModelo === MetalThursday::class;
    }

    /**
     * Determina se a consulta está associada ao modelo de secções.
     *
     * @return bool - Verdadeiro quando a consulta é de secções.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function eConsultaDeSecoes(): bool
    {
        return $this->classeModelo === MtSection::class;
    }
}
