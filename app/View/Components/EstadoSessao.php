<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\View\Component;
use Stringable;

/**
 * Prepara a apresentação de uma mensagem armazenada na sessão.
 *
 * Quando existem várias mensagens, é apresentada apenas a primeira segundo
 * a prioridade definida: erro, aviso, sucesso e informação.
 *
 * @since 1.0.0
 */
final class EstadoSessao extends Component
{
    /**
     * Configurações dos tipos de mensagem suportados.
     *
     * A ordem dos elementos define a prioridade de apresentação.
     *
     * @var array<string, array{
     *     classeAlerta: string,
     *     funcaoAcessivel: string,
     *     prioridadeAnuncio: string
     * }>
     *
     * @since 2.0.0
     */
    private const CONFIGURACOES_MENSAGENS = [
        'erro' => [
            'classeAlerta' => 'alert-danger',
            'funcaoAcessivel' => 'alert',
            'prioridadeAnuncio' => 'assertive',
        ],
        'aviso' => [
            'classeAlerta' => 'alert-warning',
            'funcaoAcessivel' => 'status',
            'prioridadeAnuncio' => 'polite',
        ],
        'sucesso' => [
            'classeAlerta' => 'alert-success',
            'funcaoAcessivel' => 'status',
            'prioridadeAnuncio' => 'polite',
        ],
        'informacao' => [
            'classeAlerta' => 'alert-info',
            'funcaoAcessivel' => 'status',
            'prioridadeAnuncio' => 'polite',
        ],
    ];

    /**
     * Indica se existe uma mensagem para apresentar.
     *
     * @since 2.0.0
     */
    public readonly bool $temMensagem;

    /**
     * Mensagem apresentada.
     *
     * @since 2.0.0
     */
    public readonly ?string $mensagem;

    /**
     * Classe visual do alerta.
     *
     * @since 2.0.0
     */
    public readonly ?string $classeAlerta;

    /**
     * Função de acessibilidade do alerta.
     *
     * @since 2.0.0
     */
    public readonly ?string $funcaoAcessivel;

    /**
     * Prioridade utilizada no anúncio da mensagem.
     *
     * @since 2.0.0
     */
    public readonly ?string $prioridadeAnuncio;

    /**
     * Cria uma nova instância do componente.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $configuracaoMensagem =
            $this->obterConfiguracaoMensagem();

        $this->temMensagem =
            $configuracaoMensagem !== null;

        $this->mensagem =
            $configuracaoMensagem['mensagem']
            ?? null;

        $this->classeAlerta =
            $configuracaoMensagem['classeAlerta']
            ?? null;

        $this->funcaoAcessivel =
            $configuracaoMensagem['funcaoAcessivel']
            ?? null;

        $this->prioridadeAnuncio =
            $configuracaoMensagem['prioridadeAnuncio']
            ?? null;
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista da mensagem de sessão.
     *
     * @since 1.0.0
     */
    public function render(): View
    {
        return view(
            'components.estado-sessao',
        );
    }

    /**
     * Obtém a primeira mensagem válida disponível na sessão.
     *
     * @return array{
     *     mensagem: string,
     *     classeAlerta: string,
     *     funcaoAcessivel: string,
     *     prioridadeAnuncio: string
     * }|null Configuração da mensagem selecionada ou nulo.
     *
     * @since 2.0.0
     */
    private function obterConfiguracaoMensagem(): ?array
    {
        foreach (
            self::CONFIGURACOES_MENSAGENS as $tipoMensagem => $configuracao
        ) {
            $mensagem = $this->normalizarMensagem(
                Session::get(
                    $tipoMensagem,
                ),
            );

            if ($mensagem === null) {
                continue;
            }

            return [
                'mensagem' => $mensagem,
                'classeAlerta' => $configuracao['classeAlerta'],
                'funcaoAcessivel' => $configuracao['funcaoAcessivel'],
                'prioridadeAnuncio' => $configuracao['prioridadeAnuncio'],
            ];
        }

        return null;
    }

    /**
     * Normaliza uma mensagem obtida da sessão.
     *
     * Apenas são aceites textos ou objetos convertíveis em texto. A mensagem
     * será escapada normalmente pela vista, mesmo quando tiver origem num
     * objeto convertível.
     *
     * @param  mixed  $valor  Valor obtido da sessão.
     * @return string|null Mensagem normalizada ou nulo.
     *
     * @since 2.0.0
     */
    private function normalizarMensagem(
        mixed $valor,
    ): ?string {
        if (
            ! is_string($valor)
            && ! $valor instanceof Stringable
        ) {
            return null;
        }

        $mensagem = trim(
            (string) $valor,
        );

        return $mensagem !== ''
            ? $mensagem
            : null;
    }
}
