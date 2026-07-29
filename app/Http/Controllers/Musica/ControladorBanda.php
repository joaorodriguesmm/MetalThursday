<?php

declare(strict_types=1);

namespace App\Http\Controllers\Musica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Musica\AtualizarBandaRequest;
use App\Http\Requests\Musica\CriarBandaRequest;
use App\Models\Geografia\OrigemGeografica;
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
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gere a consulta, criação, atualização e eliminação de bandas.
 *
 * A persistência dos dados principais e a sincronização dos géneros são
 * executadas atomicamente. As operações sobre bandas existentes voltam a
 * obter e a bloquear o registo dentro da transação.
 *
 * @since 1.0.0
 *
 * @version 4.0.0
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
     * @version 2.0.0
     */
    private const REGISTOS_POR_PAGINA =
        20;

    /**
     * Comprimento máximo do termo de pesquisa.
     *
     * @var int
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_PESQUISA =
        100;

    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @var int
     *
     * @since 4.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO =
        3;

    /**
     * Apresenta a lista paginada de bandas.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return View Listagem de bandas.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function indice(
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
                    'origem_geografica_id',
                ])
                ->with([
                    'origemGeografica' => static fn (
                        Builder $construtor,
                    ): Builder => $construtor->select([
                        'id',
                        'nome',
                    ]),

                    'generos' => static fn (
                        Builder $construtor,
                    ): Builder => $construtor
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
            'musica.bandas.indice',
            [
                'bandas' => $bandas,

                'pesquisaAtual' => $pesquisa,
            ],
        );
    }

    /**
     * Apresenta o formulário de criação de uma banda.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @return View Formulário de criação.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function criar(
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
     * Guarda uma nova banda e sincroniza os respetivos géneros.
     *
     * @param  CriarBandaRequest  $pedido  Pedido validado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function guardar(
        CriarBandaRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            Banda::class,
        );

        /**
         * @var array{
         *     nome: string,
         *     origem_geografica_id: int,
         *     generos: list<int>
         * } $dados
         */
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

                                'origem_geografica_id' => $dados['origem_geografica_id'],
                            ]);

                    $banda
                        ->generos()
                        ->sync(
                            $dados['generos'],
                        );

                    return $banda;
                },
                self::TENTATIVAS_TRANSACAO,
            );

        $banda->load([
            'origemGeografica:id,nome',
            'generos:id,nome',
        ]);

        if ($pedido->expectsJson()) {
            return response()->json(
                [
                    'mensagem' => 'Banda criada com sucesso.',

                    'banda' => $this->serializarBanda(
                        $banda,
                    ),
                ],
                Response::HTTP_CREATED,
            );
        }

        return to_route(
            'bandas.indice',
        )->with(
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
     * @version 4.0.0
     */
    public function detalhes(
        Banda $banda,
    ): View {
        $this->authorize(
            'view',
            $banda,
        );

        $banda->loadMissing([
            'origemGeografica:id,nome',
            'generos:id,nome',
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
                    $tabelaSeccoes.'.*',
                )
                ->join(
                    $tabelaMetalThursdays
                        .' as '
                        .$aliasOrdenacao,
                    $aliasOrdenacao.'.id',
                    '=',
                    $tabelaSeccoes.'.metal_thursday_id',
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
                    self::REGISTOS_POR_PAGINA,
                )
                ->withQueryString();

        return view(
            'musica.bandas.detalhes',
            [
                'banda' => $banda,

                'seccoes' => $seccoes,

                ...$this->obterDadosCabecalho(
                    $banda,
                ),
            ],
        );
    }

    /**
     * Apresenta o formulário de edição de uma banda.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Banda  $banda  Banda editada.
     * @return View Formulário de edição.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function editar(
        Request $pedido,
        Banda $banda,
    ): View {
        $this->authorize(
            'update',
            $banda,
        );

        $banda->loadMissing([
            'origemGeografica:id,nome',
            'generos:id,nome',
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
     * O registo é novamente obtido e bloqueado dentro da transação. A
     * autorização é aplicada ao modelo bloqueado.
     *
     * @param  AtualizarBandaRequest  $pedido  Pedido validado.
     * @param  Banda  $banda  Banda atualizada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function atualizar(
        AtualizarBandaRequest $pedido,
        Banda $banda,
    ): JsonResponse|RedirectResponse {
        /**
         * @var array{
         *     nome: string,
         *     origem_geografica_id: int,
         *     generos: list<int>
         * } $dados
         */
        $dados =
            $pedido->validated();

        $bandaAtualizada =
            DB::transaction(
                function () use (
                    $banda,
                    $dados,
                ): Banda {
                    $bandaBloqueada =
                        Banda::query()
                            ->whereKey(
                                $banda->getKey(),
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $this->authorize(
                        'update',
                        $bandaBloqueada,
                    );

                    $bandaBloqueada->updateOrFail([
                        'nome' => $dados['nome'],

                        'origem_geografica_id' => $dados['origem_geografica_id'],
                    ]);

                    $bandaBloqueada
                        ->generos()
                        ->sync(
                            $dados['generos'],
                        );

                    return $bandaBloqueada;
                },
                self::TENTATIVAS_TRANSACAO,
            );

        $bandaAtualizada
            ->refresh()
            ->load([
                'origemGeografica:id,nome',
                'generos:id,nome',
            ]);

        if ($pedido->expectsJson()) {
            return response()->json([
                'mensagem' => 'Banda atualizada com sucesso.',

                'banda' => $this->serializarBanda(
                    $bandaAtualizada,
                ),
            ]);
        }

        return to_route(
            'bandas.indice',
        )->with(
            'sucesso',
            'Banda atualizada com sucesso.',
        );
    }

    /**
     * Elimina logicamente uma banda.
     *
     * O registo é bloqueado antes da autorização e da eliminação.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Banda  $banda  Banda eliminada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function eliminar(
        Request $pedido,
        Banda $banda,
    ): JsonResponse|RedirectResponse {
        DB::transaction(
            function () use (
                $banda,
            ): void {
                $bandaBloqueada =
                    Banda::query()
                        ->whereKey(
                            $banda->getKey(),
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->authorize(
                    'delete',
                    $bandaBloqueada,
                );

                $bandaBloqueada->deleteOrFail();
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
            'bandas.indice',
        )->with(
            'sucesso',
            'Banda eliminada com sucesso.',
        );
    }

    /**
     * Obtém os dados utilizados pelo formulário de bandas.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Banda|null  $banda  Banda editada ou nula durante a criação.
     * @return array{
     *     banda: Banda|null,
     *     origensGeograficas: Collection<int, OrigemGeografica>,
     *     generos: Collection<int, Genero>,
     *     emEdicao: bool,
     *     enderecoFormulario: string,
     *     nomeBanda: string,
     *     identificadorOrigemGeograficaSelecionada: string,
     *     identificadoresGenerosSelecionados: list<string>,
     *     textoBotaoSubmissao: string
     * } Dados preparados.
     *
     * @since 2.0.0
     *
     * @version 4.0.0
     */
    private function obterDadosFormulario(
        Request $pedido,
        ?Banda $banda = null,
    ): array {
        $emEdicao =
            $banda instanceof Banda;

        if ($emEdicao) {
            $banda->loadMissing(
                'generos:id,nome',
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

            'origensGeograficas' => OrigemGeografica::query()
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

            'identificadorOrigemGeograficaSelecionada' => $this->normalizarIdentificadorFormulario(
                $pedido->old(
                    'origem_geografica_id',
                    $banda?->origem_geografica_id,
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
     * Prepara os dados do cabeçalho da página de detalhes da banda.
     *
     * @param  Banda  $banda  Banda apresentada.
     * @return array{
     *     nomeOrigemGeograficaBanda: string,
     *     nomesGenerosBanda: string|null
     * } Dados preparados.
     *
     * @throws LogicException Quando a origem geográfica da banda não está
     *                        disponível.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private function obterDadosCabecalho(
        Banda $banda,
    ): array {
        $origemGeografica =
            $banda->origemGeografica;

        if (! $origemGeografica instanceof OrigemGeografica) {
            throw new LogicException(
                'A banda não possui uma origem geográfica válida.',
            );
        }

        $nomesGeneros = [];

        foreach ($banda->generos as $genero) {
            if (! $genero instanceof Genero) {
                throw new LogicException(
                    'A banda possui um género persistido inválido.',
                );
            }

            $nomesGeneros[] =
                $genero->nome;
        }

        return [
            'nomeOrigemGeograficaBanda' => $origemGeografica->nome,

            'nomesGenerosBanda' => $nomesGeneros !== []
                ? implode(
                    ', ',
                    $nomesGeneros,
                )
                : null,
        ];
    }

    /**
     * Converte uma banda para o formato da resposta HTTP.
     *
     * @param  Banda  $banda  Banda convertida.
     * @return array{
     *     id: int,
     *     nome: string,
     *     origem_geografica_id: int,
     *     origem_geografica: array{id: int, nome: string},
     *     generos: list<array{id: int, nome: string}>
     * } Dados da banda.
     *
     * @throws LogicException Quando a origem geográfica da banda não está
     *                        disponível.
     *
     * @since 4.0.0
     *
     * @version 1.0.0
     */
    private function serializarBanda(
        Banda $banda,
    ): array {
        $origemGeografica =
            $banda->origemGeografica;

        if (! $origemGeografica instanceof OrigemGeografica) {
            throw new LogicException(
                'A banda não possui uma origem geográfica válida.',
            );
        }

        $generos = [];

        foreach ($banda->generos as $genero) {
            if (! $genero instanceof Genero) {
                throw new LogicException(
                    'A banda possui um género persistido inválido.',
                );
            }

            $generos[] = [
                'id' => (int) $genero->getKey(),

                'nome' => $genero->nome,
            ];
        }

        return [
            'id' => (int) $banda->getKey(),

            'nome' => $banda->nome,

            'origem_geografica_id' => (int) $origemGeografica->getKey(),

            'origem_geografica' => [
                'id' => (int) $origemGeografica->getKey(),

                'nome' => $origemGeografica->nome,
            ],

            'generos' => $generos,
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
            self::COMPRIMENTO_MAXIMO_PESQUISA,
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
     * Identificadores inválidos são ignorados apenas durante a reconstrução
     * visual do formulário. Valores repetidos são removidos.
     *
     * @param  mixed  $valores  Valores recebidos.
     * @return list<string> Identificadores normalizados.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
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
