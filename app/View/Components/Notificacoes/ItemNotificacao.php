<?php

declare(strict_types=1);

namespace App\View\Components\Notificacoes;

use App\Models\Notificacoes\NotificacaoPersistida;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara uma notificação persistida para apresentação.
 *
 * O componente normaliza o conteúdo persistido e impede que classes CSS,
 * identificadores ou ligações inválidas sejam introduzidos na vista.
 *
 * @since 2.0.0
 */
final class ItemNotificacao extends Component
{
    /**
     * Ícone utilizado quando o valor persistido é inválido.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const ICONE_PREDEFINIDO =
        'bi-info-circle';

    /**
     * Cor utilizada quando o valor persistido é inválido.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const COR_PREDEFINIDA =
        'text-info';

    /**
     * Título utilizado quando o valor persistido é inválido.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const TITULO_PREDEFINIDO =
        'Nova notificação';

    /**
     * Mensagem utilizada quando o valor persistido é inválido.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_PREDEFINIDA =
        'Tens uma nova notificação.';

    /**
     * Classes de cor permitidas na apresentação.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    private const CORES_PERMITIDAS = [
        'text-primary',
        'text-secondary',
        'text-success',
        'text-danger',
        'text-warning',
        'text-info',
        'text-light',
        'text-muted',
    ];

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
     *     dataCriacao: string|null,
     *     ligacao: string|null,
     *     enderecoMarcarComoLida: string|null
     * }
     *
     * @since 2.0.0
     */
    public readonly array $dados;

