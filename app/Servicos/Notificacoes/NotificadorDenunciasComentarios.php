<?php

declare(strict_types=1);

namespace App\Servicos\Notificacoes;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\Interacoes\DenunciaComentario;
use App\Notifications\NotificacaoAplicacao;
use App\Notifications\NotificacaoDecisaoDenunciaComentario;
use App\Notifications\NotificacaoNovaDenunciaComentario;
use App\Servicos\Interacoes\ServicoDisponibilidadeInteracoes;
use Illuminate\Contracts\Notifications\Dispatcher as DespachanteNotificacoes;
use Illuminate\Database\Eloquent\Collection as ColecaoEloquent;
use Illuminate\Support\Facades\DB;
use LogicException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Enfileira notificações relativas às denúncias de comentários.
 *
 * O enfileiramento integra a mesma transação da operação funcional,
 * garantindo a atomicidade entre a denúncia e as suas notificações.
 *
 * @since 2.0.0
 */
final class NotificadorDenunciasComentarios
{
    private const TAMANHO_LOTE = 200;

    public function __construct(
        private readonly DespachanteNotificacoes $notificacoes,
        private readonly ServicoDisponibilidadeInteracoes $disponibilidade,
    ) {}

    /**
     * Notifica os superadministradores ativos de uma nova denúncia.
     */
    public function notificarNovaDenuncia(
        DenunciaComentario $denuncia,
    ): void {
        $notificacao = new NotificacaoNovaDenunciaComentario(
            $denuncia->id,
        );

        $this->prepararNotificacaoTransacional($notificacao);

        Utilizador::query()
            ->comAcessoAtivo()
            ->where('papel', PapelUtilizador::SuperAdministrador->value)
            ->with('permissoesEmail')
            ->chunkById(
                self::TAMANHO_LOTE,
                function (ColecaoEloquent $destinatarios) use ($notificacao): void {
                    $this->notificacoes->send(
                        $destinatarios,
                        $notificacao,
                    );
                },
                'utilizadores.id',
                'id',
            );
    }

    /**
     * Comunica ao denunciante a decisão tomada pela moderação.
     */
    public function notificarDecisao(
        DenunciaComentario $denuncia,
    ): void {
        $identificadorMetalThursday = null;
        $identificadorComentario = null;

        $comentario = $denuncia->comentario()->first();

        if ($comentario instanceof Comentario) {
            try {
                $metalThursday = $this->disponibilidade
                    ->obterMetalThursdayPublicada($comentario);

                $identificadorMetalThursday = $metalThursday->id;

                if (! $comentario->trashed() && ! $comentario->temConteudoEliminado()) {
                    $identificadorComentario = $comentario->id;
                }
            } catch (NotFoundHttpException) {
                // A decisão continua válida sem uma publicação disponível.
            }
        }

        $notificacao = new NotificacaoDecisaoDenunciaComentario(
            $denuncia->id,
            $denuncia->estado,
            $identificadorMetalThursday,
            $identificadorComentario,
        );

        $this->prepararNotificacaoTransacional($notificacao);

        $denunciante = Utilizador::query()
            ->comAcessoAtivo()
            ->with('permissoesEmail')
            ->find($denuncia->denunciante_id);

        if ($denunciante instanceof Utilizador) {
            $this->notificacoes->send(
                $denunciante,
                $notificacao,
            );
        }
    }

    /**
     * Garante que a fila utiliza a ligação da transação atual.
     */
    private function prepararNotificacaoTransacional(
        NotificacaoAplicacao $notificacao,
    ): void {
        if (
            DB::transactionLevel() < 1
            || config('queue.connections.database.driver') !== 'database'
            || config('queue.connections.database.connection')
                !== DB::getDefaultConnection()
        ) {
            throw new LogicException(
                'As notificações de denúncias exigem a fila database na mesma ligação MariaDB.',
            );
        }

        $notificacao
            ->onConnection('database')
            ->beforeCommit();
    }
}
