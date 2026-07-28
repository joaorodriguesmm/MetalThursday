<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interacoes;

use App\Http\Controllers\Controller;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\Interacoes\Gosto;
use App\Servicos\Notificacoes\NotificadorInteracoes;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gere os gostos associados aos comentários.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class ControladorGosto extends Controller
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
     * @param  NotificadorInteracoes  $notificadorInteracoes  Serviço de
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
     * Adiciona ou remove o gosto do utilizador autenticado.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Comentario  $comentario  Comentário alterado.
     * @return JsonResponse Estado atualizado do gosto.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function alternar(
        Request $pedido,
        Comentario $comentario,
    ): JsonResponse {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $pedido,
            );

        /**
         * @var array{
         *     comentario: Comentario,
         *     adicionado: bool,
         *     numero_gostos: int
         * } $resultado
         */
        $resultado =
            DB::transaction(
                static function () use (
                    $comentario,
                    $identificadorUtilizador,
                ): array {
                    $comentarioBloqueado =
                        Comentario::query()
                            ->whereKey(
                                $comentario->getKey(),
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $gosto =
                        $comentarioBloqueado
                            ->gostos()
                            ->where(
                                'utilizador_id',
                                $identificadorUtilizador,
                            )
                            ->first();

                    if ($gosto instanceof Gosto) {
                        $gosto->deleteOrFail();

                        $adicionado =
                            false;
                    } else {
                        $comentarioBloqueado
                            ->gostos()
                            ->create([
                                'utilizador_id' => $identificadorUtilizador,
                            ]);

                        $adicionado =
                            true;
                    }

                    return [
                        'comentario' => $comentarioBloqueado,

                        'adicionado' => $adicionado,

                        'numero_gostos' => $comentarioBloqueado
                            ->gostos()
                            ->count(),
                    ];
                },
                self::TENTATIVAS_TRANSACAO,
            );

        $comentarioAtualizado =
            $resultado['comentario'];

        $adicionado =
            $resultado['adicionado'];

        if ($adicionado) {
            $this
                ->notificadorInteracoes
                ->notificarOutrosUtilizadores(
                    $comentarioAtualizado,
                    'gostou',
                );
        }

        $dadosIndicador =
            $this->obterDadosIndicador(
                $comentarioAtualizado,
            );

        return response()->json([
            'adicionado' => $adicionado,

            'numero_gostos' => $resultado['numero_gostos'],

            'mensagem' => $adicionado
                ? 'Gosto adicionado.'
                : 'Gosto removido.',

            'conteudo_indicador_html' => $dadosIndicador['conteudo_html'],
        ]);
    }

    /**
     * Obtém os utilizadores que gostaram do comentário.
     *
     * @param  Comentario  $comentario  Comentário consultado.
     * @return JsonResponse Lista de utilizadores.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function listarUtilizadores(
        Comentario $comentario,
    ): JsonResponse {
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
     * @param  Comentario  $comentario  Comentário consultado.
     * @return array{
     *     nomes: list<string>,
     *     conteudo_html: string
     * } Dados preparados.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterDadosIndicador(
        Comentario $comentario,
    ): array {
        $nomes =
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
                ->map(
                    static function (
                        mixed $nome,
                    ): ?string {
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
                ->values()
                ->all();

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

            'conteudo_html' => $nomesEscapados === []
                ? 'Ainda não há gostos.'
                : implode(
                    '<br>',
                    $nomesEscapados,
                ),
        ];
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
                'É necessário iniciar sessão para adicionar um gosto.',
            );
        }

        return (int) $identificador;
    }
}
