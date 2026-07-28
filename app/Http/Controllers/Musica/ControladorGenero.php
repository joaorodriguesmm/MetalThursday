<?php

declare(strict_types=1);

namespace App\Http\Controllers\Musica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Musica\AtualizarGeneroRequest;
use App\Http\Requests\Musica\CriarGeneroRequest;
use App\Models\Musica\Banda;
use App\Models\Musica\Genero;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gere a consulta, criação, atualização e eliminação de géneros musicais.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
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
     *
     * @version 1.0.0
     */
    private const ITENS_POR_PAGINA = 20;

    /**
     * Comprimento máximo do termo de pesquisa.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const LIMITE_PESQUISA = 100;

    /**
     * Apresenta a lista paginada de géneros.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return View Listagem de géneros.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function index(
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
                'generosPais' => static fn(
                    Builder $consulta,
                ): Builder => $consulta
                    ->select([
                        'generos.id',
                        'generos.nome',
                    ])
                    ->orderBy(
                        'generos.nome',
                    )
                    ->orderBy(
                        'generos.id',
                    ),
            ])
            ->when(
                $pesquisa !== null,
                static fn(
                    Builder $consulta,
                ): Builder => $consulta->where(
                    'nome',
                    'like',
                    '%' . $pesquisa . '%',
                ),
            )
            ->orderBy(
                'nome',
            )
            ->orderBy(
                'id',
            )
            ->paginate(
                self::ITENS_POR_PAGINA,
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
     * Apresenta o formulário de criação.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @return View Formulário de criação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function create(
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
     * @param  CriarGeneroRequest  $pedido  Pedido validado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function store(
        CriarGeneroRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            Genero::class,
        );

        $dados =
            $pedido->validated();

        $genero =
            DB::transaction(
                static function () use (
                    $dados,
                ): Genero {
                    $genero =
                        Genero::query()
                        ->create([
                            'nome' => $dados['nome'],
                        ]);

                    $genero
                        ->generosPais()
                        ->sync(
                            $dados['generos_pai']
                                ?? [],
                        );

                    return $genero;
                },
            );

        $genero->load([
            'generosPais',
        ]);

        if ($pedido->expectsJson()) {
            return response()->json(
                [
                    'mensagem' => 'Género criado com sucesso.',

                    'genero' => $genero,
                ],
                Response::HTTP_CREATED,
            );
        }

        return redirect()
            ->route(
                'generos.indice',
            )
            ->with(
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
     *
     * @version 3.0.0
     */
    public function show(
        Genero $genero,
    ): View {
        $this->authorize(
            'view',
            $genero,
        );

        $genero->loadMissing([
            'generosPais',
            'generosFilhos',
        ]);

        $bandas =
            $genero
            ->bandas()
            ->select([
                'bandas.id',
                'bandas.nome',
                'bandas.pais_id',
            ])
            ->with([
                'pais' => static fn(
                    Builder $consulta,
                ): Builder => $consulta->select([
                    'id',
                    'nome',
                    'codigo_iso',
                ]),

                'generos' => static fn(
                    Builder $consulta,
                ): Builder => $consulta
                    ->select([
                        'generos.id',
                        'generos.nome',
                    ])
                    ->orderBy(
                        'generos.nome',
                    )
                    ->orderBy(
                        'generos.id',
                    ),
            ])
            ->orderBy(
                'bandas.nome',
            )
            ->orderBy(
                'bandas.id',
            )
            ->paginate(
                self::ITENS_POR_PAGINA,
            )
            ->withQueryString();

        $bandas->setCollection(
            $bandas
                ->getCollection()
                ->map(
                    fn(Banda $banda): array =>
                    $this->prepararBandaAssociada(
                        $banda,
                        $genero,
                    ),
                ),
        );

        return view(
            'musica.generos.detalhes',
            [
                'genero' =>
                $genero,

                'bandas' =>
                $bandas,

                ...$this->obterDadosCabecalhoGenero(
                    $genero,
                ),
            ],
        );
    }

    /**
     * Apresenta o formulário de edição.
     *
     * O próprio género e todos os seus descendentes são excluídos dos
     * possíveis géneros pais.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Genero  $genero  Género editado.
     * @return View Formulário de edição.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function edit(
        Request $pedido,
        Genero $genero,
    ): View {
        $this->authorize(
            'update',
            $genero,
        );

        $genero->loadMissing(
            'generosPais',
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
     * @param  AtualizarGeneroRequest  $pedido  Pedido validado.
     * @param  Genero  $genero  Género atualizado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function update(
        AtualizarGeneroRequest $pedido,
        Genero $genero,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $genero,
        );

        $dados =
            $pedido->validated();

        DB::transaction(
            static function () use (
                $dados,
                $genero,
            ): void {
                $genero->updateOrFail([
                    'nome' => $dados['nome'],
                ]);

                $genero
                    ->generosPais()
                    ->sync(
                        $dados['generos_pai']
                            ?? [],
                    );
            },
        );

        $genero
            ->refresh()
            ->load([
                'generosPais',
                'generosFilhos',
            ]);

        if ($pedido->expectsJson()) {
            return response()->json([
                'mensagem' => 'Género atualizado com sucesso.',

                'genero' => $genero,
            ]);
        }

        return redirect()
            ->route(
                'generos.indice',
            )
            ->with(
                'sucesso',
                'Género atualizado com sucesso.',
            );
    }

    /**
     * Elimina um género.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Genero  $genero  Género eliminado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function destroy(
        Request $pedido,
        Genero $genero,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'delete',
            $genero,
        );

        $genero->deleteOrFail();

        if ($pedido->expectsJson()) {
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT,
            );
        }

        return redirect()
            ->route(
                'generos.indice',
            )
            ->with(
                'sucesso',
                'Género eliminado com sucesso.',
            );
    }

    /**
     * Obtém os dados utilizados pelo formulário de géneros.
     *
     * Quando existe uma submissão anterior inválida, os respetivos valores
     * são recuperados da sessão. Durante a edição, são utilizados como
     * predefinição os dados do género.
     *
     * O próprio género e os seus descendentes são excluídos dos géneros
     * disponíveis como pais.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Genero|null  $genero  Género editado ou nulo durante a criação.
     * @return array{
     *     genero: Genero|null,
     *     generosDisponiveis: Collection<int, Genero>,
     *     emEdicao: bool,
     *     enderecoFormulario: string,
     *     nomeGenero: string,
     *     identificadoresGenerosPaisSelecionados: array<int, string>,
     *     textoBotaoSubmissao: string
     * } Dados preparados.
     *
     * @since 2.0.0
     *
     * @version 3.0.0
     */
    private function obterDadosFormulario(
        Request $pedido,
        ?Genero $genero = null,
    ): array {
        $emEdicao =
            $genero instanceof Genero;

        if ($emEdicao) {
            $genero->loadMissing(
                'generosPais',
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
            $identificadoresGenerosPaisModelo = [];
            $identificadoresExcluidos = [];

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
     * Obtém os géneros disponíveis para utilização como géneros pais.
     *
     * @param  array<int, int|string>  $identificadoresExcluidos
     *                                                            Identificadores que não podem ser selecionados.
     * @return Collection<int, Genero> Géneros disponíveis.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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
                static fn(
                    Builder $consulta,
                ): Builder => $consulta->whereNotIn(
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
     * Normaliza o termo de pesquisa.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Termo normalizado ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
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
            self::LIMITE_PESQUISA,
        );
    }

    /**
     * Normaliza um texto utilizado num formulário.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string Texto normalizado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
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
     * @since 3.0.0
     *
     * @version 1.0.0
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
     * Identificadores inválidos são ignorados e valores repetidos são
     * removidos.
     *
     * @param  mixed  $valores  Valores recebidos.
     * @return array<int, string> Identificadores normalizados.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
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

    /**
     * Prepara os dados do cabeçalho da página de detalhes.
     *
     * @param  Genero  $genero  Género apresentado.
     * @return array{
     *     nomesGenerosPais: string|null,
     *     nomesGenerosFilhos: string|null
     * } Dados preparados.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterDadosCabecalhoGenero(
        Genero $genero,
    ): array {
        return [
            'nomesGenerosPais' =>
            $this->juntarNomesGeneros(
                $genero->generosPais,
            ),

            'nomesGenerosFilhos' =>
            $this->juntarNomesGeneros(
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
     *     nomePais: string,
     *     nomesOutrosGeneros: string|null
     * } Dados preparados.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function prepararBandaAssociada(
        Banda $banda,
        Genero $generoAtual,
    ): array {
        $identificadorGeneroAtual =
            (int) $generoAtual->getKey();

        $outrosGeneros =
            $banda
            ->generos
            ->filter(
                static fn(Genero $genero): bool =>
                (int) $genero->getKey()
                    !== $identificadorGeneroAtual,
            )
            ->values();

        return [
            'modelo' =>
            $banda,

            'identificador' =>
            (int) $banda->getKey(),

            'nome' =>
            $this->normalizarTextoApresentacao(
                $banda->nome,
            )
                ?? 'Banda indisponível',

            'nomePais' =>
            $this->normalizarTextoApresentacao(
                $banda->pais?->nome,
            )
                ?? 'País indisponível',

            'nomesOutrosGeneros' =>
            $this->juntarNomesGeneros(
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
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function juntarNomesGeneros(
        Collection $generos,
    ): ?string {
        $nomes = [];

        foreach ($generos as $genero) {
            if (! $genero instanceof Genero) {
                continue;
            }

            $nome =
                $this->normalizarTextoApresentacao(
                    $genero->nome,
                );

            if ($nome !== null) {
                $nomes[] =
                    $nome;
            }
        }

        return $nomes !== []
            ? implode(
                ', ',
                $nomes,
            )
            : null;
    }

    /**
     * Normaliza um texto destinado à apresentação.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Texto normalizado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarTextoApresentacao(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $texto =
            trim(
                $valor,
            );

        return $texto !== ''
            ? $texto
            : null;
    }
}
