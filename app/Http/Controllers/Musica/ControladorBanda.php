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
use Illuminate\Database\Eloquent\Collection;
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
 * @version 3.0.0
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

        $pesquisa =
            $this->normalizarPesquisa(
                $pedido->query(
                    'pesquisa',
                ),
            );

        $bandas =
            Banda::query()
            ->select([
                'id',
                'nome',
                'pais_id',
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
            'musica.bandas.indice',
            [
                'bandas' =>
                $bandas,

                'pesquisaAtual' =>
                $pesquisa,
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
            Banda::class,
        );

        return view(
            'musica.bandas.criar',
            $this->obterDadosFormulario(
                $pedido,
            ),
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
     * @version 3.0.0
     */
    public function store(
        CriarBandaRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            Banda::class,
        );

        $dados =
            $pedido->validated();

        $banda =
            DB::transaction(
                static function () use (
                    $dados,
                ): Banda {
                    $banda =
                        Banda::query()
                        ->create([
                            'nome' => $dados['nome'],

                            'pais_id' => $dados['pais_id'],
                        ]);

                    $banda
                        ->generos()
                        ->sync(
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
            ->route(
                'bandas.indice',
            )
            ->with(
                'sucesso',
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

        $seccoes =
            SeccaoMetalThursday::query()
            ->select(
                $tabelaSeccoes . '.*',
            )
            ->join(
                $tabelaMetalThursdays
                    . ' as '
                    . $aliasOrdenacao,
                $aliasOrdenacao . '.id',
                '=',
                $tabelaSeccoes
                    . '.metal_thursday_id',
            )
            ->where(
                $tabelaSeccoes . '.banda_id',
                $banda->getKey(),
            )
            ->with([
                'metalThursday.autor',
                'tipoSeccao',
            ])
            ->orderByDesc(
                $aliasOrdenacao . '.data',
            )
            ->orderByDesc(
                $tabelaSeccoes . '.id',
            )
            ->paginate(
                self::ITENS_POR_PAGINA,
            )
            ->withQueryString();

        return view(
            'musica.bandas.detalhes',
            [
                'banda' =>
                $banda,

                'seccoes' =>
                $seccoes,

                ...$this->obterDadosCabecalho(
                    $banda,
                ),
            ],
        );
    }

    /**
     * Apresenta o formulário de edição.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Banda  $banda  Banda editada.
     * @return View Formulário de edição.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function edit(
        Request $pedido,
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
            'musica.bandas.editar',
            $this->obterDadosFormulario(
                $pedido,
                $banda,
            ),
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
     * @version 3.0.0
     */
    public function update(
        AtualizarBandaRequest $pedido,
        Banda $banda,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $banda,
        );

        $dados =
            $pedido->validated();

        DB::transaction(
            static function () use (
                $dados,
                $banda,
            ): void {
                $banda->updateOrFail([
                    'nome' => $dados['nome'],

                    'pais_id' => $dados['pais_id'],
                ]);

                $banda
                    ->generos()
                    ->sync(
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
            ->route(
                'bandas.indice',
            )
            ->with(
                'sucesso',
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
     * @version 3.0.0
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
            ->route(
                'bandas.indice',
            )
            ->with(
                'sucesso',
                'Banda eliminada com sucesso.',
            );
    }

    /**
     * Obtém os dados utilizados pelo formulário de bandas.
     *
     * Quando existe uma submissão anterior inválida, os respetivos valores
     * são recuperados da sessão. Durante a edição, são utilizados como
     * predefinição os dados da banda.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Banda|null  $banda  Banda editada ou nula durante a criação.
     * @return array{
     *     banda: Banda|null,
     *     paises: Collection<int, Pais>,
     *     generos: Collection<int, Genero>,
     *     emEdicao: bool,
     *     enderecoFormulario: string,
     *     nomeBanda: string,
     *     identificadorPaisSelecionado: string,
     *     identificadoresGenerosSelecionados: array<int, string>,
     *     textoBotaoSubmissao: string
     * } Dados preparados.
     *
     * @since 2.0.0
     *
     * @version 3.0.0
     */
    private function obterDadosFormulario(
        Request $pedido,
        ?Banda $banda = null,
    ): array {
        $emEdicao =
            $banda instanceof Banda;

        if ($emEdicao) {
            $banda->loadMissing(
                'generos',
            );

            $enderecoFormulario =
                route(
                    'bandas.atualizar',
                    $banda,
                );

            $identificadoresGenerosModelo =
                $banda
                ->generos
                ->modelKeys();
        } else {
            $enderecoFormulario =
                route(
                    'bandas.guardar',
                );

            $identificadoresGenerosModelo =
                [];
        }

        return [
            'banda' => $banda,

            'paises' => Pais::query()
                ->select([
                    'id',
                    'nome',
                    'codigo_iso',
                ])
                ->orderBy(
                    'nome',
                )
                ->orderBy(
                    'id',
                )
                ->get(),

            'generos' => Genero::query()
                ->select([
                    'id',
                    'nome',
                ])
                ->orderBy(
                    'nome',
                )
                ->orderBy(
                    'id',
                )
                ->get(),

            'emEdicao' => $emEdicao,

            'enderecoFormulario' => $enderecoFormulario,

            'nomeBanda' => $this->normalizarTextoFormulario(
                $pedido->old(
                    'nome',
                    $banda?->nome,
                ),
            ),

            'identificadorPaisSelecionado' => $this->normalizarIdentificadorFormulario(
                $pedido->old(
                    'pais_id',
                    $banda?->pais_id,
                ),
            ),

            'identificadoresGenerosSelecionados' => $this->normalizarListaIdentificadoresFormulario(
                $pedido->old(
                    'generos',
                    $identificadoresGenerosModelo,
                ),
            ),

            'textoBotaoSubmissao' => $emEdicao
                ? 'Guardar alterações'
                : 'Criar banda',
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

        $identificadores =
            [];

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
     * Prepara os dados do cabeçalho da página de detalhes da banda.
     *
     * @param  Banda  $banda  Banda apresentada.
     * @return array{
     *     nomePaisBanda: string|null,
     *     nomesGenerosBanda: string|null
     * } Dados preparados.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterDadosCabecalho(
        Banda $banda,
    ): array {
        $nomePais =
            $banda->pais instanceof Pais
            ? $this->normalizarTextoApresentacao(
                $banda->pais->nome,
            )
            : null;

        $nomesGeneros = [];

        foreach ($banda->generos as $genero) {
            if (! $genero instanceof Genero) {
                continue;
            }

            $nomeGenero =
                $this->normalizarTextoApresentacao(
                    $genero->nome,
                );

            if ($nomeGenero !== null) {
                $nomesGeneros[] =
                    $nomeGenero;
            }
        }

        return [
            'nomePaisBanda' =>
            $nomePais,

            'nomesGenerosBanda' =>
            $nomesGeneros !== []
                ? implode(
                    ', ',
                    $nomesGeneros,
                )
                : null,
        ];
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
