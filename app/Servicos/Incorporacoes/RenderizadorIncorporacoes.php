<?php

declare(strict_types=1);

namespace App\Servicos\Incorporacoes;

use App\Enumeracoes\PlataformaLigacao;
use App\Models\MetalThursday\LigacaoSeccaoMetalThursday;
use Illuminate\Support\HtmlString;

/**
 * Valida e renderiza incorporações associadas às secções.
 *
 * As incorporações do YouTube utilizam o domínio sem cookies e o carregamento
 * tardio nativo do navegador. Não são efetuados pedidos HTTP pelo servidor
 * durante a apresentação da página.
 *
 * @since 2.0.0
 */
final class RenderizadorIncorporacoes
{
    /**
     * Hosts reconhecidos como pertencentes ao YouTube.
     *
     * @var list<string>
     *
     * @since 2.0.0
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
     */
    private const SEGMENTOS_VIDEO_YOUTUBE = [
        'embed',
        'shorts',
        'live',
    ];

    /**
     * Comprimento exato de um identificador de vídeo do YouTube.
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_IDENTIFICADOR_VIDEO = 11;

    /**
     * Comprimento mínimo de um identificador de lista de reprodução.
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_MINIMO_IDENTIFICADOR_LISTA = 10;

    /**
     * Comprimento máximo de um identificador de lista de reprodução.
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_MAXIMO_IDENTIFICADOR_LISTA = 150;

    /**
     * Renderiza uma ligação pertencente à nova coleção de uma secção.
     *
     * A incorporação concreta é disponibilizada para YouTube, YouTube Music,
     * Spotify e Apple Music quando o URL possui um formato reconhecido.
     *
     * @param  LigacaoSeccaoMetalThursday  $ligacaoSecao  Ligação apresentada.
     * @return HtmlString Conteúdo HTML validado.
     *
     * @since 2.0.0
     */
    public function renderizarLigacao(
        LigacaoSeccaoMetalThursday $ligacaoSecao,
    ): HtmlString {
        $ligacao = $this->normalizarLigacao(
            $ligacaoSecao->url,
        );

        if ($ligacao === null) {
            return new HtmlString('');
        }

        $incorporacao = '';

        if ($ligacaoSecao->incorporar) {
            if ($ligacaoSecao->plataforma === PlataformaLigacao::YouTube) {
                $incorporacao =
                    $this->renderizarVideoYouTube(
                        $ligacao,
                    );

                if ($incorporacao === '') {
                    $incorporacao =
                        $this->renderizarListaReproducaoYouTube(
                            $ligacao,
                        );
                }
            } elseif ($ligacaoSecao->plataforma === PlataformaLigacao::Spotify) {
                $incorporacao =
                    $this->renderizarSpotify(
                        $ligacao,
                    );
            } elseif ($ligacaoSecao->plataforma === PlataformaLigacao::AppleMusic) {
                $incorporacao =
                    $this->renderizarAppleMusic(
                        $ligacao,
                    );
            }
        }

        return new HtmlString(
            $incorporacao
                .$this->renderizarLigacaoExterna(
                    $ligacao,
                    $this->obterEtiquetaLigacaoExterna(
                        $ligacaoSecao,
                    ),
                ),
        );
    }

    /**
     * Renderiza conteúdo incorporável do Spotify.
     *
     * Apenas ligações canónicas de `open.spotify.com` são transformadas em
     * incorporações. Ligações curtas e formatos desconhecidos continuam a ser
     * apresentados como ligação externa.
     *
     * @param  string  $ligacao  Ligação validada.
     * @return string HTML da incorporação ou texto vazio.
     *
     * @since 2.0.0
     */
    private function renderizarSpotify(
        string $ligacao,
    ): string {
        $conteudo =
            $this->extrairConteudoSpotify(
                $ligacao,
            );

        if ($conteudo === null) {
            return '';
        }

        $origem = sprintf(
            'https://open.spotify.com/embed/%s/%s',
            rawurlencode(
                $conteudo['tipo'],
            ),
            rawurlencode(
                $conteudo['identificador'],
            ),
        );

        $origemEscapada =
            $this->escaparAtributo(
                $origem,
            );

        return <<<HTML
<div class="w-100">
    <iframe
        src="{$origemEscapada}"
        title="Spotify"
        width="100%"
        height="352"
        loading="lazy"
        frameborder="0"
        allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
        referrerpolicy="strict-origin-when-cross-origin"
        allowfullscreen
    ></iframe>
</div>
HTML;
    }

