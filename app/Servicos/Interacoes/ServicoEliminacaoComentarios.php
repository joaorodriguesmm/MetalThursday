<?php

declare(strict_types=1);

namespace App\Servicos\Interacoes;

use App\Models\Interacoes\Comentario;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Elimina comentários preservando a estrutura das conversas.
 */
final class ServicoEliminacaoComentarios
{
    /**
     * Elimina um comentário previamente bloqueado e autorizado.
     *
     * @return array{
     *     modo: 'marcador'|'remover',
     *     comentario: Comentario|null,
     *     comentario_pai_id: int|null,
     *     comentarios_removidos_ids: list<int>,
     *     pai_atualizado: array{id: int, numero_respostas: int}|null
     * }
     */
    public function eliminarComentarioBloqueado(
        Comentario $comentarioBloqueado,
    ): array {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('A eliminação exige uma transação ativa.');
        }

        if ($comentarioBloqueado->trashed() || $comentarioBloqueado->temConteudoEliminado()) {
            throw new LogicException('O comentário já foi eliminado.');
        }

        if (
            $comentarioBloqueado
                ->respostas()
                ->lockForUpdate()->first(['id']) !== null
        ) {
            $comentarioBloqueado
                ->conteudo_eliminado_em =
                now();

            $comentarioBloqueado
                ->saveOrFail();

            return [
                'modo' => 'marcador',

                'comentario' => $comentarioBloqueado,

                'comentario_pai_id' => $comentarioBloqueado
                    ->comentario_pai_id,

                'comentarios_removidos_ids' => [],

                'pai_atualizado' => null,
            ];
        }

        $identificadorComentario =
            $comentarioBloqueado->id;

        $identificadorPai =
            $comentarioBloqueado
                ->comentario_pai_id;

        $comentarioBloqueado
            ->deleteOrFail();

        $limpeza =
            $this
                ->limparMarcadoresSemRespostas(
                    $identificadorPai,
                );

        return [
            'modo' => 'remover',

            'comentario' => null,

            'comentario_pai_id' => $identificadorPai,

            'comentarios_removidos_ids' => [
                $identificadorComentario,
                ...$limpeza['comentarios_removidos_ids'],
            ],

            'pai_atualizado' => $limpeza['pai_atualizado'],
        ];
    }

    /**
     * Remove tombstones ancestrais que deixaram de possuir respostas.
     *
     * A limpeza propaga-se para cima até encontrar um comentário com conteúdo,
     * um tombstone que ainda possua descendentes ou a raiz da conversa.
     *
     * O primeiro pai que permanece ativo é devolvido com a quantidade atualizada
     * das respetivas respostas, permitindo sincronizar a interface sem novo
     * pedido HTTP.
     *
     * @param  int|null  $identificadorPai  Primeiro pai verificado.
     * @return array{
     *     comentarios_removidos_ids: list<int>,
     *     pai_atualizado: array{
     *         id: int,
     *         numero_respostas: int
     *     }|null
     * } Resultado da limpeza.
     *
     * @since 2.0.0
     */
    private function limparMarcadoresSemRespostas(
        ?int $identificadorPai,
    ): array {
        $identificadoresRemovidos =
            [];

        $identificadorAtual =
            $identificadorPai;

        while ($identificadorAtual !== null) {
            $comentarioPai =
                Comentario::query()
                    ->whereKey(
                        $identificadorAtual,
                    )
                    ->lockForUpdate()
                    ->first();

            if (! $comentarioPai instanceof Comentario) {
                return [
                    'comentarios_removidos_ids' => $identificadoresRemovidos,

                    'pai_atualizado' => null,
                ];
            }

            $numeroRespostas =
                $comentarioPai
                    ->respostas()
                    ->lockForUpdate()->count();

            if (
                ! $comentarioPai
                    ->temConteudoEliminado()
                || $numeroRespostas > 0
            ) {
                return [
                    'comentarios_removidos_ids' => $identificadoresRemovidos,

                    'pai_atualizado' => [
                        'id' => $comentarioPai->id,

                        'numero_respostas' => $numeroRespostas,
                    ],
                ];
            }

            $proximoIdentificadorPai =
                $comentarioPai
                    ->comentario_pai_id;

            $identificadoresRemovidos[] =
                $comentarioPai->id;

            $comentarioPai
                ->deleteOrFail();

            $identificadorAtual =
                $proximoIdentificadorPai;
        }

        return [
            'comentarios_removidos_ids' => $identificadoresRemovidos,

            'pai_atualizado' => null,
        ];
    }
}
