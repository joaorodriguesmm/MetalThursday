<?php

declare(strict_types=1);

namespace App\Http\Controllers\Musica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Musica\AtualizarGeneroRequest;
use App\Http\Requests\Musica\CriarGeneroRequest;
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
 * @version 2.1.0
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
     * @version 2.1.0
     */
    public function index(
        Request $pedido,
    ): View {
        $this->authorize(
            'viewAny',
            Genero::class,
        );

        $pesquisa = $this->normalizarPesquisa(
            $pedido->query('pesquisa'),
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
                'generos' => $generos,
            ],
        );
    }

    /**
     * Apresenta o formulário de criação.
     *
     * @return View Formulário de criação.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
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
                'generos' => $this->obterGenerosOrdenados(),
            ],
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
     * @version 2.1.0
     */
    public function store(
        CriarGeneroRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            Genero::class,
        );

        $dados = $pedido->validated();

        $genero = DB::transaction(
            static function () use (
                $dados,
            ): Genero {
                $genero = Genero::query()->create([
                    'nome' => $dados['nome'],
                ]);

                $genero->pais()->sync(
                    $dados['generos_pai'],
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
     * Apresenta os detalhes de um género.
     *
     * @param  Genero  $genero  Género apresentado.
     * @return View Página do género.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
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
                'genero' => $genero,

                'bandas' => $bandas,
            ],
        );
    }

    /**
     * Apresenta o formulário de edição.
     *
     * O próprio género e os seus descendentes são excluídos dos possíveis
     * géneros pais.
     *
     * @param  Genero  $genero  Género editado.
     * @return View Formulário de edição.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
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
                'genero' => $genero,

                'generos' => $generosDisponiveis,
            ],
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
     * @version 2.1.0
     */
    public function update(
        AtualizarGeneroRequest $pedido,
        Genero $genero,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $genero,
        );

        $dados = $pedido->validated();

        DB::transaction(
            static function () use (
                $dados,
                $genero,
            ): void {
                $genero->updateOrFail([
                    'nome' => $dados['nome'],
                ]);

                $genero->pais()->sync(
                    $dados['generos_pai'],
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
     * @version 2.1.0
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
