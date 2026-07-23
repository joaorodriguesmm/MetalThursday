<?php

declare(strict_types=1);

namespace App\Http\Controllers\Musica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\StoreGenreRequest;
use App\Http\Requests\Entities\UpdateGenreRequest;
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
use Symfony\Component\HttpFoundation\Response;

/**
 * Gere a consulta, criação, atualização e eliminação de géneros musicais.
 *
 * Os métodos públicos mantêm os nomes definidos pelo contrato dos
 * controladores de recursos do Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
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
     * Apresenta uma lista paginada de géneros.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return View Listagem de géneros.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function index(
        Request $pedido,
    ): View {
        $this->authorize(
            'viewAny',
            Genero::class,
        );

        $pesquisa = $this->normalizarPesquisa(
            $pedido->query('search'),
        );

        $generos = Genero::query()
            ->select([
                'id',
                'nome',
            ])
            ->with([
                'pais' => static fn (
                    Builder $consulta,
                ): Builder => $consulta
                    ->select([
                        'generos.id',
                        'generos.nome',
                    ])
                    ->orderBy('generos.nome')
                    ->orderBy('generos.id'),
            ])
            ->when(
                $pesquisa !== null,
                static fn (
                    Builder $consulta,
                ): Builder => $consulta->where(
                    'nome',
                    'like',
                    '%'.$pesquisa.'%',
                ),
            )
            ->orderBy('nome')
            ->orderBy('id')
            ->paginate(
                self::ITENS_POR_PAGINA,
            )
            ->withQueryString();

        return view(
            'entities.genres.index',
            [
                'genres' => $generos,
            ],
        );
    }

    /**
     * Apresenta o formulário de criação de um género.
     *
     * @return View Formulário de criação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function create(): View
    {
        $this->authorize(
            'create',
            Genero::class,
        );

        return view(
            'entities.genres.create',
            [
                'genres' => $this->obterGenerosOrdenados(),
            ],
        );
    }

    /**
     * Guarda um novo género e os respetivos géneros pais.
     *
     * Os nomes dos campos validados permanecem temporariamente iguais aos
     * utilizados pelos formulários atuais.
     *
     * @param  StoreGenreRequest  $pedido  Pedido validado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @throws ValidationException Quando um género pai não é válido.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function store(
        StoreGenreRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            Genero::class,
        );

        $dados = $pedido->validated();

        $identificadoresPais =
            $this->normalizarIdentificadoresPais(
                $dados['parent_genres'] ?? [],
            );

        $this->garantirGenerosPaisExistentes(
            $identificadoresPais,
        );

        $genero = DB::transaction(
            static function () use (
                $dados,
                $identificadoresPais,
            ): Genero {
                $genero = Genero::query()->create([
                    'nome' => $dados['name'],
                ]);

                $genero->pais()->sync(
                    $identificadoresPais,
                );

                return $genero;
            },
        );

        $genero->load([
            'pais',
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
            ->route('genres.index')
            ->with(
                'estado',
                'Género criado com sucesso.',
            );
    }

    /**
     * Apresenta um género e as bandas associadas.
     *
     * @param  Genero  $genero  Género apresentado.
     * @return View Página do género.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function show(
        Genero $genero,
    ): View {
        $this->authorize(
            'view',
            $genero,
        );

        $genero->loadMissing([
            'pais',
            'filhos',
        ]);

        $bandas = $genero
            ->bandas()
            ->select([
                'bandas.id',
                'bandas.nome',
                'bandas.pais_id',
            ])
            ->with([
                'pais' => static fn (
                    Builder $consulta,
                ): Builder => $consulta->select([
                    'id',
                    'nome',
                    'codigo_iso',
                ]),

                'generos' => static fn (
                    Builder $consulta,
                ): Builder => $consulta
                    ->select([
                        'generos.id',
                        'generos.nome',
                    ])
                    ->orderBy('generos.nome')
                    ->orderBy('generos.id'),
            ])
            ->orderBy('bandas.nome')
            ->orderBy('bandas.id')
            ->paginate(
                self::ITENS_POR_PAGINA,
            )
            ->withQueryString();

        return view(
            'entities.genres.show',
            [
                'genre' => $genero,
                'bands' => $bandas,
            ],
        );
    }

    /**
     * Apresenta o formulário de edição de um género.
     *
     * O próprio género e os seus descendentes são excluídos da lista de
     * possíveis pais, impedindo a criação de ciclos hierárquicos.
     *
     * @param  Genero  $genero  Género editado.
     * @return View Formulário de edição.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function edit(
        Genero $genero,
    ): View {
        $this->authorize(
            'update',
            $genero,
        );

        $genero->loadMissing([
            'pais',
        ]);

        $identificadoresExcluidos =
            $genero
                ->obterIdentificadoresComDescendentes();

        $generosDisponiveis = Genero::query()
            ->select([
                'id',
                'nome',
            ])
            ->whereNotIn(
                'id',
                $identificadoresExcluidos,
            )
            ->orderBy('nome')
            ->orderBy('id')
            ->get();

        return view(
            'entities.genres.edit',
            [
                'genre' => $genero,
                'genres' => $generosDisponiveis,
            ],
        );
    }

    /**
     * Atualiza um género e os respetivos géneros pais.
     *
     * @param  UpdateGenreRequest  $pedido  Pedido validado.
     * @param  Genero  $genero  Género atualizado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @throws ValidationException Quando a hierarquia não é válida.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function update(
        UpdateGenreRequest $pedido,
        Genero $genero,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $genero,
        );

        $dados = $pedido->validated();

        $identificadoresPais =
            $this->normalizarIdentificadoresPais(
                $dados['parent_genres'] ?? [],
            );

        $this->garantirGenerosPaisExistentes(
            $identificadoresPais,
        );

        $this->garantirHierarquiaSemCiclos(
            $genero,
            $identificadoresPais,
        );

        DB::transaction(
            static function () use (
                $dados,
                $genero,
                $identificadoresPais,
            ): void {
                $genero->updateOrFail([
                    'nome' => $dados['name'],
                ]);

                $genero->pais()->sync(
                    $identificadoresPais,
                );
            },
        );

        $genero
            ->refresh()
            ->load([
                'pais',
                'filhos',
            ]);

        if ($pedido->expectsJson()) {
            return response()->json([
                'mensagem' => 'Género atualizado com sucesso.',

                'genero' => $genero,
            ]);
        }

        return redirect()
            ->route('genres.index')
            ->with(
                'estado',
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
     * @version 2.0.0
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
            ->route('genres.index')
            ->with(
                'estado',
                'Género eliminado com sucesso.',
            );
    }

    /**
     * Obtém todos os géneros ordenados alfabeticamente.
     *
     * @return Collection<int, Genero> Géneros disponíveis.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterGenerosOrdenados(): Collection
    {
        return Genero::query()
            ->select([
                'id',
                'nome',
            ])
            ->orderBy('nome')
            ->orderBy('id')
            ->get();
    }

    /**
     * Normaliza os identificadores dos géneros pais.
     *
     * @param  mixed  $valor  Valor recebido do pedido.
     * @return array<int, int> Identificadores únicos.
     *
     * @throws ValidationException Quando o valor não é uma lista de
     *                             identificadores positivos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarIdentificadoresPais(
        mixed $valor,
    ): array {
        if ($valor === null) {
            return [];
        }

        if (! is_array($valor)) {
            throw ValidationException::withMessages([
                'parent_genres' => 'Os géneros pais devem ser enviados numa lista.',
            ]);
        }

        $identificadores = [];

        foreach ($valor as $identificadorRecebido) {
            $identificador = filter_var(
                $identificadorRecebido,
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 1,
                    ],
                ],
            );

            if ($identificador === false) {
                throw ValidationException::withMessages([
                    'parent_genres' => 'Foi selecionado um género pai inválido.',
                ]);
            }

            $identificadorNormalizado =
                (int) $identificador;

            $identificadores[$identificadorNormalizado] = $identificadorNormalizado;
        }

        return array_values(
            $identificadores,
        );
    }

    /**
     * Confirma que todos os géneros pais existem.
     *
     * @param  array<int, int>  $identificadores  Identificadores recebidos.
     *
     * @throws ValidationException Quando algum género não existe.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function garantirGenerosPaisExistentes(
        array $identificadores,
    ): void {
        if ($identificadores === []) {
            return;
        }

        $numeroGenerosExistentes = Genero::query()
            ->whereKey(
                $identificadores,
            )
            ->count();

        if (
            $numeroGenerosExistentes
            === count($identificadores)
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'parent_genres' => 'Foi selecionado um género pai inexistente.',
        ]);
    }

    /**
     * Impede que um género seja colocado abaixo de si próprio ou de um dos
     * seus descendentes.
     *
     * @param  Genero  $genero  Género atualizado.
     * @param  array<int, int>  $identificadoresPais  Pais selecionados.
     *
     * @throws ValidationException Quando a alteração criaria um ciclo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function garantirHierarquiaSemCiclos(
        Genero $genero,
        array $identificadoresPais,
    ): void {
        if ($identificadoresPais === []) {
            return;
        }

        $identificadoresProibidos =
            $genero
                ->obterIdentificadoresComDescendentes();

        $intersecao = array_intersect(
            $identificadoresPais,
            $identificadoresProibidos,
        );

        if ($intersecao === []) {
            return;
        }

        throw ValidationException::withMessages([
            'parent_genres' => 'Um género não pode ter como pai o próprio género nem um dos seus descendentes.',
        ]);
    }

    /**
     * Normaliza o termo de pesquisa.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Termo normalizado ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarPesquisa(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $pesquisa = trim(
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
}