    /**
     * Cria uma nova instância do componente.
     *
     * @param  NotificacaoPersistida  $notificacao  Notificação apresentada.
     *
     * @throws LogicException Quando a notificação não está persistida ou não
     *                        possui um identificador UUID válido.
     *
     * @since 2.0.0
     */
    public function __construct(
        NotificacaoPersistida $notificacao,
    ) {
        $identificador =
            $this->obterIdentificadorNotificacao(
                $notificacao,
            );

        $conteudo =
            is_array($notificacao->data)
            ? $notificacao->data
            : [];

        $lida =
            $notificacao->read_at !== null;

        $dataCriacao =
            $notificacao->created_at;

        $this->dados = [
            'identificador' => $identificador,

            'lida' => $lida,

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
                ?? self::TITULO_PREDEFINIDO,

            'mensagem' => $this->normalizarTexto(
                $conteudo['mensagem']
                    ?? null,
            )
                ?? self::MENSAGEM_PREDEFINIDA,

            'tempoRelativo' => $dataCriacao instanceof CarbonInterface
                ? $dataCriacao->diffForHumans()
                : 'Data indisponível',

            'dataCriacao' => $dataCriacao instanceof CarbonInterface
                ? $dataCriacao->toAtomString()
                : null,

            'ligacao' => $this->normalizarLigacaoInterna(
                $conteudo['ligacao']
                    ?? null,
            ),

            'enderecoMarcarComoLida' => $lida
                ? null
                : route(
                    'notificacoes.marcar-como-lida',
                    [
                        'identificadorNotificacao' => $identificador,
                    ],
                ),
        ];
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista da notificação.
     *
     * @since 2.0.0
     */
    public function render(): View
    {
        return view(
            'components.notificacoes.item-notificacao',
        );
    }

    /**
     * Obtém e valida o identificador persistido da notificação.
     *
     * @param  NotificacaoPersistida  $notificacao  Notificação recebida.
     * @return string Identificador UUID normalizado.
     *
     * @throws LogicException Quando a notificação não está persistida ou não
     *                        possui um identificador UUID válido.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorNotificacao(
        NotificacaoPersistida $notificacao,
    ): string {
        $identificador =
            $notificacao->getKey();

        if (
            ! $notificacao->exists
            || ! is_string($identificador)
        ) {
            throw new LogicException(
                'A notificação apresentada deve estar persistida.',
            );
        }

        $identificadorNormalizado =
            trim(
                $identificador,
            );

        if (! Str::isUuid($identificadorNormalizado)) {
            throw new LogicException(
                'A notificação não possui um identificador UUID válido.',
            );
        }

        return $identificadorNormalizado;
    }

    /**
     * Normaliza um texto opcional.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Texto normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarTexto(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $texto =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $valor,
                ),
            );

        if (
            ! is_string($texto)
            || $texto === ''
        ) {
            return null;
        }

        return $texto;
    }

    /**
     * Normaliza a classe de um ícone Bootstrap.
     *
     * @param  mixed  $valor  Classe recebida.
     * @return string Classe segura.
     *
     * @since 2.0.0
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
                '/^bi-[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $icone,
            ) !== 1
        ) {
            return self::ICONE_PREDEFINIDO;
        }

        return $icone;
    }

    /**
     * Normaliza a classe de cor Bootstrap.
     *
     * @param  mixed  $valor  Classe recebida.
     * @return string Classe segura.
     *
     * @since 2.0.0
     */
    private function normalizarCor(
        mixed $valor,
    ): string {
        $cor =
            $this->normalizarTexto(
                $valor,
            );

        if (
            $cor === null
            || ! in_array(
                $cor,
                self::CORES_PERMITIDAS,
                true,
            )
        ) {
            return self::COR_PREDEFINIDA;
        }

        return $cor;
    }

    /**
     * Normaliza uma ligação interna guardada na notificação.
     *
     * São aceites caminhos internos e endereços HTTP ou HTTPS pertencentes
     * ao domínio configurado para a aplicação. Os endereços absolutos válidos
     * são convertidos para caminhos internos antes de chegarem à vista.
     *
     * @param  mixed  $valor  Ligação recebida.
     * @return string|null Ligação interna segura.
     *
     * @since 2.0.0
     */
    private function normalizarLigacaoInterna(
        mixed $valor,
    ): ?string {
        $ligacao =
            $this->normalizarTexto(
                $valor,
            );

        if (
            $ligacao === null
            || preg_match(
                '/[\x00-\x1F\x7F\\\\]/',
                $ligacao,
            ) === 1
            || preg_match(
                '/\s/u',
                $ligacao,
            ) === 1
        ) {
            return null;
        }

        if (str_starts_with($ligacao, '/')) {
            return str_starts_with($ligacao, '//')
                ? null
                : $ligacao;
        }

        $componentes =
            parse_url(
                $ligacao,
            );

        if (
            ! is_array($componentes)
            || ! isset(
                $componentes['scheme'],
                $componentes['host'],
            )
            || isset(
                $componentes['user'],
                $componentes['pass'],
            )
            || ! in_array(
                mb_strtolower(
                    (string) $componentes['scheme'],
                ),
                [
                    'http',
                    'https',
                ],
                true,
            )
        ) {
            return null;
        }

        $hostAplicacao =
            $this->obterHostAplicacao();

        if (
            $hostAplicacao === null
            || mb_strtolower(
                (string) $componentes['host'],
            ) !== $hostAplicacao
        ) {
            return null;
        }

        $caminho =
            '/'.ltrim(
                (string) ($componentes['path'] ?? ''),
                '/',
            );

        $consulta =
            isset($componentes['query'])
            ? '?'.$componentes['query']
            : '';

        $fragmento =
            isset($componentes['fragment'])
            ? '#'.$componentes['fragment']
            : '';

        return $caminho.$consulta.$fragmento;
    }

    /**
     * Obtém o domínio configurado para a aplicação.
     *
     * @return string|null Domínio normalizado ou nulo.
     *
     * @since 2.0.0
     */
    private function obterHostAplicacao(): ?string
    {
        $enderecoAplicacao =
            config(
                'app.url',
            );

        if (! is_string($enderecoAplicacao)) {
            return null;
        }

        $host =
            parse_url(
                trim(
                    $enderecoAplicacao,
                ),
                PHP_URL_HOST,
            );

        if (
            ! is_string($host)
            || $host === ''
        ) {
            return null;
        }

        return mb_strtolower(
            $host,
        );
    }
}
