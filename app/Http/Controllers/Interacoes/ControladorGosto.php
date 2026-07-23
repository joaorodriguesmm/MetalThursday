<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interacoes;

use App\Http\Controllers\Controller;
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
 * @version 2.0.0
 */
final class ControladorGosto extends Controller
{
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
     * A operação bloqueia o comentário durante a transação, impedindo que
     * pedidos simultâneos criem resultados contraditórios.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  Comentario  $comentario  Comentário alterado.
     * @return JsonResponse Estado atualizado do gosto.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function alternar(
        Request $pedido,
        Comentario $comentario,
    ): JsonResponse {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $pedido,
            );

        $resultado = DB::transaction(
            static function () use (
                $comentario,
                $identificadorUtilizador,
            ): array {
                $comentarioBloqueado = Comentario::query()
                    ->whereKey(
                        $comentario->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $gosto = $comentarioBloqueado
                    ->gostos()
                    ->where(
                        'utilizador_id',
                        $identificadorUtilizador,
                    )
                    ->first();

                if ($gosto instanceof Gosto) {
                    $gosto->deleteOrFail();

                    $adicionado = false;
                } else {
                    $comentarioBloqueado
                        ->gostos()
                        ->create([
                            'utilizador_id' => $identificadorUtilizador,
                        ]);

                    $adicionado = true;
                }

                return [
                    'comentario' => $comentarioBloqueado,

                    'adicionado' => $adicionado,

                    'numero_gostos' => $comentarioBloqueado
                        ->gostos()
                        ->count(),
                ];
            },
        );

        /** @var Comentario $comentarioAtualizado */
        $comentarioAtualizado =
            $resultado['comentario'];

        $adicionado =
            (bool) $resultado['adicionado'];

        if ($adicionado) {
            $this->notificadorInteracoes
                ->notificarOutrosUtilizadores(
                    $comentarioAtualizado,
                    'gostou do',
                );
        }

        return response()->json([
            /*
             * Estas chaves permanecem temporariamente em inglês porque são
             * consumidas pelo JavaScript atual.
             */
            'liked' => $adicionado,

            'likes_count' => (int) $resultado['numero_gostos'],

            'message' => $adicionado
                ? 'Gosto adicionado.'
                : 'Gosto removido.',

            'tooltip_html' => 'A carregar...',
        ]);
    }

    /**
     * Obtém os nomes dos utilizadores que gostaram do comentário.
     *
     * @param  Comentario  $comentario  Comentário consultado.
     * @return JsonResponse Lista de utilizadores.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function listarUtilizadores(
        Comentario $comentario,
    ): JsonResponse {
        $nomes = $comentario
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
                static fn (
                    mixed $nome,
                ): string => (string) $nome,
            )
            ->all();

        $nomesEscapados = array_map(
            static fn (
                string $nome,
            ): string => e($nome),
            $nomes,
        );

        return response()->json([
            /*
             * Estas chaves permanecem temporariamente em inglês porque são
             * consumidas pelo JavaScript atual.
             */
            'names' => $nomes,

            'html' => $nomesEscapados === []
                ? 'Ainda não há gostos.'
                : implode(
                    '<br>',
                    $nomesEscapados,
                ),
        ]);
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
     * @version 1.0.0
     */
    private function obterIdentificadorUtilizador(
        Request $pedido,
    ): int {
        $identificador = $pedido
            ->user()
            ?->getAuthIdentifier();

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
