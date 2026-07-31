<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Http\Controllers\Controller;
use App\Http\Requests\Interacoes\GuardarAvaliacaoRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Avaliacao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Servicos\Notificacoes\NotificadorInteracoes;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Gere as avaliações atribuídas a MetalThursdays e respetivas secções.
 *
 * A criação e a atualização das avaliações são executadas dentro de uma
 * transação que bloqueia a entidade avaliada, garantindo uma única avaliação
 * por utilizador e entidade mesmo perante pedidos concorrentes.
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
final class ControladorAvaliacao extends Controller
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @var int
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO =
        3;

    /**
     * Ação utilizada nas notificações de avaliações.
     *
     * @var string
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private const ACAO_AVALIOU =
        'avaliou';

    /**
     * Conteúdo apresentado quando ainda não existem avaliações.
     *
     * @var string
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private const MENSAGEM_SEM_AVALIACOES =
        'Ainda sem avaliações.';

    /**
     * Cria o controlador.
     *
     * @param  NotificadorInteracoes  $notificadorInteracoes  Serviço responsável
     *                                                        pelas notificações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        private readonly NotificadorInteracoes $notificadorInteracoes,
    ) {}

    /**
     * Cria ou atualiza a avaliação do utilizador autenticado.
     *
     * A entidade avaliada é bloqueada durante a transação. Uma avaliação
     * existente apenas é persistida quando a pontuação foi efetivamente
     * alterada.
     *
     * A notificação é enviada depois da conclusão da transação e apenas quando
     * ocorreu uma criação ou alteração efetiva.
     *
     * A média, a contagem e o indicador são construídos depois da transação
     * através de uma única consulta.
     *
     * @param  GuardarAvaliacaoRequest  $pedido  Pedido validado.
     * @param  string  $tipoAvaliavel  Tipo da entidade avaliada.
     * @param  int  $identificadorAvaliavel  Identificador da entidade.
     * @return JsonResponse Estado atualizado das avaliações.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     * @throws NotFoundHttpException Quando o tipo ou o identificador não são
     *                               válidos.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    public function guardar(
        GuardarAvaliacaoRequest $pedido,
        string $tipoAvaliavel,
        int $identificadorAvaliavel,
    ): JsonResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        $identificadorUtilizador =
            (int) $utilizador->getKey();

        $pontuacao =
            $pedido->obterPontuacao();

        $avaliavel =
            $this->resolverAvaliavel(
                $tipoAvaliavel,
                $identificadorAvaliavel,
            );

        /**
         * @var array{
         *     avaliavel: MetalThursday|SeccaoMetalThursday,
         *     avaliacao_alterada: bool
         * } $resultado
         */
        $resultado =
            DB::transaction(
                function () use (
                    $avaliavel,
                    $identificadorUtilizador,
                    $pontuacao,
                ): array {
                    $avaliavelBloqueado =
                        $this->bloquearAvaliavel(
                            $avaliavel,
                        );

                    $avaliacao =
                        $avaliavelBloqueado
                            ->avaliacoes()
                            ->where(
                                'utilizador_id',
                                $identificadorUtilizador,
                            )
                            ->first();

                    $avaliacaoAlterada =
                        false;

                    if ($avaliacao instanceof Avaliacao) {
                        $pontuacaoAtual =
                            round(
                                $avaliacao->pontuacao,
                                1,
                            );

                        if ($pontuacaoAtual !== $pontuacao) {
                            $avaliacao->updateOrFail([
                                'pontuacao' => $pontuacao,
                            ]);

                            $avaliacaoAlterada =
                                true;
                        }
                    } else {
                        $avaliavelBloqueado
                            ->avaliacoes()
                            ->create([
                                'utilizador_id' => $identificadorUtilizador,

                                'pontuacao' => $pontuacao,
                            ]);

                        $avaliacaoAlterada =
                            true;
                    }

                    return [
                        'avaliavel' => $avaliavelBloqueado,

                        'avaliacao_alterada' => $avaliacaoAlterada,
                    ];
                },
                self::TENTATIVAS_TRANSACAO,
            );

        $avaliavelAtualizado =
            $resultado['avaliavel'];

        if ($resultado['avaliacao_alterada']) {
            $this
                ->notificadorInteracoes
                ->notificarOutrosUtilizadores(
                    sujeito: $avaliavelAtualizado,
                    causador: $utilizador,
                    acao: self::ACAO_AVALIOU,
                );
        }

        $dadosIndicador =
            $this->obterDadosIndicador(
                $avaliavelAtualizado,
            );

        return response()->json([
            'media_avaliacoes' => round(
                $dadosIndicador['media_avaliacoes'],
                1,
            ),

            'numero_avaliacoes' => $dadosIndicador['numero_avaliacoes'],

            'pontuacao_utilizador' => $pontuacao,

            'conteudo_indicador_html' => $dadosIndicador['conteudo_html'],
        ]);
    }

    /**
     * Resolve a entidade que recebe a avaliação.
     *
     * Apenas os tipos definidos na enumeração das entidades de interação podem
     * ser resolvidos.
     *
     * @param  string  $tipo  Slug recebido através da rota.
     * @param  int  $identificador  Identificador recebido através da rota.
     * @return MetalThursday|SeccaoMetalThursday Entidade encontrada.
     *
     * @throws NotFoundHttpException Quando o tipo ou o identificador não são
     *                               válidos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function resolverAvaliavel(
        string $tipo,
        int $identificador,
    ): MetalThursday|SeccaoMetalThursday {
        if ($identificador < 1) {
            throw new NotFoundHttpException;
        }

        $tipoEntidade =
            TipoEntidadeInteracao::deSlug(
                $tipo,
            );

        if ($tipoEntidade === null) {
            throw new NotFoundHttpException;
        }

        $classeModelo =
            $tipoEntidade->obterClasseModelo();

        return $classeModelo::query()
            ->findOrFail(
                $identificador,
            );
    }

    /**
     * Bloqueia a entidade durante a atualização da avaliação.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $avaliavel  Entidade original.
     * @return MetalThursday|SeccaoMetalThursday Entidade bloqueada.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function bloquearAvaliavel(
        MetalThursday|SeccaoMetalThursday $avaliavel,
    ): MetalThursday|SeccaoMetalThursday {
        if ($avaliavel instanceof MetalThursday) {
            return MetalThursday::query()
                ->whereKey(
                    $avaliavel->getKey(),
                )
                ->lockForUpdate()
                ->firstOrFail();
        }

        return SeccaoMetalThursday::query()
            ->whereKey(
                $avaliavel->getKey(),
            )
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Obtém a média, a contagem e o indicador das avaliações.
     *
     * Uma única consulta obtém as pontuações e os nomes necessários. A média e
     * o número total são calculados em memória a partir desses mesmos registos,
     * evitando uma consulta agregada separada.
     *
     * Os nomes são validados e escapados antes de serem incluídos no fragmento
     * HTML devolvido ao cliente.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $avaliavel  Entidade
     *                                                        consultada.
     * @return array{
     *     media_avaliacoes: float,
     *     numero_avaliacoes: int,
     *     conteudo_html: string
     * } Dados preparados.
     *
     * @throws LogicException Quando uma avaliação possui dados persistidos
     *                        inválidos.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private function obterDadosIndicador(
        MetalThursday|SeccaoMetalThursday $avaliavel,
    ): array {
        $modeloAvaliacao =
            new Avaliacao;

        $tabelaAvaliacoes =
            $modeloAvaliacao->getTable();

        $avaliacoes =
            DB::table(
                $tabelaAvaliacoes,
            )
                ->join(
                    'utilizadores',
                    'utilizadores.id',
                    '=',
                    $tabelaAvaliacoes.'.utilizador_id',
                )
                ->where(
                    $tabelaAvaliacoes.'.avaliavel_id',
                    $avaliavel->getKey(),
                )
                ->where(
                    $tabelaAvaliacoes.'.tipo_avaliavel',
                    $avaliavel->getMorphClass(),
                )
                ->orderByDesc(
                    $tabelaAvaliacoes.'.pontuacao',
                )
                ->orderBy(
                    $tabelaAvaliacoes.'.id',
                )
                ->get([
                    $tabelaAvaliacoes.'.pontuacao',

                    'utilizadores.nome',
                ]);

        if ($avaliacoes->isEmpty()) {
            return [
                'media_avaliacoes' => 0.0,

                'numero_avaliacoes' => 0,

                'conteudo_html' => self::MENSAGEM_SEM_AVALIACOES,
            ];
        }

        $somaPontuacoes =
            0.0;

        $linhas = [];

        foreach ($avaliacoes as $avaliacao) {
            $pontuacao =
                $avaliacao->pontuacao
                ?? null;

            if (
                ! is_int($pontuacao)
                && ! is_float($pontuacao)
                && ! is_string($pontuacao)
            ) {
                throw new LogicException(
                    'Foi encontrada uma avaliação com uma pontuação persistida inválida.',
                );
            }

            if (! is_numeric($pontuacao)) {
                throw new LogicException(
                    'Foi encontrada uma avaliação com uma pontuação persistida inválida.',
                );
            }

            $pontuacaoNormalizada =
                (float) $pontuacao;

            if (! is_finite($pontuacaoNormalizada)) {
                throw new LogicException(
                    'Foi encontrada uma avaliação com uma pontuação persistida inválida.',
                );
            }

            $nome =
                $avaliacao->nome
                ?? null;

            if (! is_string($nome)) {
                throw new LogicException(
                    'Foi encontrada uma avaliação sem um utilizador válido.',
                );
            }

            $nomeNormalizado =
                trim(
                    $nome,
                );

            if ($nomeNormalizado === '') {
                throw new LogicException(
                    'Foi encontrada uma avaliação associada a um utilizador sem nome válido.',
                );
            }

            $somaPontuacoes +=
                $pontuacaoNormalizada;

            $linhas[] =
                sprintf(
                    '%s: %s',
                    e(
                        $nomeNormalizado,
                    ),
                    number_format(
                        $pontuacaoNormalizada,
                        1,
                        ',',
                        '',
                    ),
                );
        }

        $numeroAvaliacoes =
            count(
                $linhas,
            );

        return [
            'media_avaliacoes' => $somaPontuacoes / $numeroAvaliacoes,

            'numero_avaliacoes' => $numeroAvaliacoes,

            'conteudo_html' => implode(
                '<br>',
                $linhas,
            ),
        ];
    }

    /**
     * Obtém o utilizador autenticado através do guard da aplicação.
     *
     * @return Utilizador Utilizador autenticado e persistido.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador =
            Auth::guard(
                'sessao',
            )->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para avaliar.',
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
}
