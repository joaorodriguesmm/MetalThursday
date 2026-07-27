<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonInterface;

/**
 * Notifica os utilizadores sobre a publicação de uma nova MetalThursday.
 *
 * A notificação é guardada na base de dados e pode também ser enviada por
 * e-mail, conforme as permissões do destinatário.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class NotificacaoMetalThursdayCriada extends NotificacaoAplicacao
{
    /**
     * Identificador da permissão que autoriza todas as comunicações.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PERMISSAO_TODAS = 'todas';

    /**
     * Identificador da permissão relativa a novas publicações.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PERMISSAO_NOVAS_PUBLICACOES =
        'novas_publicacoes';

    /**
     * Cria a notificação.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday publicada.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function __construct(
        private readonly MetalThursday $metalThursday,
    ) {
        $this->afterCommit();
    }

    /**
     * Determina se a notificação deve ser enviada por e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return bool Verdadeiro quando o envio está autorizado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function deveEnviarPorEmail(
        Utilizador $utilizador,
    ): bool {
        return $utilizador->temPermissaoEmail(
            self::PERMISSAO_TODAS,
        )
            || $utilizador->temPermissaoEmail(
                self::PERMISSAO_NOVAS_PUBLICACOES,
            );
    }

    /**
     * Converte a notificação para o formato guardado na base de dados.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return array{
     *     tipo: string,
     *     identificador_metal_thursday: int,
     *     titulo: string,
     *     mensagem: string,
     *     ligacao: string,
     *     icone: string,
     *     cor: string
     * } Dados persistidos.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function toArray(
        Utilizador $utilizador,
    ): array {
        return [
            'tipo' => 'metal_thursday_criada',

            'identificador_metal_thursday' => (int) $this->metalThursday->getKey(),

            'titulo' => 'Nova MetalThursday disponível',

            'mensagem' => $this->obterMensagem(),

            'ligacao' => $this->obterUrlAcao(
                $utilizador,
            ),

            'icone' => 'bi-fire',

            'cor' => 'text-danger',
        ];
    }

    /**
     * Obtém o assunto da mensagem de e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Assunto da mensagem.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function obterAssunto(
        Utilizador $utilizador,
    ): string {
        return sprintf(
            'Nova MetalThursday disponível: %s',
            $this->obterNomeMetalThursday(),
        );
    }

    /**
     * Obtém a linha principal da mensagem.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Conteúdo principal.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function obterLinhaMensagem(
        Utilizador $utilizador,
    ): string {
        return $this->obterMensagem();
    }

    /**
     * Obtém o texto apresentado no botão da mensagem.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Texto do botão.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function obterTextoAcao(
        Utilizador $utilizador,
    ): ?string {
        return 'Ver MetalThursday';
    }

    /**
     * Obtém o endereço da página da MetalThursday.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Endereço da MetalThursday.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    protected function obterUrlAcao(
        Utilizador $utilizador,
    ): ?string {
        return route(
            'metal-thursday.detalhes',
            [
                'metalThursday' => $this->metalThursday,
            ],
        );
    }

    /**
     * Obtém a mensagem principal da notificação.
     *
     * @return string Mensagem construída.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterMensagem(): string
    {
        $this->metalThursday->loadMissing([
            'edicao',
            'autor',
            'criadoPor',
        ]);

        return sprintf(
            'Uma nova MetalThursday da autoria de %s foi publicada por %s: %s',
            $this->obterNomeAutor(),
            $this->obterNomeCriador(),
            $this->obterNomeMetalThursday(),
        );
    }

    /**
     * Obtém o nome utilizado para identificar a MetalThursday.
     *
     * @return string Nome de apresentação.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterNomeMetalThursday(): string
    {
        $nome =
            $this->metalThursday->nome;

        if (
            is_string($nome)
            && trim($nome) !== ''
        ) {
            return trim(
                $nome,
            );
        }

        $this->metalThursday->loadMissing([
            'edicao',
        ]);

        $edicao =
            $this->metalThursday->edicao;

        $nomeEdicao =
            $edicao instanceof Edicao
            && is_string($edicao->nome)
            && trim($edicao->nome) !== ''
            ? trim(
                $edicao->nome,
            )
            : null;

        $data =
            $this->metalThursday->data;

        $dataFormatada =
            $data instanceof CarbonInterface
            ? $data->format(
                'd/m/Y',
            )
            : null;

        if (
            $nomeEdicao !== null
            && $dataFormatada !== null
        ) {
            return sprintf(
                '%s — %s',
                $nomeEdicao,
                $dataFormatada,
            );
        }

        if ($nomeEdicao !== null) {
            return $nomeEdicao;
        }

        if ($dataFormatada !== null) {
            return sprintf(
                'MetalThursday de %s',
                $dataFormatada,
            );
        }

        return sprintf(
            'MetalThursday #%d',
            (int) $this->metalThursday->getKey(),
        );
    }

    /**
     * Obtém o nome do autor.
     *
     * @return string Nome do autor ou valor alternativo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterNomeAutor(): string
    {
        $autor =
            $this->metalThursday->autor;

        if (
            $autor instanceof Utilizador
            && is_string($autor->nome)
            && trim($autor->nome) !== ''
        ) {
            return trim(
                $autor->nome,
            );
        }

        return 'um utilizador';
    }

    /**
     * Obtém o nome do utilizador que publicou a MetalThursday.
     *
     * @return string Nome do criador ou valor alternativo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterNomeCriador(): string
    {
        $criador =
            $this->metalThursday->criadoPor;

        if (
            $criador instanceof Utilizador
            && is_string($criador->nome)
            && trim($criador->nome) !== ''
        ) {
            return trim(
                $criador->nome,
            );
        }

        return 'o sistema';
    }
}
