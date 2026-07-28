<?php

declare(strict_types=1);

namespace App\Http\Controllers\MetalThursday;

use App\Http\Controllers\Controller;
use App\Http\Requests\MetalThursday\AtualizarEdicaoRequest;
use App\Http\Requests\MetalThursday\AtualizarLigacaoCompilacaoEdicaoRequest;
use App\Http\Requests\MetalThursday\CriarEdicaoRequest;
use App\Http\Requests\MetalThursday\GuardarMusicasFavoritasEdicaoRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Servicos\MetalThursday\ServicoApresentacaoDetalhesEdicao;
use App\Servicos\MetalThursday\ServicoMusicasFavoritasEdicao;
use Carbon\CarbonInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gere a consulta, criação, atualização e eliminação de edições.
 *
 * Gere também as músicas favoritas e a ligação da compilação associadas a
 * cada edição.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
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
     *                                                                  das músicas
     *                                                                  favoritas.
     * @param  ServicoApresentacaoDetalhesEdicao  $servicoApresentacaoDetalhes
     *                                                     Serviço de apresentação.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function __construct(
        private readonly ServicoMusicasFavoritasEdicao $servicoMusicasFavoritas,
        private readonly ServicoApresentacaoDetalhesEdicao $servicoApresentacaoDetalhes,
    ) {}

    /**
     * Apresenta a lista paginada de edições.
     *
     * @return View Listagem de edições.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function index(): View
    {
        $this->authorize(
            'viewAny',
            Edicao::class,
        );

        $edicoes = Edicao::query()
            ->orderByDesc(
                'data_inicio',
            )
            ->orderByDesc(
                'id',
            )
            ->paginate(
                self::ITENS_POR_PAGINA,
            )
            ->withQueryString();

        return view(
            'metal-thursday.edicoes.indice',
            [
                'edicoes' => $edicoes,
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
     * @version 3.0.0
     */
    public function create(): View
    {
        $this->authorize(
            'create',
            Edicao::class,
        );

        return view(
            'metal-thursday.edicoes.criar',
            $this->obterDadosFormulario(),
        );
    }

    /**
     * Guarda uma nova edição.
     *
     * @param  CriarEdicaoRequest  $pedido  Pedido validado.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function store(
        CriarEdicaoRequest $pedido,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'create',
            Edicao::class,
        );

        $edicao = Edicao::query()->create(
            $pedido->safe()->only([
                'nome',
                'data_inicio',
                'data_fim',
            ]),
        );

        if ($pedido->expectsJson()) {
            return response()->json(
                [
                    'mensagem' =>
                    'Edição criada com sucesso.',

                    'edicao' =>
                    $this->serializarEdicao(
                        $edicao,
                    ),
                ],
                Response::HTTP_CREATED,
            );
        }

        return redirect()
            ->route(
                'edicoes.indice',
            )
            ->with(
                'sucesso',
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
     * @version 3.0.0
     */
    public function show(
        Edicao $edicao,
    ): View {
        $this->authorize(
            'view',
            $edicao,
        );

        return view(
            'metal-thursday.edicoes.detalhes',
            [
                'edicao' =>
                $edicao,

                ...$this
                    ->servicoApresentacaoDetalhes
                    ->preparar(
                        $edicao,
                    ),
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
     * @version 3.0.0
     */
    public function edit(
        Edicao $edicao,
    ): View {
        $this->authorize(
            'update',
            $edicao,
        );

        return view(
            'metal-thursday.edicoes.editar',
            [
                'edicao' =>
                $edicao,

                ...$this->obterDadosFormulario(
                    $edicao,
                ),
            ],
        );
    }

    /**
     * Atualiza uma edição.
     *
     * @param  AtualizarEdicaoRequest  $pedido  Pedido validado.
     * @param  Edicao  $edicao  Edição atualizada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function update(
        AtualizarEdicaoRequest $pedido,
        Edicao $edicao,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $edicao,
        );

        $edicao->updateOrFail(
            $pedido->safe()->only([
                'nome',
                'data_inicio',
                'data_fim',
            ]),
        );

        $edicao->refresh();

        if ($pedido->expectsJson()) {
            return response()->json([
                'mensagem' =>
                'Edição atualizada com sucesso.',

                'edicao' =>
                $this->serializarEdicao(
                    $edicao,
                ),
            ]);
        }

        return redirect()
            ->route(
                'edicoes.indice',
            )
            ->with(
                'sucesso',
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
     * @version 3.0.0
     */
    public function destroy(
        Request $pedido,
        Edicao $edicao,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'delete',
            $edicao,
        );

        $possuiMetalThursdays = MetalThursday::query()
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
                        'mensagem' =>
                        $mensagem,
                    ],
                    Response::HTTP_CONFLICT,
                );
            }

            return back()->withErrors([
                'edicao' =>
                $mensagem,
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
            ->route(
                'edicoes.indice',
            )
            ->with(
                'sucesso',
                'Edição eliminada com sucesso.',
            );
    }

    /**
     * Guarda as músicas favoritas dos utilizadores.
     *
     * @param  GuardarMusicasFavoritasEdicaoRequest  $pedido  Pedido validado.
     * @param  Edicao  $edicao  Edição alterada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @throws AuthenticationException Quando não existe utilizador
     *                                 autenticado.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function guardarMusicasFavoritas(
        GuardarMusicasFavoritasEdicaoRequest $pedido,
        Edicao $edicao,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $edicao,
        );

        $identificadorRegistador =
            $this->obterIdentificadorUtilizador(
                $pedido,
            );

        $this->servicoMusicasFavoritas
            ->sincronizar(
                $edicao,
                $pedido->obterClassificacoes(),
                $identificadorRegistador,
            );

        if ($pedido->expectsJson()) {
            return response()->json([
                'mensagem' =>
                'Músicas favoritas guardadas com sucesso.',
            ]);
        }

        return back()->with(
            'sucesso',
            'Músicas favoritas guardadas com sucesso.',
        );
    }

    /**
     * Atualiza a ligação da compilação.
     *
     * @param  AtualizarLigacaoCompilacaoEdicaoRequest  $pedido  Pedido validado.
     * @param  Edicao  $edicao  Edição atualizada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function atualizarLigacaoCompilacao(
        AtualizarLigacaoCompilacaoEdicaoRequest $pedido,
        Edicao $edicao,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $edicao,
        );

        $edicao->updateOrFail([
            'ligacao_compilacao' =>
            $pedido->obterLigacaoCompilacao(),
        ]);

        if ($pedido->expectsJson()) {
            return response()->json([
                'mensagem' =>
                'Ligação da compilação atualizada com sucesso.',

                'ligacao_compilacao' =>
                $edicao->ligacao_compilacao,
            ]);
        }

        return back()->with(
            'sucesso',
            'Ligação da compilação atualizada com sucesso.',
        );
    }

    /**
     * Prepara os dados utilizados pelo formulário de uma edição.
     *
     * @param  Edicao|null  $edicao  Edição editada ou nula durante a criação.
     * @return array{
     *     emEdicao: bool,
     *     enderecoFormulario: string,
     *     nomeEdicao: string,
     *     dataInicioEdicao: string,
     *     dataFimEdicao: string,
     *     textoBotaoSubmissao: string
     * } Dados preparados para o formulário.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterDadosFormulario(
        ?Edicao $edicao = null,
    ): array {
        $emEdicao =
            $edicao instanceof Edicao;

        return [
            'emEdicao' =>
            $emEdicao,

            'enderecoFormulario' =>
            $emEdicao
                ? route(
                    'edicoes.atualizar',
                    $edicao,
                )
                : route(
                    'edicoes.guardar',
                ),

            'nomeEdicao' =>
            $edicao instanceof Edicao
                ? (string) $edicao->nome
                : '',

            'dataInicioEdicao' =>
            $this->formatarDataFormulario(
                $edicao?->data_inicio,
            ),

            'dataFimEdicao' =>
            $this->formatarDataFormulario(
                $edicao?->data_fim,
            ),

            'textoBotaoSubmissao' =>
            $emEdicao
                ? 'Guardar alterações'
                : 'Criar edição',
        ];
    }

    /**
     * Formata uma data para utilização num campo HTML do tipo `date`.
     *
     * @param  mixed  $data  Data recebida.
     * @return string Data no formato `Y-m-d` ou texto vazio.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function formatarDataFormulario(
        mixed $data,
    ): string {
        if (! $data instanceof CarbonInterface) {
            return '';
        }

        return $data->format(
            'Y-m-d',
        );
    }

    /**
     * Obtém o identificador do utilizador autenticado.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return int Identificador do utilizador.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterIdentificadorUtilizador(
        Request $pedido,
    ): int {
        $utilizador =
            $pedido->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para guardar as músicas.',
            );
        }

        $identificador =
            $utilizador->getKey();

        if (
            ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new AuthenticationException(
                'Não foi possível identificar o utilizador autenticado.',
            );
        }

        return (int) $identificador;
    }

    /**
     * Converte uma edição para o formato de resposta HTTP.
     *
     * @param  Edicao  $edicao  Edição convertida.
     * @return array{
     *     id: int,
     *     nome: string,
     *     data_inicio: string,
     *     data_fim: string|null,
     *     ligacao_compilacao: string|null,
     *     texto_apresentacao: string
     * } Dados da edição.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function serializarEdicao(
        Edicao $edicao,
    ): array {
        $dataInicio =
            $edicao->data_inicio;

        $dataFim =
            $edicao->data_fim;

        return [
            'id' =>
            (int) $edicao->getKey(),

            'nome' =>
            (string) $edicao->nome,

            'data_inicio' =>
            $dataInicio instanceof CarbonInterface
                ? $dataInicio->format(
                    'Y-m-d',
                )
                : (string) $dataInicio,

            'data_fim' =>
            $dataFim instanceof CarbonInterface
                ? $dataFim->format(
                    'Y-m-d',
                )
                : null,

            'ligacao_compilacao' =>
            is_string(
                $edicao->ligacao_compilacao,
            )
                ? $edicao->ligacao_compilacao
                : null,

            'texto_apresentacao' =>
            $this->obterTextoApresentacao(
                $edicao,
            ),
        ];
    }

    /**
     * Obtém o texto apresentado para uma edição.
     *
     * @param  Edicao  $edicao  Edição apresentada.
     * @return string Texto da edição.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterTextoApresentacao(
        Edicao $edicao,
    ): string {
        $dataInicio =
            $edicao->data_inicio;

        $inicio =
            $dataInicio instanceof CarbonInterface
            ? $dataInicio->format(
                'd/m/Y',
            )
            : (string) $dataInicio;

        $dataFim =
            $edicao->data_fim;

        if (! $dataFim instanceof CarbonInterface) {
            return sprintf(
                '%s — %s',
                $edicao->nome,
                $inicio,
            );
        }

        return sprintf(
            '%s — %s a %s',
            $edicao->nome,
            $inicio,
            $dataFim->format(
                'd/m/Y',
            ),
        );
    }
}
