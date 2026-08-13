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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gere a consulta, criação, atualização e eliminação de edições.
 *
 * Gere também as músicas favoritas e a ligação da compilação associadas a
 * cada edição. As alterações a edições existentes são executadas sob bloqueio
 * transacional para manter a consistência perante pedidos concorrentes.
 *
 * @since 1.0.0
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
     */
    private const REGISTOS_POR_PAGINA = 20;

    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Mensagem apresentada quando uma edição ainda possui MetalThursdays.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_EDICAO_COM_METAL_THURSDAYS =
        'A edição não pode ser eliminada enquanto possuir MetalThursdays.';

    /**
     * Cria o controlador.
     *
     * @param  ServicoMusicasFavoritasEdicao  $servicoMusicasFavoritas  Serviço
     *                                                                  das músicas
     *                                                                  favoritas.
     * @param  ServicoApresentacaoDetalhesEdicao  $servicoApresentacaoDetalhes
     *                                                                          Serviço de apresentação.
     *
     * @since 2.0.0
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
     */
    public function indice(): View
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
                self::REGISTOS_POR_PAGINA,
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
     * Apresenta o formulário de criação de uma edição.
     *
     * @return View Formulário de criação.
     *
     * @since 1.0.0
     */
    public function criar(): View
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
     */
    public function guardar(
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
                    'mensagem' => 'Edição criada com sucesso.',

                    'edicao' => $this->serializarEdicao(
                        $edicao,
                    ),
                ],
                Response::HTTP_CREATED,
            );
        }

        return to_route(
            'edicoes.indice',
        )->with(
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
     */
    public function detalhes(
        Edicao $edicao,
    ): View {
        $this->authorize(
            'view',
            $edicao,
        );

        return view(
            'metal-thursday.edicoes.detalhes',
            [
                'edicao' => $edicao,

                ...$this
                    ->servicoApresentacaoDetalhes
                    ->preparar(
                        $edicao,
                    ),
            ],
        );
    }

    /**
     * Apresenta o formulário de edição de uma edição.
     *
     * @param  Edicao  $edicao  Edição editada.
     * @return View Formulário de edição.
     *
     * @since 1.0.0
     */
    public function editar(
        Edicao $edicao,
    ): View {
        $this->authorize(
            'update',
            $edicao,
        );

        return view(
            'metal-thursday.edicoes.editar',
            [
                'edicao' => $edicao,

                ...$this->obterDadosFormulario(
                    $edicao,
                ),
            ],
        );
    }

    /**
     * Atualiza uma edição sob bloqueio transacional.
     *
     * A autorização é verificada antes de abrir a transação. A edição é
     * novamente obtida e bloqueada imediatamente antes da atualização.
     *
     * O modelo só é persistido quando os dados normalizados alteram
     * efetivamente o estado existente, evitando atualizar os dados de
     * auditoria sem uma alteração real.
     *
     * @param  AtualizarEdicaoRequest  $pedido  Pedido validado.
     * @param  Edicao  $edicao  Edição atualizada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     */
    public function atualizar(
        AtualizarEdicaoRequest $pedido,
        Edicao $edicao,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $edicao,
        );

        $dados =
            $pedido->safe()->only([
                'nome',
                'data_inicio',
                'data_fim',
            ]);

        $edicaoAtualizada = DB::transaction(
            function () use (
                $edicao,
                $dados,
            ): Edicao {
                $edicaoBloqueada =
                    $this->bloquearEdicao(
                        $edicao,
                    );

                $edicaoBloqueada->fill(
                    $dados,
                );

                if ($edicaoBloqueada->isDirty()) {
                    $edicaoBloqueada->saveOrFail();
                }

                return $edicaoBloqueada;
            },
            self::TENTATIVAS_TRANSACAO,
        );

        if ($pedido->expectsJson()) {
            return response()->json([
                'mensagem' => 'Edição atualizada com sucesso.',

                'edicao' => $this->serializarEdicao(
                    $edicaoAtualizada,
                ),
            ]);
        }

        return to_route(
            'edicoes.indice',
        )->with(
            'sucesso',
            'Edição atualizada com sucesso.',
        );
    }

    /**
     * Elimina logicamente uma edição sem MetalThursdays associadas.
     *
     * A autorização é verificada antes de abrir a transação. A edição é
     * bloqueada antes da verificação das associações. O serviço de
     * persistência das MetalThursdays bloqueia a mesma edição durante a
     * criação, impedindo uma associação concorrente entre a verificação e a
     * eliminação.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Edicao  $edicao  Edição eliminada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     */
    public function eliminar(
        Request $pedido,
        Edicao $edicao,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'delete',
            $edicao,
        );

        $foiEliminada = DB::transaction(
            function () use (
                $edicao,
            ): bool {
                $edicaoBloqueada =
                    $this->bloquearEdicao(
                        $edicao,
                    );

                $possuiMetalThursdays =
                    MetalThursday::query()
                        ->where(
                            'edicao_id',
                            $edicaoBloqueada->getKey(),
                        )
                        ->exists();

                if ($possuiMetalThursdays) {
                    return false;
                }

                $edicaoBloqueada->deleteOrFail();

                return true;
            },
            self::TENTATIVAS_TRANSACAO,
        );

        if (! $foiEliminada) {
            if ($pedido->expectsJson()) {
                return response()->json(
                    [
                        'mensagem' => self::MENSAGEM_EDICAO_COM_METAL_THURSDAYS,
                    ],
                    Response::HTTP_CONFLICT,
                );
            }

            return back()->withErrors([
                'edicao' => self::MENSAGEM_EDICAO_COM_METAL_THURSDAYS,
            ]);
        }

        if ($pedido->expectsJson()) {
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT,
            );
        }

        return to_route(
            'edicoes.indice',
        )->with(
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
     */
    public function guardarMusicasFavoritas(
        GuardarMusicasFavoritasEdicaoRequest $pedido,
        Edicao $edicao,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $edicao,
        );

        $registador =
            $this->obterUtilizadorAutenticado();

        $this
            ->servicoMusicasFavoritas
            ->sincronizar(
                edicao: $edicao,
                musicasFavoritas: $pedido->obterMusicasFavoritas(),
                registador: $registador,
            );

        if ($pedido->expectsJson()) {
            return response()->json([
                'mensagem' => 'Músicas favoritas guardadas com sucesso.',
            ]);
        }

        return back()->with(
            'sucesso',
            'Músicas favoritas guardadas com sucesso.',
        );
    }

    /**
     * Atualiza a ligação da compilação sob bloqueio transacional.
     *
     * A autorização é verificada antes de abrir a transação. A edição é
     * novamente obtida e bloqueada imediatamente antes da atualização.
     *
     * A edição só é persistida quando a ligação normalizada é diferente da
     * ligação existente, evitando atualizar os dados de auditoria sem uma
     * alteração real.
     *
     * @param  AtualizarLigacaoCompilacaoEdicaoRequest  $pedido  Pedido validado.
     * @param  Edicao  $edicao  Edição atualizada.
     * @return JsonResponse|RedirectResponse Resposta da operação.
     *
     * @since 1.0.0
     */
    public function atualizarLigacaoCompilacao(
        AtualizarLigacaoCompilacaoEdicaoRequest $pedido,
        Edicao $edicao,
    ): JsonResponse|RedirectResponse {
        $this->authorize(
            'update',
            $edicao,
        );

        $ligacaoCompilacao =
            $pedido->obterLigacaoCompilacao();

        $edicaoAtualizada = DB::transaction(
            function () use (
                $edicao,
                $ligacaoCompilacao,
            ): Edicao {
                $edicaoBloqueada =
                    $this->bloquearEdicao(
                        $edicao,
                    );

                $edicaoBloqueada->fill([
                    'ligacao_compilacao' => $ligacaoCompilacao,
                ]);

                if (
                    $edicaoBloqueada->isDirty(
                        'ligacao_compilacao',
                    )
                ) {
                    $edicaoBloqueada->saveOrFail();
                }

                return $edicaoBloqueada;
            },
            self::TENTATIVAS_TRANSACAO,
        );

        if ($pedido->expectsJson()) {
            return response()->json([
                'mensagem' => 'Ligação da compilação atualizada com sucesso.',

                'ligacao_compilacao' => $edicaoAtualizada->ligacao_compilacao,
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
     * @since 2.0.0
     */
    private function obterDadosFormulario(
        ?Edicao $edicao = null,
    ): array {
        $emEdicao =
            $edicao instanceof Edicao;

        return [
            'emEdicao' => $emEdicao,

            'enderecoFormulario' => $emEdicao
                ? route(
                    'edicoes.atualizar',
                    $edicao,
                )
                : route(
                    'edicoes.guardar',
                ),

            'nomeEdicao' => $edicao instanceof Edicao
                ? $this->obterNomeEdicao(
                    $edicao,
                )
                : '',

            'dataInicioEdicao' => $this->formatarDataFormulario(
                $edicao?->data_inicio,
            ),

            'dataFimEdicao' => $this->formatarDataFormulario(
                $edicao?->data_fim,
            ),

            'textoBotaoSubmissao' => $emEdicao
                ? 'Guardar alterações'
                : 'Criar edição',
        ];
    }

    /**
     * Bloqueia uma edição existente dentro da transação atual.
     *
     * @param  Edicao  $edicao  Edição original.
     * @return Edicao Edição novamente obtida e bloqueada.
     *
     * @since 2.0.0
     */
    private function bloquearEdicao(
        Edicao $edicao,
    ): Edicao {
        return Edicao::query()
            ->whereKey(
                $edicao->getKey(),
            )
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Formata uma data para utilização num campo HTML do tipo `date`.
     *
     * @param  mixed  $data  Data recebida.
     * @return string Data no formato `Y-m-d` ou texto vazio.
     *
     * @since 2.0.0
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
     * Obtém o utilizador autenticado através do guard da aplicação.
     *
     * @return Utilizador Utilizador autenticado e persistido.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     */
    private function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador =
            Auth::guard(
                'sessao',
            )->user();

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

        return $utilizador;
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
     * @throws LogicException Quando a edição contém dados persistidos
     *                        inválidos.
     *
     * @since 2.0.0
     */
    private function serializarEdicao(
        Edicao $edicao,
    ): array {
        $identificador =
            $edicao->getKey();

        if (
            ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new LogicException(
                'A edição não possui um identificador persistido válido.',
            );
        }

        $dataInicio =
            $edicao->data_inicio;

        if (! $dataInicio instanceof CarbonInterface) {
            throw new LogicException(
                'A edição não possui uma data de início válida.',
            );
        }

        $dataFim =
            $edicao->data_fim;

        if (
            $dataFim !== null
            && ! $dataFim instanceof CarbonInterface
        ) {
            throw new LogicException(
                'A edição não possui uma data de fim válida.',
            );
        }

        $ligacaoCompilacao =
            $edicao->ligacao_compilacao;

        if (
            $ligacaoCompilacao !== null
            && ! is_string($ligacaoCompilacao)
        ) {
            throw new LogicException(
                'A edição não possui uma ligação de compilação válida.',
            );
        }

        return [
            'id' => (int) $identificador,

            'nome' => $this->obterNomeEdicao(
                $edicao,
            ),

            'data_inicio' => $dataInicio->format(
                'Y-m-d',
            ),

            'data_fim' => $dataFim?->format(
                'Y-m-d',
            ),

            'ligacao_compilacao' => $ligacaoCompilacao,

            'texto_apresentacao' => $this->obterTextoApresentacao(
                $edicao,
            ),
        ];
    }

    /**
     * Obtém o nome persistido de uma edição.
     *
     * @param  Edicao  $edicao  Edição consultada.
     * @return string Nome normalizado.
     *
     * @throws LogicException Quando o nome persistido não é válido.
     *
     * @since 2.0.0
     */
    private function obterNomeEdicao(
        Edicao $edicao,
    ): string {
        $nome =
            $edicao->nome;

        if (! is_string($nome)) {
            throw new LogicException(
                'A edição não possui um nome válido.',
            );
        }

        $nomeNormalizado =
            trim(
                $nome,
            );

        if ($nomeNormalizado === '') {
            throw new LogicException(
                'A edição não possui um nome válido.',
            );
        }

        return $nomeNormalizado;
    }

    /**
     * Obtém o texto apresentado para uma edição.
     *
     * @param  Edicao  $edicao  Edição apresentada.
     * @return string Texto da edição.
     *
     * @throws LogicException Quando as datas persistidas não são válidas.
     *
     * @since 2.0.0
     */
    private function obterTextoApresentacao(
        Edicao $edicao,
    ): string {
        $dataInicio =
            $edicao->data_inicio;

        if (! $dataInicio instanceof CarbonInterface) {
            throw new LogicException(
                'A edição não possui uma data de início válida.',
            );
        }

        $nome =
            $this->obterNomeEdicao(
                $edicao,
            );

        $inicio =
            $dataInicio->format(
                'd/m/Y',
            );

        $dataFim =
            $edicao->data_fim;

        if ($dataFim === null) {
            return sprintf(
                '%s — %s',
                $nome,
                $inicio,
            );
        }

        if (! $dataFim instanceof CarbonInterface) {
            throw new LogicException(
                'A edição não possui uma data de fim válida.',
            );
        }

        return sprintf(
            '%s — %s a %s',
            $nome,
            $inicio,
            $dataFim->format(
                'd/m/Y',
            ),
        );
    }
}
