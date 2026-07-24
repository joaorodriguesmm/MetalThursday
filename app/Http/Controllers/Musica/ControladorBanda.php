<?php

declare(strict_types=1);

namespace App\Http\Controllers\Musica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Musica\AtualizarBandaRequest;
use App\Http\Requests\Musica\CriarBandaRequest;
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
 * Os nomes dos métodos públicos correspondem ao contrato dos controladores
 * de recursos do Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
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
     * Comprimento máximo do termo de pesquisa.
     *
     * @var int
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private const LIMITE_PESQUISA = 100;

    /**
     * Apresenta a lista paginada de bandas.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return View Listagem de bandas.
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
            Banda::class,
        );

        $pesquisa = $this->normalizarPesquisa(
            $pedido->query('pesquisa'),
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
                'bandas' => $bandas,
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
     * @param  CriarBandaRequest  $pedido  Pedido validado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function store(
        CriarBandaRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            Banda::class,
        );

        $dados = $pedido->validated();

        $banda = DB::transaction(
            static function () use ($dados): Banda {
                $banda = Banda::query()->create([
                    'nome' => $dados['nome'],

                    'pais_id' => $dados['pais_id'],
                ]);

                $banda->generos()->sync(
                    $dados['generos'],
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
     * Apresenta os detalhes de uma banda.
     *
     * @param  Banda  $banda  Banda apresentada.
     * @return View Página de detalhes.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
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

        $modeloSeccao = new SeccaoMetalThursday;
        $modeloMetalThursday = new MetalThursday;

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
                'banda' => $banda,

                'seccoes' => $seccoes,
            ],
        );
    }

    /**
     * Apresenta o formulário de edição.
     *
     * @param  Banda  $banda  Banda editada.
     * @return View Formulário de edição.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
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
                'banda' => $banda,

                ...$this->obterDadosFormulario(),
            ],
        );
    }

    /**
     * Atualiza uma banda e sincroniza os respetivos géneros.
     *
     * @param  AtualizarBandaRequest  $pedido  Pedido validado.
     * @param  Banda  $banda  Banda atualizada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function update(
        AtualizarBandaRequest $pedido,
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
                    'nome' => $dados['nome'],

                    'pais_id' => $dados['pais_id'],
                ]);

                $banda->generos()->sync(
                    $dados['generos'],
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
     * @version 2.1.0
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
     * Obtém os dados utilizados pelos formulários.
     *
     * @return array<string, mixed> Dados dos formulários.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterDadosFormulario(): array
    {
        return [
            'paises' => Pais::query()
                ->select([
                    'id',
                    'nome',
                    'codigo_iso',
                ])
                ->orderBy('nome')
                ->orderBy('id')
                ->get(),

            'generos' => Genero::query()
                ->select([
                    'id',
                    'nome',
                ])
                ->orderBy('nome')
                ->orderBy('id')
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
     * @version 1.1.0
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
