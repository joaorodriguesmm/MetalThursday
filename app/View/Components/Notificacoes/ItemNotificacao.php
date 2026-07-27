<?php

declare(strict_types=1);

namespace App\View\Components\Notificacoes;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara uma notificação para apresentação.
 *
 * O componente normaliza o conteúdo persistido no campo de dados e impede
 * que classes CSS ou ligações inválidas sejam introduzidas na apresentação.
 *
 * @since 3.0.0
 *
 * @version 1.0.0
 */
final class ItemNotificacao extends Component
{
    /**
     * Dados preparados para apresentação.
     *
     * @var array{
     *     identificador: string,
     *     lida: bool,
     *     icone: string,
     *     cor: string,
     *     titulo: string,
     *     mensagem: string,
     *     tempoRelativo: string,
     *     ligacao: string|null
     * }
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly array $dados;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  DatabaseNotification  $notificacao  Notificação apresentada.
     *
     * @throws LogicException Quando a notificação não possui identificador.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        DatabaseNotification $notificacao,
    ) {
        $identificador =
            $notificacao->getKey();

        if (
            ! is_string($identificador)
            || trim($identificador) === ''
        ) {
            throw new LogicException(
                'A notificação não possui um identificador válido.',
            );
        }

        $conteudo =
            is_array($notificacao->data)
            ? $notificacao->data
            : [];

        $this->dados = [
            'identificador' => trim(
                $identificador,
            ),

            'lida' => $notificacao->read_at !== null,

            'icone' => $this->normalizarIcone(
                $conteudo['icone']
                    ?? null,
            ),

            'cor' => $this->normalizarCor(
                $conteudo['cor']
                    ?? null,
            ),

            'titulo' => $this->normalizarTexto(
                $conteudo['titulo']
                    ?? null,
            )
                ?? 'Nova notificação',

            'mensagem' => $this->normalizarTexto(
                $conteudo['mensagem']
                    ?? null,
            )
                ?? 'Tens uma nova notificação.',

            'tempoRelativo' => $notificacao->created_at?->diffForHumans()
                ?? 'Data indisponível',

            'ligacao' => $this->normalizarLigacao(
                $conteudo['ligacao']
                    ?? null,
            ),
        ];
    }

    /**
     * Obtém a view do componente.
     *
     * @return View View da notificação.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function render(): View
    {
        return view(
            'components.notificacoes.item-notificacao',
        );
    }

    /**
     * Normaliza um texto opcional.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Texto normalizado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarTexto(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $texto =
            trim(
                $valor,
            );

        return $texto !== ''
            ? $texto
            : null;
    }

    /**
     * Normaliza a classe de um ícone Bootstrap.
     *
     * @param  mixed  $valor  Classe recebida.
     * @return string Classe segura.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarIcone(
        mixed $valor,
    ): string {
        $icone =
            $this->normalizarTexto(
                $valor,
            );

        if (
            $icone === null
            || preg_match(
                '/^bi-[a-z0-9-]+$/',
                $icone,
            ) !== 1
        ) {
            return 'bi-info-circle';
        }

        return $icone;
    }

    /**
     * Normaliza a classe de cor Bootstrap.
     *
     * @param  mixed  $valor  Classe recebida.
     * @return string Classe segura.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarCor(
        mixed $valor,
    ): string {
        $cor =
            $this->normalizarTexto(
                $valor,
            );

        $coresPermitidas = [
            'text-primary',
            'text-secondary',
            'text-success',
            'text-danger',
            'text-warning',
            'text-info',
            'text-light',
            'text-muted',
        ];

        return in_array(
            $cor,
            $coresPermitidas,
            true,
        )
            ? $cor
            : 'text-info';
    }

    /**
     * Normaliza uma ligação guardada na notificação.
     *
     * São permitidas ligações internas e endereços HTTP ou HTTPS.
     *
     * @param  mixed  $valor  Ligação recebida.
     * @return string|null Ligação segura.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarLigacao(
        mixed $valor,
    ): ?string {
        $ligacao =
            $this->normalizarTexto(
                $valor,
            );

        if ($ligacao === null) {
            return null;
        }

        if (str_starts_with($ligacao, '/')) {
            return $ligacao;
        }

        if (
            filter_var(
                $ligacao,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            return null;
        }

        $esquema =
            parse_url(
                $ligacao,
                PHP_URL_SCHEME,
            );

        return in_array(
            $esquema,
            [
                'http',
                'https',
            ],
            true,
        )
            ? $ligacao
            : null;
    }
}
