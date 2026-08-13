<?php

declare(strict_types=1);

namespace App\Http\Controllers\Musica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Musica\AtualizarGeneroRequest;
use App\Http\Requests\Musica\CriarGeneroRequest;
use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Banda;
use App\Models\Musica\Genero;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gere a consulta, criação, atualização e eliminação de géneros musicais.
 *
 * A criação e a atualização da hierarquia são executadas atomicamente. Antes
 * de alterar as relações, todos os géneros ativos são bloqueados por ordem de
 * identificador, impedindo que operações concorrentes introduzam ciclos.
 *
 * @since 1.0.0
 */
final class ControladorGenero extends Controller
{
    use AuthorizesRequests;

    /**
     * Número de registos apresentados por página.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const REGISTOS_POR_PAGINA =
        20;

    /**
     * Comprimento máximo do termo de pesquisa.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_MAXIMO_PESQUISA =
        100;

    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO =
        3;

    /**
     * Apresenta a lista paginada de géneros.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return View Listagem de géneros.
     *
     * @since 1.0.0
     */
    public function indice(
        Request $pedido,
    ): View {
        $this->authorize(
            'viewAny',
            Genero::class,
        );

        $pesquisa =
            $this->normalizarPesquisa(
                $pedido->query(
                    'pesquisa',
                ),
            );

        $generos =
            Genero::query()
                ->select([
                    'id',
                    'nome',
                ])
                ->with([
                    'generosPais:id,nome',
                ])
                ->when(
                    $pesquisa !== null,
                    static fn (
                        Builder $construtor,
                    ): Builder => $construtor->where(
                        'nome',
                        'like',
                        '%'.$pesquisa.'%',
                    ),
                )
                ->orderBy(
                    'nome',
                )
                ->orderBy(
                    'id',
                )
                ->paginate(
                    self::REGISTOS_POR_PAGINA,
                )
                ->withQueryString();

        return view(
            'musica.generos.indice',
            [
                'generos' => $generos,

                'pesquisaAtual' => $pesquisa,
            ],
        );
    }

    /**
     * Apresenta o formulário de criação de um género.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @return View Formulário de criação.
     *
     * @since 1.0.0
     */
    public function criar(
        Request $pedido,
    ): View {
        $this->authorize(
            'create',
            Genero::class,
        );

        return view(
            'musica.generos.criar',
            $this->obterDadosFormulario(
                $pedido,
            ),
        );
    }

    /**
     * Guarda um novo género e sincroniza os respetivos géneros pais.
     *
     * Os géneros ativos são bloqueados antes da criação, garantindo que os
     * pais validados continuam disponíveis até ao fim da transação.
     *
     * @param  CriarGeneroRequest  $pedido  Pedido validado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @throws ValidationException Quando um género pai deixou de estar
     *                             disponível após a validação do pedido.
     *
     * @since 1.0.0
     */
    public function guardar(
        CriarGeneroRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            Genero::class,
        );

        /**
         * @var array{
         *     nome: string,
         *     generos_pai: list<int>
         * } $dados
         */
        $dados =
            $pedido->validated();

        $genero =
            DB::transaction(
                function () use (
                    $dados,
                ): Genero {
                    $identificadoresAtivos =
                        $this->bloquearHierarquiaAtiva();

                    $this->garantirGenerosPaisDisponiveis(
                        $dados['generos_pai'],
                        $identificadoresAtivos,
                    );

                    $genero =
                        Genero::query()
                            ->create([
                                'nome' => $dados['nome'],
                            ]);

                    $genero
                        ->generosPais()
                        ->sync(
                            $dados['generos_pai'],
                        );

                    return $genero;
                },
                self::TENTATIVAS_TRANSACAO,
            );

        if ($pedido->expectsJson()) {
            $genero
                ->load(
                    'generosPais:id,nome',
                )
                ->setRelation(
                    'generosFilhos',
                    new Collection,
                );

            return response()->json(
                [
                    'mensagem' => 'Género criado com sucesso.',

                    'genero' => $this->serializarGenero(
                        $genero,
                    ),
                ],
                Response::HTTP_CREATED,
            );
        }

