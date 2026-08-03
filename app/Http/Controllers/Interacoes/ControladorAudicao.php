<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Http\Controllers\Controller;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Audicao;
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
 * Gere as audições associadas aos MetalThursdays e às respetivas secções.
 *
 * A alternância é executada numa transação que bloqueia a entidade audível,
 * garantindo que pedidos concorrentes para a mesma entidade são processados
 * sequencialmente.
 *
 * @since 1.0.0
 *
 * @version 4.1.0
 */
final class ControladorAudicao extends Controller
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
     * Ação utilizada na notificação de uma nova audição.
     *
     * @var string
     *
     * @since 4.0.0
     *
     * @version 1.0.0
     */
    private const ACAO_OUVIU =
        'ouviu';

    /**
     * Conteúdo apresentado quando ainda não existem audições.
     *
     * @var string
     *
     * @since 4.0.0
     *
     * @version 1.0.0
     */
    private const MENSAGEM_SEM_AUDICOES =
        'Ninguém marcou como ouvido.';

    /**
     * Cria o controlador.
     *
     * @param  NotificadorInteracoes  $notificadorInteracoes  Serviço
     *                                                        responsável
     *                                                        pelas
     *                                                        notificações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        private readonly NotificadorInteracoes $notificadorInteracoes,
    ) {}

    /**
     * Adiciona ou remove a audição do utilizador autenticado.
     *
     * O bloqueio da entidade audível serializa todas as alternâncias
     * associadas ao mesmo registo. A restrição única da base de dados
     * continua a garantir, no máximo, uma audição por utilizador e entidade.
     *
     * A transação limita-se à alteração da audição. A contagem e o indicador
     * são construídos depois da libertação do bloqueio através de uma única
     * consulta.
     *
     * A notificação é enviada depois da conclusão da transação e apenas
     * quando a entidade foi marcada como ouvida.
     *
     * @param  string  $tipoAudivel  Tipo público da entidade ouvida.
     * @param  int  $identificadorAudivel  Identificador da entidade.
     * @return JsonResponse Estado atualizado da audição.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     * @throws NotFoundHttpException Quando o tipo ou identificador não são
     *                               válidos.
     *
     * @since 1.0.0
     *
     * @version 4.1.0
     */
    public function alternar(
        string $tipoAudivel,
        int $identificadorAudivel,
    ): JsonResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        $identificadorUtilizador =
            (int) $utilizador->getKey();

        $audivel =
            $this->resolverAudivel(
                $tipoAudivel,
                $identificadorAudivel,
            );

        /**
         * @var array{
         *     audivel: MetalThursday|SeccaoMetalThursday,
         *     marcado_como_ouvido: bool
         * } $resultado
         */
        $resultado =
            DB::transaction(
                function () use (
                    $audivel,
                    $identificadorUtilizador,
                ): array {
                    $audivelBloqueado =
                        $this->bloquearAudivel(
                            $audivel,
                        );

                    $audicao =
                        $audivelBloqueado
                            ->audicoes()
                            ->where(
                                'utilizador_id',
                                $identificadorUtilizador,
                            )
                            ->first();

                    if ($audicao instanceof Audicao) {
                        $audicao->deleteOrFail();

                        $marcadoComoOuvido =
                            false;
                    } else {
                        $audivelBloqueado
                            ->audicoes()
                            ->create([
                                'utilizador_id' => $identificadorUtilizador,
                            ]);

                        $marcadoComoOuvido =
                            true;
                    }

                    return [
                        'audivel' => $audivelBloqueado,

                        'marcado_como_ouvido' => $marcadoComoOuvido,
                    ];
                },
                self::TENTATIVAS_TRANSACAO,
            );

        $audivelAtualizado =
            $resultado['audivel'];

        $marcadoComoOuvido =
            $resultado['marcado_como_ouvido'];

        if ($marcadoComoOuvido) {
            $this
                ->notificadorInteracoes
                ->notificarOutrosUtilizadores(
                    sujeito: $audivelAtualizado,
                    causador: $utilizador,
                    acao: self::ACAO_OUVIU,
                );
        }

        $dadosIndicador =
            $this->obterDadosIndicador(
                $audivelAtualizado,
            );

        return response()->json([
            'marcado_como_ouvido' => $marcadoComoOuvido,

            'numero_audicoes' => $dadosIndicador['numero_audicoes'],

            'conteudo_indicador_html' => $dadosIndicador['conteudo_html'],
        ]);
    }

    /**
     * Resolve a entidade que recebe a audição.
     *
     * Apenas os tipos definidos na enumeração das entidades de interação podem
     * ser resolvidos.
     *
     * @param  string  $tipo  Slug recebido através da rota.
     * @param  int  $identificador  Identificador recebido através da rota.
     * @return MetalThursday|SeccaoMetalThursday Entidade encontrada.
     *
     * @throws NotFoundHttpException Quando o tipo ou identificador não são
     *                               válidos.
     *
     * @since 2.0.0
     *
     * @version 3.0.0
     */
    private function resolverAudivel(
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
     * Bloqueia a entidade durante a alteração da audição.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $audivel  Entidade original.
     * @return MetalThursday|SeccaoMetalThursday Entidade bloqueada.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function bloquearAudivel(
        MetalThursday|SeccaoMetalThursday $audivel,
    ): MetalThursday|SeccaoMetalThursday {
        if ($audivel instanceof MetalThursday) {
            return MetalThursday::query()
                ->whereKey(
                    $audivel->getKey(),
                )
                ->lockForUpdate()
                ->firstOrFail();
        }

        return SeccaoMetalThursday::query()
            ->whereKey(
                $audivel->getKey(),
            )
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Obtém a contagem e o indicador das audições.
     *
     * Uma única consulta obtém os identificadores e nomes necessários. O
     * número total corresponde ao número de registos devolvidos por essa mesma
     * consulta.
     *
     * Os nomes são validados, ordenados e escapados antes de serem incluídos
     * no fragmento HTML devolvido ao cliente. Contas diferentes com o mesmo
     * nome permanecem representadas individualmente.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $audivel  Entidade
     *                                                      consultada.
     * @return array{
     *     numero_audicoes: int,
     *     conteudo_html: string
     * } Dados preparados.
     *
     * @throws LogicException Quando uma audição possui dados persistidos
     *                        inválidos.
     *
     * @since 4.1.0
     *
     * @version 1.0.0
     */
    private function obterDadosIndicador(
        MetalThursday|SeccaoMetalThursday $audivel,
    ): array {
        $modeloAudicao =
            new Audicao;

        $tabelaAudicoes =
            $modeloAudicao->getTable();

        $audicoes =
            DB::table(
                $tabelaAudicoes,
            )
                ->join(
                    'utilizadores',
                    'utilizadores.id',
                    '=',
                    $tabelaAudicoes.'.utilizador_id',
                )
                ->where(
                    $tabelaAudicoes.'.audivel_id',
                    $audivel->getKey(),
                )
                ->where(
                    $tabelaAudicoes.'.tipo_audivel',
                    $audivel->getMorphClass(),
                )
                ->get([
                    'utilizadores.id AS utilizador_id',

                    'utilizadores.nome',
                ]);

        if ($audicoes->isEmpty()) {
            return [
                'numero_audicoes' => 0,

                'conteudo_html' => self::MENSAGEM_SEM_AUDICOES,
            ];
        }

        /**
         * @var list<array{
         *     identificador: int,
         *     nome: string
         * }> $utilizadores
         */
        $utilizadores = [];

        foreach ($audicoes as $audicao) {
            $identificadorUtilizador =
                $audicao->utilizador_id
                ?? null;

            if (
                ! is_numeric($identificadorUtilizador)
                || (int) $identificadorUtilizador < 1
            ) {
                throw new LogicException(
                    'Foi encontrada uma audição associada a um utilizador inválido.',
                );
            }

            $nome =
                $audicao->nome
                ?? null;

            if (! is_string($nome)) {
                throw new LogicException(
                    'Foi encontrada uma audição sem um utilizador válido.',
                );
            }

            $nomeNormalizado =
                trim(
                    $nome,
                );

            if ($nomeNormalizado === '') {
                throw new LogicException(
                    'Foi encontrada uma audição associada a um utilizador sem nome válido.',
                );
            }

            $utilizadores[] = [
                'identificador' => (int) $identificadorUtilizador,

                'nome' => $nomeNormalizado,
            ];
        }

        usort(
            $utilizadores,
            static function (
                array $primeiroUtilizador,
                array $segundoUtilizador,
            ): int {
                $comparacaoNome =
                    strnatcasecmp(
                        $primeiroUtilizador['nome'],
                        $segundoUtilizador['nome'],
                    );

                if ($comparacaoNome !== 0) {
                    return $comparacaoNome;
                }

                return $primeiroUtilizador['identificador']
                    <=> $segundoUtilizador['identificador'];
            },
        );

        $nomesEscapados =
            array_map(
                static fn (
                    array $utilizador,
                ): string => e(
                    $utilizador['nome'],
                ),
                $utilizadores,
            );

        return [
            'numero_audicoes' => count(
                $utilizadores,
            ),

            'conteudo_html' => implode(
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
                'É necessário iniciar sessão para registar uma audição.',
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
