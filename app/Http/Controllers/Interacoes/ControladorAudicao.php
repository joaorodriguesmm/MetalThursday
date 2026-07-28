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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Gere as audições associadas aos MetalThursdays e às respetivas secções.
 *
 * @since 1.0.0
 *
 * @version 3.2.0
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
    private const TENTATIVAS_TRANSACAO = 3;

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
     * @param  Request  $pedido  Pedido HTTP.
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
     * @version 3.2.0
     */
    public function alternar(
        Request $pedido,
        string $tipoAudivel,
        int $identificadorAudivel,
    ): JsonResponse {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $pedido,
            );

        $audivel =
            $this->resolverAudivel(
                $tipoAudivel,
                $identificadorAudivel,
            );

        /**
         * @var array{
         *     audivel: MetalThursday|SeccaoMetalThursday,
         *     marcado_como_ouvido: bool,
         *     numero_audicoes: int
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

                        'numero_audicoes' => $audivelBloqueado
                            ->audicoes()
                            ->count(),
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
                    $audivelAtualizado,
                    'ouviu',
                );
        }

        return response()->json([
            'marcado_como_ouvido' => $marcadoComoOuvido,

            'numero_audicoes' => $resultado['numero_audicoes'],

            'conteudo_indicador_html' => $this->obterConteudoIndicador(
                $audivelAtualizado,
            ),
        ]);
    }

    /**
     * Resolve a entidade que recebe a audição.
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
     * @version 1.0.0
     */
    private function bloquearAudivel(
        MetalThursday|SeccaoMetalThursday $audivel,
    ): MetalThursday|SeccaoMetalThursday {
        $classeModelo =
            $audivel::class;

        /** @var MetalThursday|SeccaoMetalThursday $audivelBloqueado */
        $audivelBloqueado =
            $classeModelo::query()
                ->whereKey(
                    $audivel->getKey(),
                )
                ->lockForUpdate()
                ->firstOrFail();

        return $audivelBloqueado;
    }

    /**
     * Obtém o conteúdo apresentado no indicador das audições.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $audivel  Entidade
     *                                                      consultada.
     * @return string Conteúdo HTML do indicador.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterConteudoIndicador(
        MetalThursday|SeccaoMetalThursday $audivel,
    ): string {
        $nomes =
            $audivel
                ->audicoes()
                ->with([
                    'utilizador:id,nome',
                ])
                ->orderBy(
                    'id',
                )
                ->get()
                ->map(
                    static function (
                        Audicao $audicao,
                    ): ?string {
                        $nome =
                            $audicao
                                ->utilizador
                                ?->nome;

                        if (! is_string($nome)) {
                            return null;
                        }

                        $nomeNormalizado =
                            trim(
                                $nome,
                            );

                        return $nomeNormalizado !== ''
                            ? $nomeNormalizado
                            : null;
                    },
                )
                ->filter(
                    static fn (
                        mixed $nome,
                    ): bool => is_string(
                        $nome,
                    ),
                )
                ->unique()
                ->sort(
                    SORT_NATURAL
                        | SORT_FLAG_CASE,
                )
                ->values();

        if ($nomes->isEmpty()) {
            return 'Ninguém marcou como ouvido.';
        }

        return $nomes
            ->map(
                static fn (
                    string $nome,
                ): string => e(
                    $nome,
                ),
            )
            ->implode(
                '<br>',
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
                'É necessário iniciar sessão para registar uma audição.',
            );
        }

        return (int) $identificador;
    }
}
