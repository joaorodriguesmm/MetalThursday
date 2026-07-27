<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Stringable;

/**
 * Prepara a apresentação de uma mensagem armazenada na sessão.
 *
 * Quando existem várias mensagens, é apresentada apenas a primeira segundo
 * a prioridade definida: erro, aviso, sucesso e informação.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class EstadoSessao extends Component
{
    /**
     * Configurações dos tipos de mensagem suportados.
     *
     * A ordem dos elementos define a prioridade de apresentação.
     *
     * @var array<string, array{
     *     classe_alerta: string,
     *     funcao_acessivel: string,
     *     prioridade_anuncio: string
     * }>
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private const CONFIGURACOES_MENSAGENS = [
        'erro' => [
            'classe_alerta' => 'alert-danger',

            'funcao_acessivel' => 'alert',

            'prioridade_anuncio' => 'assertive',
        ],

        'aviso' => [
            'classe_alerta' => 'alert-warning',

            'funcao_acessivel' => 'status',

            'prioridade_anuncio' => 'polite',
        ],

        'sucesso' => [
            'classe_alerta' => 'alert-success',

            'funcao_acessivel' => 'status',

            'prioridade_anuncio' => 'polite',
        ],

        'informacao' => [
            'classe_alerta' => 'alert-info',

            'funcao_acessivel' => 'status',

            'prioridade_anuncio' => 'polite',
        ],
    ];

    /**
     * Indica se existe uma mensagem para apresentar.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly bool $temMensagem;

    /**
     * Mensagem apresentada.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly ?string $mensagem;

    /**
     * Classe visual do alerta.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly ?string $classeAlerta;

    /**
     * Função de acessibilidade do alerta.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly ?string $funcaoAcessivel;

    /**
     * Prioridade utilizada no anúncio da mensagem.
     *
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public readonly ?string $prioridadeAnuncio;

    /**
     * Cria uma nova instância do componente.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function __construct()
    {
        $mensagemSelecionada = null;
        $configuracaoSelecionada = null;

        foreach (
            self::CONFIGURACOES_MENSAGENS as $tipoMensagem => $configuracao
        ) {
            $mensagem = $this->normalizarMensagem(
                session()->get(
                    $tipoMensagem,
                ),
            );

            if ($mensagem === null) {
                continue;
            }

            $mensagemSelecionada = $mensagem;
            $configuracaoSelecionada = $configuracao;

            break;
        }

        $this->temMensagem =
            $mensagemSelecionada !== null
            && $configuracaoSelecionada !== null;

        $this->mensagem =
            $mensagemSelecionada;

        $this->classeAlerta =
            $configuracaoSelecionada['classe_alerta']
            ?? null;

        $this->funcaoAcessivel =
            $configuracaoSelecionada['funcao_acessivel']
            ?? null;

        $this->prioridadeAnuncio =
            $configuracaoSelecionada['prioridade_anuncio']
            ?? null;
    }

    /**
     * Obtém a view do componente.
     *
     * @return View View da mensagem de sessão.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function render(): View
    {
        return view(
            'components.estado-sessao',
        );
    }

    /**
     * Normaliza uma mensagem obtida da sessão.
     *
     * Apenas são aceites textos ou objetos convertíveis em texto. A mensagem
     * será escapada normalmente pela view, mesmo quando tiver origem num
     * objeto convertível.
     *
     * @param  mixed  $valor  Valor obtido da sessão.
     * @return string|null Mensagem normalizada ou nulo.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
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
