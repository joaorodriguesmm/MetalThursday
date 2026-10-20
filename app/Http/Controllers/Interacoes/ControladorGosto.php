<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interacoes;

use App\Http\Controllers\Controller;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\Interacoes\Gosto;
use App\Servicos\Interacoes\ServicoAcessoInteracoes;
use App\Servicos\Interacoes\ServicoDisponibilidadeInteracoes;
use App\Servicos\Notificacoes\NotificadorInteracoes;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gere os gostos associados aos comentários.
 *
 * A alternância é executada numa transação e bloqueia o comentário, garantindo
 * que pedidos concorrentes para o mesmo comentário são processados
 * sequencialmente.
 *
 * @since 1.0.0
 */
final class ControladorGosto extends Controller
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO =
        3;

    /**
     * Ação utilizada na notificação de um novo gosto.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const ACAO_GOSTOU =
        'gostou';

    /**
     * Mensagem apresentada quando o gosto é adicionado.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_GOSTO_ADICIONADO =
        'Gosto adicionado.';

    /**
     * Mensagem apresentada quando o gosto é removido.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_GOSTO_REMOVIDO =
        'Gosto removido.';

    /**
     * Texto apresentado quando o comentário ainda não possui gostos.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_SEM_GOSTOS =
        'Ainda não há gostos.';

    /**
     * Cria o controlador.
     *
     * @param  NotificadorInteracoes  $notificadorInteracoes  Serviço de
     *                                                        notificações.
     * @param  ServicoDisponibilidadeInteracoes  $servicoDisponibilidadeInteracoes  Serviço responsável
     *                                                                              pela disponibilidade
     *                                                                              temporal.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly NotificadorInteracoes $notificadorInteracoes,
        private readonly ServicoAcessoInteracoes $servicoAcessoInteracoes,
        private readonly ServicoDisponibilidadeInteracoes $servicoDisponibilidadeInteracoes,
    ) {}

    /**
     * Define o estado do gosto do utilizador autenticado ou alterna-o
     * quando o pedido não inclui estado_desejado.
     *
     * O bloqueio do comentário serializa as operações e a restrição
     * única impede gostos duplicados por utilizador.
     *
     * A alteração e o enfileiramento de notificações pertencem à
     * mesma transação. Só a criação efetiva de um gosto notifica.
     * A contagem e o indicador são obtidos após libertar o bloqueio.
     *
     * @param  Request  $pedido  Pedido HTTP com estado_desejado opcional.
     * @param  Comentario  $comentario  Comentário alterado.
     * @return JsonResponse Estado atualizado do gosto.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     */
    public function alternar(
        Request $pedido,
        Comentario $comentario,
    ): JsonResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        $identificadorUtilizador =
            $utilizador->id;

        $dadosValidados = $pedido->validate([
            'estado_desejado' => ['sometimes', 'required', 'boolean'],
        ]);

        $estadoDesejado = array_key_exists('estado_desejado', $dadosValidados)
            ? (bool) $dadosValidados['estado_desejado']
            : null;

        /**
         * @var array{
         *     comentario: Comentario,
         *     adicionado: bool
         * } $resultado
         */
        $resultado =
            DB::transaction(
                function () use (
                    $comentario,
                    $identificadorUtilizador,
                    $utilizador,
                    $estadoDesejado,
                ): array {
                    $comentarioBloqueado =
                        Comentario::query()
                            ->whereKey(
                                $comentario->getKey(),
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    if (
                        $comentarioBloqueado
                            ->temConteudoEliminado()
                    ) {
                        abort(
                            Response::HTTP_GONE,
                        );
                    }

                    $this->servicoDisponibilidadeInteracoes
                        ->obterMetalThursdayPublicadaComBloqueio(
                            $comentarioBloqueado,
                        );

                    $this->servicoAcessoInteracoes
                        ->garantirAcessoAtivoComBloqueio(
                            $identificadorUtilizador,
                        );

                    $gosto =
                        $comentarioBloqueado
                            ->gostos()
                            ->where(
                                'utilizador_id',
                                $identificadorUtilizador,
                            )
                            ->first();

                    $adicionado =
                        $estadoDesejado
                        ?? ! ($gosto instanceof Gosto);

                    $gostoCriado = false;

                    if ($adicionado && ! ($gosto instanceof Gosto)) {
                        $comentarioBloqueado
                            ->gostos()
                            ->create([
                                'utilizador_id' => $identificadorUtilizador,
                            ]);

                        $gostoCriado = true;
                    } elseif (! $adicionado && $gosto instanceof Gosto) {
                        $gosto->deleteOrFail();
                    }

                    if ($gostoCriado) {
                        $this->notificadorInteracoes
                            ->notificarOutrosUtilizadores(
                                sujeito: $comentarioBloqueado,
                                causador: $utilizador,
                                acao: self::ACAO_GOSTOU,
                                enfileirarNaTransacao: true,
                            );
                    }

                    return [
                        'comentario' => $comentarioBloqueado,

                        'adicionado' => $adicionado,
                    ];
                },
                self::TENTATIVAS_TRANSACAO,
            );

        $comentarioAtualizado =
            $resultado['comentario'];

        $adicionado =
            $resultado['adicionado'];

        $dadosIndicador =
            $this->obterDadosIndicador(
                $comentarioAtualizado,
            );

        return response()->json([
            'adicionado' => $adicionado,

            'numero_gostos' => $dadosIndicador['numero_gostos'],

            'mensagem' => $adicionado
                ? self::MENSAGEM_GOSTO_ADICIONADO
                : self::MENSAGEM_GOSTO_REMOVIDO,

            'conteudo_indicador_html' => $dadosIndicador['conteudo_html'],
        ]);
    }

    /**
     * Obtém os utilizadores que gostaram do comentário.
     *
     * Um comentário cujo conteúdo foi eliminado mantém-se apenas como elemento
     * estrutural da conversa e deixa de disponibilizar as interações associadas
     * ao conteúdo original.
     *
     * @param  Comentario  $comentario  Comentário consultado.
     * @return JsonResponse Lista de utilizadores.
     *
     * @since 1.0.0
     */
    public function listarUtilizadores(
        Comentario $comentario,
    ): JsonResponse {
        if (
            $comentario
                ->temConteudoEliminado()
        ) {
            abort(
                Response::HTTP_GONE,
            );
        }

        $this->servicoDisponibilidadeInteracoes
            ->obterMetalThursdayPublicada(
                $comentario,
            );

        $dadosIndicador =
            $this->obterDadosIndicador(
                $comentario,
            );

        return response()->json([
            'nomes' => $dadosIndicador['nomes'],

            'conteudo_indicador_html' => $dadosIndicador['conteudo_html'],
        ]);
    }

    /**
     * Obtém os dados utilizados no indicador dos gostos.
     *
     * A mesma consulta fornece os nomes apresentados e o número total de
     * gostos. Os nomes são escapados antes de serem incluídos no fragmento
     * HTML devolvido ao cliente.
     *
     * @param  Comentario  $comentario  Comentário consultado.
     * @return array{
     *     nomes: list<string>,
     *     numero_gostos: int,
     *     conteudo_html: string
     * } Dados preparados.
     *
     * @throws LogicException Quando a consulta devolve um nome persistido com
     *                        um formato inválido.
     *
     * @since 2.0.0
     */
    private function obterDadosIndicador(
        Comentario $comentario,
    ): array {
        $nomesObtidos =
            $comentario
                ->gostos()
                ->join(
                    'utilizadores',
                    'utilizadores.id',
                    '=',
                    'gostos.utilizador_id',
                )
                ->orderBy(
                    'utilizadores.nome',
                )
                ->orderBy(
                    'utilizadores.id',
                )
                ->pluck(
                    'utilizadores.nome',
                )
                ->all();

        $nomes = [];

        foreach ($nomesObtidos as $nome) {
            if (! is_string($nome)) {
                throw new LogicException(
                    'Foi encontrado um utilizador com um nome persistido inválido.',
                );
            }

            $nomeNormalizado =
                trim(
                    $nome,
                );

            if ($nomeNormalizado === '') {
                throw new LogicException(
                    'Foi encontrado um utilizador sem um nome persistido válido.',
                );
            }

            $nomes[] =
                $nomeNormalizado;
        }

        $nomesEscapados =
            array_map(
                static fn (
                    string $nome,
                ): string => e(
                    $nome,
                ),
                $nomes,
            );

        return [
            'nomes' => $nomes,

            'numero_gostos' => count(
                $nomes,
            ),

            'conteudo_html' => $nomesEscapados === []
                ? self::MENSAGEM_SEM_GOSTOS
                : implode(
                    '<br>',
                    $nomesEscapados,
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
     */
    private function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador =
            Auth::guard(
                'sessao',
            )->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para adicionar um gosto.',
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