    /**
     * Extrai o tipo e o identificador de uma ligação canónica do Spotify.
     *
     * São aceites os tipos atualmente suportados pelo gerador de incorporações
     * do Spotify. O prefixo regional `intl-*` e o segmento `embed` são
     * tolerados para permitir URLs produzidos pelo próprio serviço.
     *
     * @param  string  $ligacao  Ligação validada.
     * @return array{tipo: string, identificador: string}|null Conteúdo ou nulo.
     *
     * @since 2.0.0
     */
    private function extrairConteudoSpotify(
        string $ligacao,
    ): ?array {
        $componentes = parse_url(
            $ligacao,
        );

        if (
            ! is_array($componentes)
            || ! isset(
                $componentes['host'],
                $componentes['path'],
            )
            || mb_strtolower(
                (string) $componentes['host'],
            ) !== 'open.spotify.com'
        ) {
            return null;
        }

        $segmentos = array_values(
            array_filter(
                explode(
                    '/',
                    trim(
                        (string) $componentes['path'],
                        '/',
                    ),
                ),
                static fn (string $segmento): bool => $segmento !== '',
            ),
        );

        if (
            isset($segmentos[0])
            && preg_match(
                '/^intl-[a-z]{2}(?:-[a-z]{2})?$/i',
                $segmentos[0],
            ) === 1
        ) {
            array_shift(
                $segmentos,
            );
        }

        if (($segmentos[0] ?? null) === 'embed') {
            array_shift(
                $segmentos,
            );
        }

        if (count($segmentos) !== 2) {
            return null;
        }

        [$tipo, $identificador] = $segmentos;

        if (
            ! in_array(
                $tipo,
                [
                    'album',
                    'artist',
                    'episode',
                    'playlist',
                    'show',
                    'track',
                ],
                true,
            )
            || preg_match(
                '/^[A-Za-z0-9]{10,64}$/',
                $identificador,
            ) !== 1
        ) {
            return null;
        }

        return [
            'tipo' => $tipo,
            'identificador' => $identificador,
        ];
    }

    /**
     * Renderiza conteúdo incorporável do Apple Music.
     *
     * O leitor utiliza o domínio `embed.music.apple.com`. Apenas álbuns,
     * listas de reprodução e músicas com caminhos reconhecidos são
     * transformados; os restantes URLs continuam como ligações externas.
     *
     * @param  string  $ligacao  Ligação validada.
     * @return string HTML da incorporação ou texto vazio.
     *
     * @since 2.0.0
     */
    private function renderizarAppleMusic(
        string $ligacao,
    ): string {
        $conteudo =
            $this->extrairConteudoAppleMusic(
                $ligacao,
            );

        if ($conteudo === null) {
            return '';
        }

        $origem =
            'https://embed.music.apple.com'
            .$conteudo['caminho'];

        if ($conteudo['identificador_musica'] !== null) {
            $origem .= '?i='.rawurlencode(
                $conteudo['identificador_musica'],
            );
        }

        $origemEscapada =
            $this->escaparAtributo(
                $origem,
            );

        $altura = $conteudo['musica']
            ? 175
            : 450;

        return <<<HTML
<div class="w-100">
    <iframe
        src="{$origemEscapada}"
        title="Apple Music"
        width="100%"
        height="{$altura}"
        loading="lazy"
        frameborder="0"
        allow="autoplay *; encrypted-media *; clipboard-write"
        referrerpolicy="strict-origin-when-cross-origin"
        sandbox="allow-forms allow-popups allow-same-origin allow-scripts allow-top-navigation-by-user-activation"
    ></iframe>
</div>
HTML;
    }

