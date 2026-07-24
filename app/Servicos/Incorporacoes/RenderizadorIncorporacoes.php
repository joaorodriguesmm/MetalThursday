<?php

declare(strict_types=1);

namespace App\Servicos\Incorporacoes;

use App\Enumeracoes\TipoIncorporacao;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Illuminate\Support\HtmlString;

/**
 * Valida e renderiza incorporações associadas às secções.
 *
 * As incorporações do YouTube utilizam o domínio sem cookies e o carregamento
 * tardio nativo do navegador. Não são efetuados pedidos HTTP pelo servidor
 * durante a apresentação da página.
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class RenderizadorIncorporacoes
{
    /**
     * Comprimento máximo de uma ligação persistida.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_LIGACAO = 2048;

    /**
     * Hosts reconhecidos como pertencentes ao YouTube.
     *
     * @var list<string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const HOSTS_YOUTUBE = [
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'music.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
        'youtu.be',
        'www.youtu.be',
    ];

    /**
     * Segmentos utilizados pelo YouTube antes do identificador do vídeo.
     *
     * @var list<string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const SEGMENTOS_VIDEO_YOUTUBE = [
        'embed',
        'shorts',
        'live',
    ];

    /**
     * Renderiza a incorporação e a ligação externa de uma secção.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção apresentada.
     * @return HtmlString Conteúdo HTML validado.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function renderizar(
        SeccaoMetalThursday $seccao,
    ): HtmlString {
        $ligacao =
            $this->normalizarLigacao(
                $seccao->ligacao,
            );

        if ($ligacao === null) {
            return new HtmlString('');
        }

        $tipo =
            $this->obterTipoIncorporacao(
                $seccao->tipo_incorporacao,
            );

        $incorporacao = match ($tipo) {
            TipoIncorporacao::VideoYouTube => $this->renderizarVideoYouTube(
                $ligacao,
            ),

            TipoIncorporacao::ListaReproducaoYouTube => $this->renderizarListaReproducaoYouTube(
                $ligacao,
            ),

            TipoIncorporacao::Ligacao => '',
        };

        $ligacaoExterna =
            $this->renderizarLigacaoExterna(
                $ligacao,
            );

        return new HtmlString(
            $incorporacao
                .$ligacaoExterna,
        );
    }

    /**
     * Obtém as definições utilizadas pela interface para reconhecer ligações.
     *
     * @return list<array{
     *     tipo: string,
     *     etiqueta: string,
     *     expressao_regular: string
     * }> Definições das incorporações reconhecidas.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function definicoesParaJavaScript(): array
    {
        $definicoes = [];

        foreach (
            [
                TipoIncorporacao::VideoYouTube,
                TipoIncorporacao::ListaReproducaoYouTube,
            ] as $tipo
        ) {
            $expressaoRegular =
                $tipo->expressaoRegularJavaScript();

            if (
                ! is_string($expressaoRegular)
                || $expressaoRegular === ''
            ) {
                continue;
            }

            $definicoes[] = [
                'tipo' => $tipo->value,

                'etiqueta' => $tipo->etiqueta(),

                'expressao_regular' => $expressaoRegular,
            ];
        }

        return $definicoes;
    }

    /**
     * Obtém o tipo de incorporação associado à secção.
     *
     * Quando o valor não é reconhecido, é utilizada apenas a ligação externa.
     *
     * @param  mixed  $valor  Valor persistido ou enum já convertido.
     * @return TipoIncorporacao Tipo reconhecido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterTipoIncorporacao(
        mixed $valor,
    ): TipoIncorporacao {
        if ($valor instanceof TipoIncorporacao) {
            return $valor;
        }

        return TipoIncorporacao::tentarCriar(
            $valor,
        ) ?? TipoIncorporacao::Ligacao;
    }

    /**
     * Renderiza um vídeo do YouTube.
     *
     * @param  string  $ligacao  Ligação validada.
     * @return string HTML da incorporação ou texto vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function renderizarVideoYouTube(
        string $ligacao,
    ): string {
        $identificador =
            $this->extrairIdentificadorVideoYouTube(
                $ligacao,
            );

        if ($identificador === null) {
            return '';
        }

        return $this->renderizarIframe(
            sprintf(
                'https://www.youtube-nocookie.com/embed/%s?rel=0',
                rawurlencode(
                    $identificador,
                ),
            ),
            'Vídeo do YouTube',
        );
    }

    /**
     * Renderiza uma lista de reprodução do YouTube.
     *
     * @param  string  $ligacao  Ligação validada.
     * @return string HTML da incorporação ou texto vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function renderizarListaReproducaoYouTube(
        string $ligacao,
    ): string {
        $identificador =
            $this->extrairIdentificadorListaYouTube(
                $ligacao,
            );

        if ($identificador === null) {
            return '';
        }

        return $this->renderizarIframe(
            sprintf(
                'https://www.youtube-nocookie.com/embed/videoseries?list=%s&rel=0',
                rawurlencode(
                    $identificador,
                ),
            ),
            'Lista de reprodução do YouTube',
        );
    }

    /**
     * Renderiza um iframe responsivo.
     *
     * @param  string  $origem  Origem previamente validada.
     * @param  string  $titulo  Título acessível.
     * @return string HTML do iframe.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function renderizarIframe(
        string $origem,
        string $titulo,
    ): string {
        $origemEscapada =
            $this->escaparAtributo(
                $origem,
            );

        $tituloEscapado =
            $this->escaparAtributo(
                $titulo,
            );

        return <<<HTML
<div class="ratio ratio-16x9">
    <iframe
        src="{$origemEscapada}"
        title="{$tituloEscapado}"
        loading="lazy"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        referrerpolicy="strict-origin-when-cross-origin"
        sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"
        allowfullscreen
    ></iframe>
</div>
HTML;
    }

    /**
     * Renderiza o botão da ligação externa.
     *
     * @param  string  $ligacao  Ligação validada.
     * @return string HTML do botão.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function renderizarLigacaoExterna(
        string $ligacao,
    ): string {
        $ligacaoEscapada =
            $this->escaparAtributo(
                $ligacao,
            );

        return <<<HTML
<div class="mt-2">
    <a
        href="{$ligacaoEscapada}"
        target="_blank"
        rel="noopener noreferrer external"
        class="btn btn-sm btn-secondary"
    >
        Abrir ligação externa
    </a>
</div>
HTML;
    }

    /**
     * Valida e normaliza uma ligação HTTP ou HTTPS.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Ligação válida ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function normalizarLigacao(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $ligacao =
            trim(
                $valor,
            );

        if (
            $ligacao === ''
            || mb_strlen($ligacao)
            > self::COMPRIMENTO_MAXIMO_LIGACAO
            || str_contains(
                $ligacao,
                '\\',
            )
            || preg_match(
                '/[\x00-\x1F\x7F]/',
                $ligacao,
            ) === 1
            || filter_var(
                $ligacao,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            return null;
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
        ) {
            return null;
        }

        $esquema =
            mb_strtolower(
                (string) $componentes['scheme'],
            );

        $host =
            trim(
                (string) $componentes['host'],
            );

        if (
            ! in_array(
                $esquema,
                [
                    'http',
                    'https',
                ],
                true,
            )
            || $host === ''
        ) {
            return null;
        }

        return $ligacao;
    }

    /**
     * Extrai o identificador de um vídeo do YouTube.
     *
     * @param  string  $ligacao  Ligação validada.
     * @return string|null Identificador ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function extrairIdentificadorVideoYouTube(
        string $ligacao,
    ): ?string {
        $componentes =
            $this->decomporLigacaoYouTube(
                $ligacao,
            );

        if ($componentes === null) {
            return null;
        }

        if (
            in_array(
                $componentes['host'],
                [
                    'youtu.be',
                    'www.youtu.be',
                ],
                true,
            )
        ) {
            return $this->validarIdentificadorVideo(
                $componentes['segmentos'][0]
                    ?? null,
            );
        }

        $identificadorConsulta =
            $this->validarIdentificadorVideo(
                $componentes['consulta']['v']
                    ?? null,
            );

        if ($identificadorConsulta !== null) {
            return $identificadorConsulta;
        }

        $primeiroSegmento =
            $componentes['segmentos'][0]
            ?? null;

        if (
            ! is_string($primeiroSegmento)
            || ! in_array(
                mb_strtolower(
                    $primeiroSegmento,
                ),
                self::SEGMENTOS_VIDEO_YOUTUBE,
                true,
            )
        ) {
            return null;
        }

        return $this->validarIdentificadorVideo(
            $componentes['segmentos'][1]
                ?? null,
        );
    }

    /**
     * Extrai o identificador de uma lista de reprodução do YouTube.
     *
     * @param  string  $ligacao  Ligação validada.
     * @return string|null Identificador ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function extrairIdentificadorListaYouTube(
        string $ligacao,
    ): ?string {
        $componentes =
            $this->decomporLigacaoYouTube(
                $ligacao,
            );

        if ($componentes === null) {
            return null;
        }

        $identificador =
            $componentes['consulta']['list']
            ?? null;

        if (
            ! is_string($identificador)
            || preg_match(
                '/^[A-Za-z0-9_-]{10,150}$/D',
                $identificador,
            ) !== 1
        ) {
            return null;
        }

        return $identificador;
    }

    /**
     * Decompõe uma ligação pertencente ao YouTube.
     *
     * @param  string  $ligacao  Ligação validada.
     * @return array{
     *     host: string,
     *     segmentos: list<string>,
     *     consulta: array<string, mixed>
     * }|null Componentes reconhecidos ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function decomporLigacaoYouTube(
        string $ligacao,
    ): ?array {
        $componentes =
            parse_url(
                $ligacao,
            );

        if (
            ! is_array($componentes)
            || ! isset($componentes['host'])
        ) {
            return null;
        }

        $host =
            mb_strtolower(
                rtrim(
                    (string) $componentes['host'],
                    '.',
                ),
            );

        if (
            ! in_array(
                $host,
                self::HOSTS_YOUTUBE,
                true,
            )
        ) {
            return null;
        }

        $caminho =
            trim(
                (string) (
                    $componentes['path']
                    ?? ''
                ),
                '/',
            );

        $segmentos =
            $caminho === ''
            ? []
            : array_values(
                array_filter(
                    explode(
                        '/',
                        $caminho,
                    ),
                    static fn (
                        string $segmento,
                    ): bool => $segmento !== '',
                ),
            );

        $consulta = [];

        parse_str(
            (string) (
                $componentes['query']
                ?? ''
            ),
            $consulta,
        );

        return [
            'host' => $host,

            'segmentos' => $segmentos,

            'consulta' => $consulta,
        ];
    }

    /**
     * Valida um identificador de vídeo do YouTube.
     *
     * @param  mixed  $identificador  Valor recebido.
     * @return string|null Identificador válido ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarIdentificadorVideo(
        mixed $identificador,
    ): ?string {
        if (
            ! is_string($identificador)
            || preg_match(
                '/^[A-Za-z0-9_-]{11}$/D',
                $identificador,
            ) !== 1
        ) {
            return null;
        }

        return $identificador;
    }

    /**
     * Escapa um valor utilizado num atributo HTML.
     *
     * @param  string  $valor  Valor original.
     * @return string Valor escapado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function escaparAtributo(
        string $valor,
    ): string {
        return htmlspecialchars(
            $valor,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );
    }
}
