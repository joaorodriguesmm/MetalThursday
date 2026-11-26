<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\DenunciaComentario;
use InvalidArgumentException;

/**
 * Informa os superadministradores de uma nova denúncia de comentário.
 *
 * Guarda apenas o identificador da denúncia, sem depender de relações
 * Eloquent que possam mudar enquanto o trabalho aguarda na fila.
 *
 * @since 2.0.0
 */
final class NotificacaoNovaDenunciaComentario extends NotificacaoAplicacao
{
    /**
     * @throws InvalidArgumentException Quando o identificador é inválido.
     */
    public function __construct(
        private readonly int $identificadorDenuncia,
    ) {
        if ($identificadorDenuncia < 1) {
            throw new InvalidArgumentException(
                'A denúncia tem de possuir um identificador positivo.',
            );
        }
    }

    /**
     * Restringe ambos os canais aos superadministradores com acesso ativo.
     *
     * @return array<int, string>
     */
    public function via(object $notificavel): array
    {
        if (
            $notificavel instanceof Utilizador
            && (
                ! $notificavel->temAcessoAtivo()
                || ! $notificavel->eSuperAdministrador()
            )
        ) {
            return [];
        }

        return parent::via($notificavel);
    }

    /**
     * Evita a entrega tardia se o destinatário perder os privilégios.
     */
    public function shouldSend(object $notificavel, string $canal): bool
    {
        if (
            ! $notificavel instanceof Utilizador
            || ! $notificavel->exists
        ) {
            return false;
        }

        $utilizadorAtual = Utilizador::query()
            ->find($notificavel->id);

        return $utilizadorAtual instanceof Utilizador
            && $utilizadorAtual->temAcessoAtivo()
            && $utilizadorAtual->eSuperAdministrador()
            && DenunciaComentario::query()
                ->whereKey($this->identificadorDenuncia)
                ->where('estado', 'pendente')
                ->exists()
            && parent::shouldSend($notificavel, $canal);
    }

    /**
     * @return array{tipo: string, identificador_denuncia: int, titulo: string, mensagem: string, ligacao: string, icone: string, cor: string}
     */
    public function toArray(Utilizador $utilizador): array
    {
        return [
            'tipo' => 'nova_denuncia_comentario',
            'identificador_denuncia' => $this->identificadorDenuncia,
            'titulo' => 'Nova denúncia de comentário',
            'mensagem' => 'Foi apresentada uma denúncia de comentário para análise.',
            'ligacao' => $this->obterUrlAcao($utilizador),
            'icone' => 'bi-flag',
            'cor' => 'text-warning',
        ];
    }

    protected function deveEnviarPorEmail(Utilizador $utilizador): bool
    {
        return $utilizador->temAcessoAtivo()
            && $utilizador->eSuperAdministrador()
            && (
                $utilizador->temPermissaoEmail(
                    IdentificadorPermissaoEmail::TodasNotificacoes->value,
                )
                || $utilizador->temPermissaoEmail(
                    IdentificadorPermissaoEmail::NovasDenunciasComentarios->value,
                )
            );
    }

    protected function obterAssunto(Utilizador $utilizador): string
    {
        return 'MetalThursday — Nova denúncia de comentário';
    }

    protected function obterLinhaMensagem(Utilizador $utilizador): string
    {
        return 'Foi apresentada uma denúncia de comentário que requer análise pela moderação.';
    }

    protected function obterTextoAcao(Utilizador $utilizador): string
    {
        return 'Consultar denúncias';
    }

    protected function obterUrlAcao(Utilizador $utilizador): string
    {
        return route('moderacao.denuncias-comentarios.painel');
    }
}
