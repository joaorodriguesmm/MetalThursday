<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use App\Servicos\Interacoes\ServicoDisponibilidadeInteracoes;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Informa o denunciante de uma decisão de moderação.
 *
 * Conserva o estado decidido e os identificadores no momento da decisão,
 * revalidando a disponibilidade do conteúdo durante a entrega.
 *
 * @since 2.0.0
 */
final class NotificacaoDecisaoDenunciaComentario extends NotificacaoAplicacao
{
    /**
     * @throws InvalidArgumentException Quando os dados da decisão são inválidos.
     */
    public function __construct(
        private readonly int $identificadorDenuncia,
        private readonly string $estado,
        private readonly ?int $identificadorMetalThursday = null,
        private readonly ?int $identificadorComentario = null,
    ) {
        if ($identificadorDenuncia < 1) {
            throw new InvalidArgumentException(
                'A denúncia tem de possuir um identificador positivo.',
            );
        }

        if (! in_array($estado, ['rejeitada', 'aceite'], true)) {
            throw new InvalidArgumentException(
                'O estado da decisão de moderação é inválido.',
            );
        }

        if (
            ($identificadorMetalThursday !== null && $identificadorMetalThursday < 1)
            || ($identificadorComentario !== null && $identificadorComentario < 1)
        ) {
            throw new InvalidArgumentException(
                'Os identificadores do contexto da denúncia são inválidos.',
            );
        }
    }

    /**
     * @return array{tipo: string, identificador_denuncia: int, estado: string, titulo: string, mensagem: string, ligacao: string, icone: string, cor: string}
     */
    public function toArray(Utilizador $utilizador): array
    {
        return [
            'tipo' => 'decisao_denuncia_comentario',
            'identificador_denuncia' => $this->identificadorDenuncia,
            'estado' => $this->estado,
            'titulo' => 'Denúncia analisada',
            'mensagem' => $this->obterLinhaMensagem($utilizador),
            'ligacao' => $this->obterUrlAcao($utilizador),
            'icone' => 'bi-shield-check',
            'cor' => 'text-info',
        ];
    }

    /**
     * Restringe a notificação a utilizadores com acesso ativo.
     *
     * @return array<int, string>
     */
    public function via(object $notificavel): array
    {
        if ($notificavel instanceof Utilizador && ! $notificavel->temAcessoAtivo()) {
            return [];
        }

        return parent::via($notificavel);
    }

    /**
     * Revalida o acesso do destinatário antes da entrega.
     */
    public function shouldSend(object $notificavel, string $canal): bool
    {
        if (! $notificavel instanceof Utilizador || ! $notificavel->exists) {
            return false;
        }

        $utilizadorAtual = Utilizador::query()->find($notificavel->id);

        return $utilizadorAtual instanceof Utilizador
            && $utilizadorAtual->temAcessoAtivo()
            && parent::shouldSend($utilizadorAtual, $canal);
    }

    protected function deveEnviarPorEmail(Utilizador $utilizador): bool
    {
        if (! $utilizador->temAcessoAtivo()) {
            return false;
        }

        return $utilizador->temPermissaoEmail(
            IdentificadorPermissaoEmail::TodasNotificacoes->value,
        )
            || $utilizador->temPermissaoEmail(
                IdentificadorPermissaoEmail::DecisoesDenunciasComentarios->value,
            );
    }

    protected function obterAssunto(Utilizador $utilizador): string
    {
        return 'MetalThursday — Decisão sobre a tua denúncia';
    }

    protected function obterLinhaMensagem(Utilizador $utilizador): string
    {
        return $this->estado === 'rejeitada'
            ? 'A denúncia que apresentaste foi analisada e rejeitada pela moderação.'
            : 'A denúncia que apresentaste foi analisada e aceite pela moderação.';
    }

    protected function obterTextoAcao(Utilizador $utilizador): string
    {
        $parametros = $this->obterParametrosAcao();

        if ($parametros === []) {
            return 'Abrir MetalThursday';
        }

        return isset($parametros['comentario'])
            ? 'Ver comentário'
            : 'Ver publicação';
    }

    protected function obterUrlAcao(Utilizador $utilizador): string
    {
        $parametros = $this->obterParametrosAcao();

        return $parametros === []
            ? route('inicio')
            : route('metal-thursday.detalhes', $parametros);
    }

    /**
     * Revalida o contexto da denúncia no momento da construção da mensagem.
     *
     * @return array{metalThursday?: int, comentario?: int}
     */
    private function obterParametrosAcao(): array
    {
        if ($this->identificadorMetalThursday === null) {
            return [];
        }

        $publicacao = MetalThursday::query()
            ->find($this->identificadorMetalThursday);

        if (
            ! $publicacao instanceof MetalThursday
            || ! $publicacao->estaPublicada()
        ) {
            return [];
        }

        $parametros = [
            'metalThursday' => $publicacao->id,
        ];

        if ($this->identificadorComentario === null) {
            return $parametros;
        }

        $comentario = Comentario::query()
            ->find($this->identificadorComentario);

        if (
            ! $comentario instanceof Comentario
            || $comentario->temConteudoEliminado()
        ) {
            return $parametros;
        }

        try {
            $raiz = app(ServicoDisponibilidadeInteracoes::class)
                ->obterMetalThursdayPublicada($comentario);

            if ($raiz->id === $publicacao->id) {
                $parametros['comentario'] = $comentario->id;
            }
        } catch (NotFoundHttpException) {
            // Mantém a ligação à publicação quando o comentário está indisponível.
        }

        return $parametros;
    }
}