    /**
     * Extrai conteúdo incorporável de uma ligação Apple Music.
     *
     * São aceites caminhos com código de país e conteúdo `album`, `playlist`
     * ou `song`. O identificador final é validado de acordo com o tipo. Num
     * álbum, o parâmetro `i` identifica uma música individual.
     *
     * @param  string  $ligacao  Ligação validada.
     * @return array{
     *     caminho: string,
     *     identificador_musica: string|null,
     *     musica: bool
     * }|null Conteúdo incorporável ou nulo.
     *
     * @since 2.0.0
     */
    private function extrairConteudoAppleMusic(
        string $ligacao,
    ): ?array {
        $componentes = parse_url(
            $ligacao,
        );

        if (
            ! is_array($componentes)
            || ! isset(
                $componentes['host'],
                $componentes['path'],
            )
            || mb_strtolower(
                (string) $componentes['host'],
            ) !== 'music.apple.com'
        ) {
            return null;
        }

        $caminho = (string) $componentes['path'];

        $segmentos = array_values(
            array_filter(
                explode(
                    '/',
                    trim(
                        $caminho,
                        '/',
                    ),
                ),
                static fn (string $segmento): bool => $segmento !== '',
            ),
        );

        if (
            count($segmentos) < 3
            || count($segmentos) > 4
            || preg_match(
                '/^[a-z]{2}$/i',
                $segmentos[0],
            ) !== 1
        ) {
            return null;
        }

        $tipo = mb_strtolower(
            $segmentos[1],
        );

        if (
            ! in_array(
                $tipo,
                [
                    'album',
                    'playlist',
                    'song',
                ],
                true,
            )
        ) {
            return null;
        }

        $identificador = $segmentos[array_key_last(
            $segmentos,
        )];

        $identificadorValido = match ($tipo) {
            'album',
            'song' => preg_match(
                '/^[0-9]+$/',
                $identificador,
            ) === 1,

            'playlist' => preg_match(
                '/^pl\.[A-Za-z0-9._-]+$/',
                $identificador,
            ) === 1,

            default => false,
        };

        if (! $identificadorValido) {
            return null;
        }

        $identificadorMusica = null;

        if (isset($componentes['query'])) {
            $parametros = [];

            parse_str(
                (string) $componentes['query'],
                $parametros,
            );

            if (array_key_exists(
                'i',
                $parametros,
            )) {
                $valor = $parametros['i'];

                if (
                    $tipo !== 'album'
                    || ! is_string($valor)
                    || preg_match(
                        '/^[0-9]+$/',
                        $valor,
                    ) !== 1
                ) {
                    return null;
                }

                $identificadorMusica = $valor;
            }
        }

        return [
            'caminho' => $caminho,
            'identificador_musica' => $identificadorMusica,
            'musica' => $tipo === 'song'
                || $identificadorMusica !== null,
        ];
    }

    /**
     * Renderiza um vídeo do YouTube.
     *
     * @param  string  $ligacao  Ligação validada.
     * @return string HTML da incorporação ou texto vazio.
     *
     * @since 2.0.0
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
     * A origem e o título são escapados antes de serem introduzidos nos
     * atributos HTML.
     *
     * @param  string  $origem  Origem previamente validada.
     * @param  string  $titulo  Título acessível.
     * @return string HTML do iframe.
     *
     * @since 2.0.0
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
     */
    private function renderizarLigacaoExterna(
        string $ligacao,
        string $etiqueta = 'Abrir ligação externa',
    ): string {
        $ligacaoEscapada =
            $this->escaparAtributo(
                $ligacao,
            );

        $etiquetaEscapada =
            $this->escaparAtributo(
                $etiqueta,
            );

        return <<<HTML
<div class="mt-2">
    <a
        href="{$ligacaoEscapada}"
        target="_blank"
        rel="noopener noreferrer external"
        class="btn btn-sm btn-secondary"
    >
        {$etiquetaEscapada}
    </a>
</div>
HTML;
    }

