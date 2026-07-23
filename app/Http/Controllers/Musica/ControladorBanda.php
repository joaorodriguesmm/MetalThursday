<?php

declare(strict_types=1);

namespace App\Http\Controllers\Musica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\StoreBandRequest;
use App\Http\Requests\Entities\UpdateBandRequest;
use App\Models\Geografia\Pais;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\Musica\Banda;
use App\Models\Musica\Genero;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gere a consulta, criação, atualização e eliminação de bandas.
 *
 * Os nomes dos métodos correspondem ao contrato dos controladores de recursos
 * do Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class ControladorBanda extends Controller
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
     * Apresenta a lista paginada de bandas.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return View Listagem de bandas.
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
            Banda::class,
        );

        $pesquisa = $this->normalizarPesquisa(
            $pedido->query('search'),
        );

        $bandas = Banda::query()
            ->select([
                'id',
                'nome',
                'pais_id',
            ])
            ->with([
                'pais' => static fn (
                    Builder $consulta,
                ): Builder => $consulta
                    ->select([
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
                    ->orderBy('generos.nome'),
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
            'entities.bands.index',
            [
                'bands' => $bandas,
            ],
        );
    }

    /**
     * Apresenta o formulário de criação de uma banda.
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
            Banda::class,
        );

        return view(
            'entities.bands.create',
            $this->obterDadosFormulario(),
        );
    }

    /**
     * Guarda uma nova banda.
     *
     * Os nomes dos campos validados permanecem temporariamente iguais aos
     * utilizados pelos pedidos e formulários atuais.
     *
     * @param  StoreBandRequest  $pedido  Pedido validado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function store(
        StoreBandRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            Banda::class,
        );

        $dados = $pedido->validated();

        $banda = DB::transaction(
            static function () use (
                $dados,
            ): Banda {
                $banda = Banda::query()->create([
                    'nome' => $dados['name'],

                    'pais_id' => $dados['country_id'],
                ]);

                $banda->generos()->sync(
                    $dados['genres'],
                );

                return $banda;
            },
        );

        $banda->load([
            'pais',
            'generos',
        ]);

        if ($pedido->expectsJson()) {
            return response()->json(
                [
                    'mensagem' => 'Banda criada com sucesso.',

                    'banda' => $banda,
                ],
                Response::HTTP_CREATED,
            );
        }

        return redirect()
            ->route('bands.index')
            ->with(
                'estado',
                'Banda criada com sucesso.',
            );
    }

    /**
     * Apresenta uma banda e as secções em que participa.
     *
     * As secções são ordenadas na base de dados pela data da respetiva
     * MetalThursday, evitando carregar e ordenar toda a coleção em memória.
     *
     * @param  Banda  $banda  Banda apresentada.
     * @return View Página da banda.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function show(
        Banda $banda,
    ): View {
        $this->authorize(
            'view',
            $banda,
        );

        $banda->loadMissing([
            'pais',
            'generos',
        ]);

        $modeloSeccao =
            new SeccaoMetalThursday;

        $modeloMetalThursday =
            new MetalThursday;

        $tabelaSeccoes =
            $modeloSeccao->getTable();

        $tabelaMetalThursdays =
            $modeloMetalThursday->getTable();

        $aliasOrdenacao =
            'metal_thursdays_ordenacao';

        $seccoes = SeccaoMetalThursday::query()
            ->select(
                $tabelaSeccoes.'.*',
            )
            ->join(
                $tabelaMetalThursdays
                    .' as '
                    .$aliasOrdenacao,

                $aliasOrdenacao.'.id',
                '=',
                $tabelaSeccoes
                    .'.metal_thursday_id',
            )
            ->where(
                $tabelaSeccoes.'.banda_id',
                $banda->getKey(),
            )
            ->with([
                'metalThursday.autor',
                'tipoSeccao',
            ])
            ->orderByDesc(
                $aliasOrdenacao.'.data',
            )
            ->orderByDesc(
                $tabelaSeccoes.'.id',
            )
            ->paginate(
                self::ITENS_POR_PAGINA,
            )
            ->withQueryString();

        return view(
            'entities.bands.show',
            [
                'band' => $banda,
                'sections' => $seccoes,
            ],
        );
    }

    /**
     * Apresenta o formulário de edição de uma banda.
     *
     * @param  Banda  $banda  Banda editada.
     * @return View Formulário de edição.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function edit(
        Banda $banda,
    ): View {
        $this->authorize(
            'update',
            $banda,
        );

        $banda->loadMissing([
            'pais',
            'generos',
        ]);

        return view(
            'entities.bands.edit',
            [
                'band' => $banda,
                ...$this->obterDadosFormulario(),
            ],
        );
    }

    /**
     * Atualiza uma banda e os respetivos géneros.
     *
     * @param  UpdateBandRequest  $pedido  Pedido validado.
     * @param  Banda  $banda  Banda atualizada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function update(
        UpdateBandRequest $pedido,
        Banda $banda,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $banda,
        );

        $dados = $pedido->validated();

        DB::transaction(
            static function () use (
                $dados,
                $banda,
            ): void {
                $banda->updateOrFail([
                    'nome' => $dados['name'],

                    'pais_id' => $dados['country_id'],
                ]);

                $banda->generos()->sync(
                    $dados['genres'],
                );
            },
        );

        $banda
            ->refresh()
            ->load([
                'pais',
                'generos',
            ]);

        if ($pedido->expectsJson()) {
            return response()->json([
                'mensagem' => 'Banda atualizada com sucesso.',

                'banda' => $banda,
            ]);
        }

        return redirect()
            ->route('bands.index')
            ->with(
                'estado',
                'Banda atualizada com sucesso.',
            );
    }

    /**
     * Elimina uma banda.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Banda  $banda  Banda eliminada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function destroy(
        Request $pedido,
        Banda $banda,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'delete',
            $banda,
        );

        $banda->deleteOrFail();

        if ($pedido->expectsJson()) {
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT,
            );
        }

        return redirect()
            ->route('bands.index')
            ->with(
                'estado',
                'Banda eliminada com sucesso.',
            );
    }

    /**
     * Obtém os países e os géneros utilizados pelos formulários.
     *
     * Os nomes das chaves permanecem temporariamente iguais aos esperados
     * pelas vistas atuais.
     *
     * @return array{
     *     countries: mixed,
     *     genres: mixed
     * } Dados dos formulários.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterDadosFormulario(): array
    {
        return [
            'countries' => Pais::query()
                ->select([
                    'id',
                    'nome',
                    'codigo_iso',
                ])
                ->orderBy('nome')
                ->get(),

            'genres' => Genero::query()
                ->select([
                    'id',
                    'nome',
                ])
                ->orderBy('nome')
                ->get(),
        ];
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
            100,
        );
    }
}