        return to_route(
            'generos.indice',
        )->with(
            'sucesso',
            'Género criado com sucesso.',
        );
    }

    /**
     * Apresenta os detalhes de um género.
     *
     * @param  Genero  $genero  Género apresentado.
     * @return View Página do género.
     *
     * @since 1.0.0
     */
    public function detalhes(
        Genero $genero,
    ): View {
        $this->authorize(
            'view',
            $genero,
        );

        $genero->loadMissing([
            'generosPais:id,nome',
            'generosFilhos:id,nome',
        ]);

        $bandas =
            $genero
                ->bandas()
                ->select([
                    'bandas.id',
                    'bandas.nome',
                    'bandas.origem_geografica_id',
                ])
                ->with([
                    'origemGeografica:id,nome',
                    'generos:id,nome',
                ])
                ->orderBy(
                    'bandas.nome',
                )
                ->orderBy(
                    'bandas.id',
                )
                ->paginate(
                    self::REGISTOS_POR_PAGINA,
                )
                ->withQueryString();

        $bandas->setCollection(
            $bandas
                ->getCollection()
                ->map(
                    fn (
                        Banda $banda,
                    ): array => $this->prepararBandaAssociada(
                        $banda,
                        $genero,
                    ),
                ),
        );

        return view(
            'musica.generos.detalhes',
            [
                'genero' => $genero,

                'bandas' => $bandas,

                ...$this->obterDadosCabecalhoGenero(
                    $genero,
                ),
            ],
        );
    }

    /**
     * Apresenta o formulário de edição de um género.
     *
     * O próprio género e todos os seus descendentes são excluídos dos
     * possíveis géneros pais.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Genero  $genero  Género editado.
     * @return View Formulário de edição.
     *
     * @since 1.0.0
     */
    public function editar(
        Request $pedido,
        Genero $genero,
    ): View {
        $this->authorize(
            'update',
            $genero,
        );

        $genero->loadMissing(
            'generosPais:id,nome',
        );

        return view(
            'musica.generos.editar',
            $this->obterDadosFormulario(
                $pedido,
                $genero,
            ),
        );
    }

    /**
     * Atualiza um género e sincroniza os respetivos géneros pais.
     *
     * Toda a hierarquia ativa é bloqueada por ordem de identificador. Depois
     * do bloqueio, os descendentes são novamente calculados, impedindo que
     * alterações concorrentes contornem a validação do pedido e criem ciclos.
     *
     * O género só é persistido quando o nome ou a relação de géneros pais
     * sofre uma alteração efetiva. Desta forma, os dados de auditoria não são
     * atualizados por uma submissão idêntica, mas continuam a refletir uma
     * alteração exclusivamente hierárquica.
     *
     * @param  AtualizarGeneroRequest  $pedido  Pedido validado.
     * @param  Genero  $genero  Género atualizado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @throws ValidationException Quando um género pai deixou de estar
     *                             disponível ou passou a ser proibido.
     *
     * @since 1.0.0
     */
    public function atualizar(
        AtualizarGeneroRequest $pedido,
        Genero $genero,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $genero,
        );

        /**
         * @var array{
         *     nome: string,
         *     generos_pai: list<int>
         * } $dados
         */
        $dados =
            $pedido->validated();

        $generoAtualizado =
            DB::transaction(
                function () use (
                    $genero,
                    $dados,
                ): Genero {
                    $identificadoresAtivos =
                        $this->bloquearHierarquiaAtiva();

                    $generoBloqueado =
                        Genero::query()
                            ->whereKey(
                                $genero->getKey(),
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $this->garantirGenerosPaisDisponiveis(
                        $dados['generos_pai'],
                        $identificadoresAtivos,
                    );

                    $identificadoresProibidos =
                        $generoBloqueado
                            ->obterIdentificadoresComDescendentes();

                    $this->garantirHierarquiaSemCiclos(
                        $dados['generos_pai'],
                        $identificadoresProibidos,
                    );

                    $generoBloqueado->nome =
                        $dados['nome'];

                    $alteracoesGenerosPais =
                        $generoBloqueado
                            ->generosPais()
                            ->sync(
                                $dados['generos_pai'],
                            );

                    if (
                        $generoBloqueado->isDirty()
                        || $alteracoesGenerosPais['attached'] !== []
                        || $alteracoesGenerosPais['detached'] !== []
                        || $alteracoesGenerosPais['updated'] !== []
                    ) {
                        $generoBloqueado->saveOrFail();
                    }

                    return $generoBloqueado;
                },
                self::TENTATIVAS_TRANSACAO,
            );

        if ($pedido->expectsJson()) {
            $generoAtualizado->load([
                'generosPais:id,nome',
                'generosFilhos:id,nome',
            ]);

            return response()->json([
                'mensagem' => 'Género atualizado com sucesso.',

                'genero' => $this->serializarGenero(
                    $generoAtualizado,
                ),
            ]);
        }

        return to_route(
            'generos.indice',
        )->with(
            'sucesso',
            'Género atualizado com sucesso.',
        );
    }

    /**
     * Elimina logicamente um género.
     *
     * A hierarquia ativa é bloqueada antes da eliminação para impedir que o
     * género seja associado concorrentemente como pai de outro género.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Genero  $genero  Género eliminado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     */
    public function eliminar(
        Request $pedido,
        Genero $genero,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'delete',
            $genero,
        );

        DB::transaction(
            function () use (
                $genero,
            ): void {
                $this->bloquearHierarquiaAtiva();

                $generoBloqueado =
                    Genero::query()
                        ->whereKey(
                            $genero->getKey(),
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $generoBloqueado->deleteOrFail();
            },
            self::TENTATIVAS_TRANSACAO,
        );

        if ($pedido->expectsJson()) {
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT,
            );
        }

        return to_route(
            'generos.indice',
        )->with(
            'sucesso',
            'Género eliminado com sucesso.',
        );
    }

    /**
     * Obtém os dados utilizados pelo formulário de géneros.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Genero|null  $genero  Género editado ou nulo durante a criação.
     * @return array{
     *     genero: Genero|null,
     *     generosDisponiveis: Collection<int, Genero>,
     *     emEdicao: bool,
     *     enderecoFormulario: string,
     *     nomeGenero: string,
     *     identificadoresGenerosPaisSelecionados: list<string>,
     *     textoBotaoSubmissao: string
     * } Dados preparados.
     *
     * @since 2.0.0
     */
    private function obterDadosFormulario(
        Request $pedido,
        ?Genero $genero = null,
    ): array {
        $emEdicao =
            $genero instanceof Genero;

        if ($emEdicao) {
            $genero->loadMissing(
                'generosPais:id,nome',
            );

            $identificadoresGenerosPaisModelo =
                $genero
                    ->generosPais
                    ->modelKeys();

            $identificadoresExcluidos =
                $genero
                    ->obterIdentificadoresComDescendentes();

            $enderecoFormulario =
                route(
                    'generos.atualizar',
                    $genero,
                );
        } else {
            $identificadoresGenerosPaisModelo =
                [];

            $identificadoresExcluidos =
                [];

            $enderecoFormulario =
                route(
                    'generos.guardar',
                );
        }

        return [
            'genero' => $genero,

            'generosDisponiveis' => $this->obterGenerosDisponiveis(
                $identificadoresExcluidos,
            ),

            'emEdicao' => $emEdicao,

            'enderecoFormulario' => $enderecoFormulario,

            'nomeGenero' => $this->normalizarTextoFormulario(
                $pedido->old(
                    'nome',
                    $genero?->nome,
                ),
            ),

            'identificadoresGenerosPaisSelecionados' => $this->normalizarListaIdentificadoresFormulario(
                $pedido->old(
                    'generos_pai',
                    $identificadoresGenerosPaisModelo,
                ),
            ),

            'textoBotaoSubmissao' => $emEdicao
                ? 'Guardar alterações'
                : 'Criar género',
        ];
    }

    /**
     * Bloqueia todos os géneros ativos por ordem de identificador.
     *
     * A ordem determinística reduz a probabilidade de bloqueios mútuos e
     * serializa todas as alterações da hierarquia realizadas pelo
     * controlador.
     *
     * @return array<int, true> Identificadores dos géneros ativos bloqueados.
     *
     * @since 2.0.0
     */
    private function bloquearHierarquiaAtiva(): array
    {
        return Genero::query()
            ->select([
                'id',
            ])
            ->orderBy(
                'id',
            )
            ->lockForUpdate()
            ->pluck(
                'id',
            )
            ->mapWithKeys(
                static fn (
                    mixed $identificador,
                ): array => [
                    (int) $identificador => true,
                ],
            )
            ->all();
    }

    /**
     * Confirma que todos os géneros pais continuam ativos.
     *
     * @param  list<int>  $identificadoresGenerosPais  Géneros pais pedidos.
     * @param  array<int, true>  $identificadoresAtivos  Géneros ativos
     *                                                   bloqueados.
     *
     * @throws ValidationException Quando um género pai deixou de estar
     *                             disponível.
     *
     * @since 2.0.0
     */
    private function garantirGenerosPaisDisponiveis(
        array $identificadoresGenerosPais,
        array $identificadoresAtivos,
    ): void {
        foreach (
            $identificadoresGenerosPais as $identificadorGeneroPai
        ) {
            if (
                isset(
                    $identificadoresAtivos[$identificadorGeneroPai],
                )
            ) {
                continue;
            }

            throw ValidationException::withMessages([
                'generos_pai' => 'Um dos géneros pais selecionados deixou de estar disponível.',
            ]);
        }
    }

    /**
     * Confirma que os géneros pais não incluem o próprio género nem qualquer
     * descendente.
     *
     * @param  list<int>  $identificadoresGenerosPais  Géneros pais pedidos.
     * @param  list<int>  $identificadoresProibidos  Género atual e respetivos
     *                                               descendentes.
     *
     * @throws ValidationException Quando a alteração criaria um ciclo.
     *
     * @since 2.0.0
     */
    private function garantirHierarquiaSemCiclos(
        array $identificadoresGenerosPais,
        array $identificadoresProibidos,
    ): void {
        if (
            array_intersect(
                $identificadoresGenerosPais,
                $identificadoresProibidos,
            ) === []
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'generos_pai' => 'Um género não pode ter como pai o próprio género nem um dos seus descendentes.',
        ]);
    }

    /**
     * Obtém os géneros disponíveis para utilização como géneros pais.
     *
     * @param  list<int>  $identificadoresExcluidos  Identificadores que não
     *                                               podem ser selecionados.
     * @return Collection<int, Genero> Géneros disponíveis.
     *
     * @since 2.0.0
     */
    private function obterGenerosDisponiveis(
        array $identificadoresExcluidos,
    ): Collection {
        return Genero::query()
            ->select([
                'id',
                'nome',
            ])
            ->when(
                $identificadoresExcluidos !== [],
                static fn (
                    Builder $construtor,
                ): Builder => $construtor->whereNotIn(
                    'id',
                    $identificadoresExcluidos,
                ),
            )
            ->orderBy(
                'nome',
            )
            ->orderBy(
                'id',
            )
            ->get();
    }

    /**
     * Prepara os dados do cabeçalho da página de detalhes.
     *
     * @param  Genero  $genero  Género apresentado.
     * @return array{
     *     nomesGenerosPais: string|null,
     *     nomesGenerosFilhos: string|null
     * } Dados preparados.
     *
     * @since 2.0.0
     */
    private function obterDadosCabecalhoGenero(
        Genero $genero,
    ): array {
        return [
            'nomesGenerosPais' => $this->juntarNomesGeneros(
                $genero->generosPais,
            ),

            'nomesGenerosFilhos' => $this->juntarNomesGeneros(
                $genero->generosFilhos,
            ),
        ];
    }

    /**
     * Prepara uma banda associada para apresentação.
     *
     * @param  Banda  $banda  Banda apresentada.
     * @param  Genero  $generoAtual  Género da página atual.
     * @return array{
     *     modelo: Banda,
     *     identificador: int,
     *     nome: string,
     *     nomeOrigemGeografica: string,
     *     nomesOutrosGeneros: string|null
     * } Dados preparados.
     *
     * @throws LogicException Quando a banda contém dados persistidos
     *                        inválidos.
     *
     * @since 2.0.0
     */
    private function prepararBandaAssociada(
        Banda $banda,
        Genero $generoAtual,
    ): array {
        $origemGeografica =
            $banda->origemGeografica;

        if (! $origemGeografica instanceof OrigemGeografica) {
            throw new LogicException(
                'A banda associada não possui uma origem geográfica válida.',
            );
        }

        $identificadorGeneroAtual =
            (int) $generoAtual->getKey();

        $outrosGeneros =
            $banda
                ->generos
                ->filter(
                    static fn (
                        Genero $genero,
                    ): bool => (int) $genero->getKey()
                        !== $identificadorGeneroAtual,
                )
                ->values();

        return [
            'modelo' => $banda,

            'identificador' => (int) $banda->getKey(),

            'nome' => $banda->nome,

            'nomeOrigemGeografica' => $origemGeografica->nome,

            'nomesOutrosGeneros' => $this->juntarNomesGeneros(
                $outrosGeneros,
            ),
        ];
    }

    /**
     * Junta os nomes de uma coleção de géneros.
     *
     * @param  Collection<int, Genero>  $generos  Géneros apresentados.
     * @return string|null Nomes separados por vírgulas ou nulo.
     *
     * @throws LogicException Quando a coleção contém um registo inválido.
     *
     * @since 2.0.0
     */
    private function juntarNomesGeneros(
        Collection $generos,
    ): ?string {
        $nomes = [];

        foreach ($generos as $genero) {
            if (! $genero instanceof Genero) {
                throw new LogicException(
                    'Foi encontrado um género persistido inválido.',
                );
            }

            $nome =
                trim(
                    $genero->nome,
                );

            if ($nome === '') {
                throw new LogicException(
                    'Foi encontrado um género sem um nome persistido válido.',
                );
            }

            $nomes[] =
                $nome;
        }

        return $nomes !== []
            ? implode(
                ', ',
                $nomes,
            )
            : null;
    }

    /**
     * Converte um género para o formato da resposta HTTP.
     *
     * @param  Genero  $genero  Género convertido.
     * @return array{
     *     id: int,
     *     nome: string,
     *     generos_pai: list<array{id: int, nome: string}>,
     *     generos_filhos: list<array{id: int, nome: string}>
     * } Dados do género.
     *
     * @since 2.0.0
     */
    private function serializarGenero(
        Genero $genero,
    ): array {
        return [
            'id' => (int) $genero->getKey(),

            'nome' => $genero->nome,

            'generos_pai' => $this->serializarColecaoGeneros(
                $genero->generosPais,
            ),

            'generos_filhos' => $this->serializarColecaoGeneros(
                $genero->generosFilhos,
            ),
        ];
    }

    /**
     * Converte uma coleção de géneros para uma lista da resposta HTTP.
     *
     * @param  Collection<int, Genero>  $generos  Géneros convertidos.
     * @return list<array{id: int, nome: string}> Géneros serializados.
     *
     * @throws LogicException Quando a coleção contém um registo inválido.
     *
     * @since 2.0.0
     */
    private function serializarColecaoGeneros(
        Collection $generos,
    ): array {
        $dados = [];

        foreach ($generos as $genero) {
            if (! $genero instanceof Genero) {
                throw new LogicException(
                    'Foi encontrado um género persistido inválido.',
                );
            }

            $dados[] = [
                'id' => (int) $genero->getKey(),

                'nome' => $genero->nome,
            ];
        }

        return $dados;
    }

    /**
     * Normaliza o termo de pesquisa.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Termo normalizado ou nulo.
     *
     * @since 2.0.0
     */
    private function normalizarPesquisa(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $pesquisa =
            trim(
                $valor,
            );

        if ($pesquisa === '') {
            return null;
        }

        return mb_substr(
            $pesquisa,
            0,
            self::COMPRIMENTO_MAXIMO_PESQUISA,
        );
    }

    /**
     * Normaliza um texto utilizado num formulário.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string Texto normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarTextoFormulario(
        mixed $valor,
    ): string {
        if (
            ! is_string($valor)
            && ! is_int($valor)
            && ! is_float($valor)
        ) {
            return '';
        }

        return trim(
            (string) $valor,
        );
    }

    /**
     * Normaliza um identificador utilizado num formulário.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string Identificador normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificadorFormulario(
        mixed $valor,
    ): string {
        if (is_int($valor)) {
            return $valor > 0
                ? (string) $valor
                : '';
        }

        if (! is_string($valor)) {
            return '';
        }

        $identificador =
            trim(
                $valor,
            );

        if (
            $identificador === ''
            || ! ctype_digit($identificador)
            || (int) $identificador < 1
        ) {
            return '';
        }

        return (string) (int) $identificador;
    }

    /**
     * Normaliza uma lista de identificadores do formulário.
     *
     * Identificadores inválidos são ignorados apenas durante a reconstrução
     * visual do formulário. Valores repetidos são removidos.
     *
     * @param  mixed  $valores  Valores recebidos.
     * @return list<string> Identificadores normalizados.
     *
     * @since 2.0.0
     */
    private function normalizarListaIdentificadoresFormulario(
        mixed $valores,
    ): array {
        if (! is_array($valores)) {
            return [];
        }

        $identificadores = [];

        foreach ($valores as $valor) {
            $identificador =
                $this->normalizarIdentificadorFormulario(
                    $valor,
                );

            if ($identificador === '') {
                continue;
            }

            $identificadores[$identificador] =
                $identificador;
        }

        return array_values(
            $identificadores,
        );
    }
}
