<?php

declare(strict_types=1);

namespace App\Http\Controllers\MetalThursday;

use App\Http\Controllers\Controller;
use App\Http\Requests\MetalThursday\StoreEditionRequest;
use App\Http\Requests\MetalThursday\UpdateEditionRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\MusicaFavoritaEdicao;
use App\Servicos\MetalThursday\ServicoMusicasFavoritasEdicao;
use Carbon\CarbonInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gere a consulta, criação, atualização e eliminação de edições.
 *
 * Gere também as músicas favoritas e a ligação da compilação associadas a
 * cada edição.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class ControladorEdicao extends Controller
{
    use AuthorizesRequests;

    /**
     * Número de edições apresentadas por página.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ITENS_POR_PAGINA = 20;

    /**
     * Cria o controlador.
     *
     * @param  ServicoMusicasFavoritasEdicao  $servicoMusicasFavoritas  Serviço
     *                                                                  das escolhas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        private readonly ServicoMusicasFavoritasEdicao $servicoMusicasFavoritas,
    ) {}

    /**
     * Apresenta a lista paginada de edições.
     *
     * @return View Listagem de edições.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function index(): View
    {
        $this->authorize(
            'viewAny',
            Edicao::class,
        );

        $edicoes = Edicao::query()
            ->orderByDesc('data_inicio')
            ->orderByDesc('id')
            ->paginate(
                self::ITENS_POR_PAGINA,
            )
            ->withQueryString();

        return view(
            'entities.editions.index',
            [
                'editions' => $edicoes,
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
     * @version 2.0.0
     */
    public function create(): View
    {
        $this->authorize(
            'create',
            Edicao::class,
        );

        return view(
            'entities.editions.create',
        );
    }

    /**
     * Guarda uma nova edição.
     *
     * @param  StoreEditionRequest  $pedido  Pedido validado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function store(
        StoreEditionRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            Edicao::class,
        );

        $edicao = Edicao::query()->create(
            $this->normalizarDadosEdicao(
                $pedido->validated(),
            ),
        );

        if ($pedido->expectsJson()) {
            return response()->json(
                [
                    'id' => $edicao->getKey(),

                    'nome' => $edicao->nome,

                    /*
                     * Campos temporários utilizados pelo JavaScript atual.
                     */
                    'name' => $edicao->nome,

                    'display_text' => $this->obterTextoApresentacao(
                        $edicao,
                    ),
                ],
                Response::HTTP_CREATED,
            );
        }

        return redirect()
            ->route('editions.index')
            ->with(
                'estado',
                'Edição criada com sucesso.',
            );
    }

    /**
     * Apresenta os detalhes de uma edição.
     *
     * @param  Edicao  $edicao  Edição apresentada.
     * @return View Página da edição.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function show(
        Edicao $edicao,
    ): View {
        $this->authorize(
            'view',
            $edicao,
        );

        $utilizadores = Utilizador::query()
            ->selecionaveis()
            ->select([
                'id',
                'nome',
            ])
            ->get();

        $classificacoes = MusicaFavoritaEdicao::query()
            ->where(
                'edicao_id',
                $edicao->getKey(),
            )
            ->orderBy('utilizador_id')
            ->orderBy('posicao')
            ->orderBy('id')
            ->get()
            ->groupBy('utilizador_id');

        $bloqueada = $utilizadores->isNotEmpty()
            && $utilizadores->every(
                static function (
                    Utilizador $utilizador,
                ) use (
                    $classificacoes,
                ): bool {
                    $escolhas = $classificacoes->get(
                        $utilizador->getKey(),
                    );

                    return $escolhas !== null
                        && $escolhas->count()
                        >= ServicoMusicasFavoritasEdicao::NUMERO_POSICOES;
                },
            );

        return view(
            'entities.editions.show',
            [
                /*
                 * Estas chaves permanecem temporariamente em inglês até à
                 * revisão das vistas.
                 */
                'edition' => $edicao,

                'rankings' => $classificacoes,

                'users' => $utilizadores,

                'isLocked' => $bloqueada,
            ],
        );
    }

    /**
     * Apresenta o formulário de edição.
     *
     * @param  Edicao  $edicao  Edição editada.
     * @return View Formulário de edição.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function edit(
        Edicao $edicao,
    ): View {
        $this->authorize(
            'update',
            $edicao,
        );

        return view(
            'entities.editions.edit',
            [
                'edition' => $edicao,
            ],
        );
    }

    /**
     * Atualiza uma edição.
     *
     * @param  UpdateEditionRequest  $pedido  Pedido validado.
     * @param  Edicao  $edicao  Edição atualizada.
     * @return RedirectResponse Redirecionamento para a listagem.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function update(
        UpdateEditionRequest $pedido,
        Edicao $edicao,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $edicao,
        );

        $edicao->updateOrFail(
            $this->normalizarDadosEdicao(
                $pedido->validated(),
            ),
        );

        return redirect()
            ->route('editions.index')
            ->with(
                'estado',
                'Edição atualizada com sucesso.',
            );
    }

    /**
     * Elimina uma edição sem MetalThursdays associadas.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Edicao  $edicao  Edição eliminada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function destroy(
        Request $pedido,
        Edicao $edicao,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'delete',
            $edicao,
        );

        $possuiMetalThursdays =
            MetalThursday::query()
                ->where(
                    'edicao_id',
                    $edicao->getKey(),
                )
                ->exists();

        if ($possuiMetalThursdays) {
            $mensagem =
                'A edição não pode ser eliminada enquanto possuir MetalThursdays.';

            if ($pedido->expectsJson()) {
                return response()->json(
                    [
                        'message' => $mensagem,
                    ],
                    Response::HTTP_CONFLICT,
                );
            }

            return back()->withErrors([
                'edicao' => $mensagem,
            ]);
        }

        $edicao->deleteOrFail();

        if ($pedido->expectsJson()) {
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT,
            );
        }

        return redirect()
            ->route('editions.index')
            ->with(
                'estado',
                'Edição eliminada com sucesso.',
            );
    }

    /**
     * Guarda as músicas favoritas dos utilizadores.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Edicao  $edicao  Edição alterada.
     * @return RedirectResponse Redirecionamento para a página da edição.
     *
     * @throws AuthenticationException Quando não existe utilizador autenticado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function guardarMusicasFavoritas(
        Request $pedido,
        Edicao $edicao,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $edicao,
        );

        $dados = $pedido->validate([
            'rankings' => [
                'required',
                'array',
                'min:1',
            ],

            'rankings.*' => [
                'required',
                'array',
                'size:3',
            ],

            'rankings.*.*' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $identificadorRegistador = $pedido
            ->user()
            ?->getAuthIdentifier();

        if (
            ! is_numeric($identificadorRegistador)
            || (int) $identificadorRegistador < 1
        ) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para guardar as músicas.',
            );
        }

        $this->servicoMusicasFavoritas
            ->sincronizar(
                $edicao,
                $dados['rankings'],
                (int) $identificadorRegistador,
            );

        return back()->with(
            'estado',
            'Músicas favoritas guardadas com sucesso.',
        );
    }

    /**
     * Atualiza a ligação da compilação.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Edicao  $edicao  Edição atualizada.
     * @return RedirectResponse Redirecionamento para a página da edição.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function atualizarLigacaoCompilacao(
        Request $pedido,
        Edicao $edicao,
    ): RedirectResponse {
        $this->authorize(
            'update',
            $edicao,
        );

        $dados = $pedido->validate([
            'ligacao_compilacao' => [
                'nullable',
                'url:http,https',
                'max:2048',
            ],

            /*
             * Campo temporário utilizado pela vista atual.
             */
            'compilation_link' => [
                'nullable',
                'url:http,https',
                'max:2048',
            ],
        ]);

        $ligacao = array_key_exists(
            'ligacao_compilacao',
            $dados,
        )
            ? $dados['ligacao_compilacao']
            : ($dados['compilation_link'] ?? null);

        $edicao->updateOrFail([
            'ligacao_compilacao' => is_string($ligacao)
                && trim($ligacao) !== ''
                ? trim($ligacao)
                : null,
        ]);

        return back()->with(
            'estado',
            'Ligação da compilação atualizada com sucesso.',
        );
    }

    /**
     * Normaliza os dados de uma edição.
     *
     * Os campos ingleses permanecem temporariamente suportados até à revisão
     * dos pedidos e das vistas.
     *
     * @param  array<string, mixed>  $dados  Dados validados.
     * @return array{
     *     nome: string,
     *     data_inicio: string,
     *     data_fim: string|null,
     *     ligacao_compilacao: string|null
     * } Dados persistíveis.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarDadosEdicao(
        array $dados,
    ): array {
        return [
            'nome' => $this->obterTextoObrigatorio(
                $dados['nome']
                    ?? $dados['name']
                    ?? null,
                'nome',
            ),

            'data_inicio' => $this->obterTextoObrigatorio(
                $dados['data_inicio']
                    ?? $dados['start_date']
                    ?? null,
                'data_inicio',
            ),

            'data_fim' => $this->obterTextoOpcional(
                $dados['data_fim']
                    ?? $dados['end_date']
                    ?? null,
            ),

            'ligacao_compilacao' => $this->obterTextoOpcional(
                $dados['ligacao_compilacao']
                    ?? $dados['compilation_link']
                    ?? null,
            ),
        ];
    }

    /**
     * Obtém um texto obrigatório.
     *
     * @param  mixed  $valor  Valor recebido.
     * @param  string  $campo  Nome do campo.
     * @return string Texto normalizado.
     *
     * @throws LogicException Quando o valor não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterTextoObrigatorio(
        mixed $valor,
        string $campo,
    ): string {
        $texto = $this->obterTextoOpcional(
            $valor,
        );

        if ($texto === null) {
            throw new LogicException(
                sprintf(
                    'O pedido validado não contém o campo %s.',
                    $campo,
                ),
            );
        }

        return $texto;
    }

    /**
     * Normaliza um texto opcional.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Texto normalizado ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterTextoOpcional(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $texto = trim(
            $valor,
        );

        return $texto === ''
            ? null
            : $texto;
    }

    /**
     * Obtém o texto apresentado na resposta JSON de criação.
     *
     * @param  Edicao  $edicao  Edição criada.
     * @return string Texto da edição.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterTextoApresentacao(
        Edicao $edicao,
    ): string {
        $dataInicio = $edicao->data_inicio;

        $inicio = $dataInicio instanceof CarbonInterface
            ? $dataInicio->format('d/m/Y')
            : (string) $dataInicio;

        $dataFim = $edicao->data_fim;

        if ($dataFim === null) {
            return sprintf(
                '%s — %s',
                $edicao->nome,
                $inicio,
            );
        }

        $fim = $dataFim instanceof CarbonInterface
            ? $dataFim->format('d/m/Y')
            : (string) $dataFim;

        return sprintf(
            '%s — %s a %s',
            $edicao->nome,
            $inicio,
            $fim,
        );
    }
}