    /**
     * Obtém a etiqueta apresentada no botão de uma nova ligação.
     *
     * @param  LigacaoSeccaoMetalThursday  $ligacao  Ligação apresentada.
     * @return string Etiqueta segura a apresentar.
     *
     * @since 2.0.0
     */
    private function obterEtiquetaLigacaoExterna(
        LigacaoSeccaoMetalThursday $ligacao,
    ): string {
        if ($ligacao->plataforma !== PlataformaLigacao::Outro) {
            return 'Abrir no '.$ligacao->plataforma->nome();
        }

        $etiqueta = $ligacao->etiqueta;

        if (
            ! is_string($etiqueta)
            || preg_match(
                '//u',
                $etiqueta,
            ) !== 1
            || trim(
                $etiqueta,
            ) === ''
        ) {
            return 'Abrir ligação externa';
        }

        return trim(
            $etiqueta,
        );
    }

    /**
     * Valida e normaliza uma ligação HTTP ou HTTPS.
     *
     * Esta validação é defensiva. A ligação já deve ter sido validada pelo
     * atributo definitivo do modelo {@see LigacaoSeccaoMetalThursday}, mas o serviço
     * nunca produz HTML com base num valor que não tenha confirmado.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Ligação válida ou nula.
     *
     * @since 2.0.0
     */
    private function normalizarLigacao(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        if (
            preg_match(
                '//u',
                $valor,
            ) !== 1
        ) {
            return null;
        }

        $ligacao = trim(
            $valor,
        );

        if (
            $ligacao === ''
            || mb_strlen(
                $ligacao,
            ) > LigacaoSeccaoMetalThursday::COMPRIMENTO_MAXIMO_URL
            || str_contains(
                $ligacao,
                '\\',
            )
            || preg_match(
                '/[\x00-\x20\x7F]/',
                $ligacao,
            ) === 1
            || filter_var(
                $ligacao,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            return null;
        }

        $componentes = parse_url(
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
            )
            || isset(
                $componentes['pass'],
            )
        ) {
            return null;
        }

        $esquema = mb_strtolower(
            (string) $componentes['scheme'],
        );

        $host = trim(
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
     * São reconhecidas ligações curtas, parâmetros `v` e os caminhos
     * `embed`, `shorts` e `live`.
     *
     * @param  string  $ligacao  Ligação validada.
     * @return string|null Identificador ou nulo.
     *
     * @since 2.0.0
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

        if (! is_string($identificador)) {
            return null;
        }

        $comprimento = strlen(
            $identificador,
        );

        if (
            $comprimento
            < self::COMPRIMENTO_MINIMO_IDENTIFICADOR_LISTA
            || $comprimento
            > self::COMPRIMENTO_MAXIMO_IDENTIFICADOR_LISTA
            || preg_match(
                '/\A[A-Za-z0-9_-]+\z/',
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
     * }|null Componentes reconhecidos ou nulos.
     *
     * @since 2.0.0
     */
    private function decomporLigacaoYouTube(
        string $ligacao,
    ): ?array {
        $componentes = parse_url(
            $ligacao,
        );

        if (
            ! is_array($componentes)
            || ! isset(
                $componentes['host'],
            )
        ) {
            return null;
        }

        $host = mb_strtolower(
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

        $caminho = trim(
            (string) (
                $componentes['path']
                ?? ''
            ),
            '/',
        );

        $segmentos = $caminho === ''
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
     */
    private function validarIdentificadorVideo(
        mixed $identificador,
    ): ?string {
        if (
            ! is_string($identificador)
            || strlen(
                $identificador,
            ) !== self::COMPRIMENTO_IDENTIFICADOR_VIDEO
            || preg_match(
                '/\A[A-Za-z0-9_-]+\z/',
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
