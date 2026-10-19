<?php

declare(strict_types=1);

namespace App\Servicos\Interacoes;

use App\Models\Interacoes\Comentario;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impede a repetição da mesma submissão de comentários ou respostas.
 *
 * A chave identifica uma operação e não o conteúdo do comentário.
 *
 * @since 2.0.0
 */
final class ServicoIdempotenciaComentarios
{
    /**
     * Obtém o comentário criado anteriormente ou executa a criação.
     *
     * Deve ser chamado dentro da transação que valida a publicação.
     *
     * @param  Closure(): Comentario  $criar
     * @return array{comentario: Comentario, novo: bool}
     */
    public function obterOuCriar(
        int $identificadorUtilizador,
        ?string $chave,
        string $operacao,
        string $contexto,
        string $conteudo,
        Closure $criar,
    ): array {
        if (DB::transactionLevel() < 1) {
            throw new LogicException(
                'A idempotência de comentários exige uma transação ativa.',
            );
        }

        if ($chave === null) {
            return [
                'comentario' => $criar(),
                'novo' => true,
            ];
        }

        $assinatura = hash(
            'sha256',
            json_encode(
                [$operacao, $contexto, $conteudo],
                JSON_THROW_ON_ERROR,
            ),
        );

        /*
         * A restrição única sincroniza operações concorrentes.
         * Uma inserção pendente só fica visível após o commit.
         */
        try {
            DB::table('submissoes_comentarios')
                ->insert([
                    'utilizador_id' => $identificadorUtilizador,
                    'chave' => $chave,
                    'assinatura' => $assinatura,
                    'comentario_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $inseridos = 1;
        } catch (UniqueConstraintViolationException) {
            $inseridos = 0;
        }

        $registo = DB::table('submissoes_comentarios')
            ->where('utilizador_id', $identificadorUtilizador)
            ->where('chave', $chave)
            ->lockForUpdate()
            ->first(['id', 'assinatura', 'comentario_id']);

        if ($registo === null) {
            throw new LogicException(
                'Não foi possível persistir a submissão do comentário.',
            );
        }

        if ($inseridos === 1) {
            $comentario = $criar();

            DB::table('submissoes_comentarios')
                ->where('id', $registo->id)
                ->update([
                    'comentario_id' => $comentario->getKey(),
                    'updated_at' => now(),
                ]);

            return [
                'comentario' => $comentario,
                'novo' => true,
            ];
        }

        if (! hash_equals(
            (string) $registo->assinatura,
            $assinatura,
        )) {
            abort(
                Response::HTTP_CONFLICT,
                'Chave de submissão já utilizada com dados diferentes.',
            );
        }

        /*
         * A transacao pode ter iniciado uma leitura consistente antes de
         * outra submissao concorrente confirmar o comentario.
         *
         * FOR UPDATE obriga a consultar o estado confirmado mais recente,
         * em vez de reutilizar o snapshot anterior (REPEATABLE READ).
         */
        $comentario = Comentario::query()
            ->whereKey((int) $registo->comentario_id)
            ->lockForUpdate()
            ->first();

        if (
            ! $comentario instanceof Comentario
            || $comentario->temConteudoEliminado()
        ) {
            abort(
                Response::HTTP_GONE,
                'O comentário original já não está disponível.',
            );
        }

        return [
            'comentario' => $comentario,
            'novo' => false,
        ];
    }
}
