<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Notifica os utilizadores sobre a publicação de uma nova MetalThursday.
 *
 * A notificação guarda apenas um retrato de valores escalares obtidos no
 * momento da criação. O processamento posterior da fila não depende da
 * existência atual do modelo nem executa consultas para reconstruir a
 * mensagem.
 *
 * @since 1.0.0
 *
 * @version 3.1.0
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
     * Identificador da MetalThursday publicada.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private readonly int $identificadorMetalThursday;

    /**
     * Nome utilizado para apresentar a MetalThursday.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private readonly string $nomeMetalThursday;

    /**
     * Nome do autor no momento da publicação.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private readonly string $nomeAutor;

    /**
     * Nome do utilizador que publicou a MetalThursday.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private readonly string $nomeCriador;

    /**
     * Cria a notificação e captura os dados necessários para a fila.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday publicada.
     *
     * @throws InvalidArgumentException Quando a MetalThursday não está
     *                                  persistida ou não possui um
     *                                  identificador válido.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    public function __construct(
        MetalThursday $metalThursday,
    ) {
        $this->identificadorMetalThursday =
            $this->obterIdentificadorMetalThursday(
                $metalThursday,
            );

        $metalThursday->loadMissing([
            'edicao:id,nome',
            'autor:id,nome',
            'criadoPor:id,nome',
        ]);

        $this->nomeMetalThursday =
            $this->construirNomeMetalThursday(
                $metalThursday,
            );

        $this->nomeAutor =
            $this->obterNomeUtilizador(
                $metalThursday->autor,
                'um utilizador',
            );

        $this->nomeCriador =
            $this->obterNomeUtilizador(
                $metalThursday->criadoPor,
                'o sistema',
            );

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
     * @version 3.1.0
     */
    public function toArray(
        Utilizador $utilizador,
    ): array {
        return [
            'tipo' => 'metal_thursday_criada',

            'identificador_metal_thursday' => $this->identificadorMetalThursday,

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
     * @version 2.1.0
     */
    protected function obterAssunto(
        Utilizador $utilizador,
    ): string {
        return sprintf(
            'Nova MetalThursday disponível: %s',
            $this->nomeMetalThursday,
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
     * @version 3.1.0
     */
    protected function obterUrlAcao(
        Utilizador $utilizador,
    ): ?string {
        return route(
            'metal-thursday.detalhes',
            [
                'metalThursday' => $this->identificadorMetalThursday,
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
     * @version 2.0.0
     */
    private function obterMensagem(): string
    {
        return sprintf(
            'Uma nova MetalThursday da autoria de %s foi publicada por %s: %s',
            $this->nomeAutor,
            $this->nomeCriador,
            $this->nomeMetalThursday,
        );
    }

    /**
     * Constrói o nome utilizado para identificar a MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday consultada.
     * @return string Nome de apresentação.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function construirNomeMetalThursday(
        MetalThursday $metalThursday,
    ): string {
        $nome =
            $metalThursday->nome;

        if (
            is_string($nome)
            && trim($nome) !== ''
        ) {
            return trim(
                $nome,
            );
        }

        $edicao =
            $metalThursday->edicao;

        $nomeEdicao =
            $edicao instanceof Edicao
            && is_string($edicao->nome)
            && trim($edicao->nome) !== ''
            ? trim(
                $edicao->nome,
            )
            : null;

        $data =
            $metalThursday->data;

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
            $this->identificadorMetalThursday,
        );
    }

    /**
     * Obtém um nome válido de utilizador ou o texto alternativo.
     *
     * @param  Utilizador|null  $utilizador  Utilizador consultado.
     * @param  string  $alternativa  Texto utilizado quando o nome não existe.
     * @return string Nome normalizado ou alternativa.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private function obterNomeUtilizador(
        ?Utilizador $utilizador,
        string $alternativa,
    ): string {
        if (
            $utilizador instanceof Utilizador
            && is_string($utilizador->nome)
            && trim($utilizador->nome) !== ''
        ) {
            return trim(
                $utilizador->nome,
            );
        }

        return $alternativa;
    }

    /**
     * Obtém o identificador persistido da MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday recebida.
     * @return int Identificador persistido.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido ou
     *                                  não possui um identificador válido.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorMetalThursday(
        MetalThursday $metalThursday,
    ): int {
        if (! $metalThursday->exists) {
            throw new InvalidArgumentException(
                'A MetalThursday da notificação deve estar persistida.',
            );
        }

        $identificador =
            $metalThursday->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (is_string($identificador)) {
            $identificadorNormalizado = trim(
                $identificador,
            );

            if (
                $identificadorNormalizado !== ''
                && ctype_digit(
                    $identificadorNormalizado,
                )
                && (int) $identificadorNormalizado > 0
            ) {
                return (int) $identificadorNormalizado;
            }
        }

        throw new InvalidArgumentException(
            'A MetalThursday da notificação deve possuir um identificador válido.',
        );
    }
}
