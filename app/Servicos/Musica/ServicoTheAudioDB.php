<?php

declare(strict_types=1);

namespace App\Servicos\Musica;

use App\Models\Comum\Ligacao;
use App\Models\Musica\Artista;
use App\Servicos\Integracoes\LimitadorPedidosExternos;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Obtém dados complementares de artistas através do TheAudioDB.
 *
 * A consulta é efetuada através do MBID selecionado no MusicBrainz, evitando
 * correspondências frágeis baseadas apenas no nome do artista.
 *
 * @phpstan-type DadosArtistaTheAudioDB array{
 *     id: int|null,
 *     nome: string|null,
 *     mbid: string|null,
 *     pais: string|null,
 *     codigo_pais: string|null,
 *     ano_inicio: int|null,
 *     ano_fim: int|null,
 *     dissolvido: bool|null,
 *     biografia: string|null,
 *     idioma_biografia: 'pt'|'en'|null,
 *     imagem: string|null,
 *     logo: string|null,
 *     ligacoes: list<array{titulo: string, url: string}>
 * }
 *
 * @since 2.0.0
 */
final class ServicoTheAudioDB
{
    /**
     * Cria o serviço com o limitador partilhado de pedidos externos.
     *
     * @param  LimitadorPedidosExternos  $limitadorPedidos  Coordenador dos
     *                                                      limites de
     *                                                      comunicação.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly LimitadorPedidosExternos $limitadorPedidos,
    ) {}

    /**
     * Obtém um artista através do identificador MusicBrainz.
     *
     * @param  string  $mbid  Identificador MusicBrainz.
     * @return DadosArtistaTheAudioDB|null Artista normalizado ou nulo.
     *
     * @throws RuntimeException Quando o MBID ou a configuração são inválidos,
     *                          ou quando o serviço não pode ser consultado.
     *
     * @since 2.0.0
     */
    public function obterArtistaPorMusicBrainz(
        string $mbid,
    ): ?array {
        $mbid =
            $this->validarMbid(
                $mbid,
            );

        $chave =
            trim(
                $this->obterConfiguracaoTexto(
                    'theaudiodb.api_key',
                    '123',
                ),
            );

        if ($chave === '') {
            throw new RuntimeException(
                'A chave da API do TheAudioDB não está configurada.',
            );
        }

        $resposta =
            $this->executarPedido(
                '/api/v1/json/'
                    .rawurlencode(
                        $chave,
                    )
                    .'/artist-mb.php',
                [
                    'i' => $mbid,
                ],
            );

        $artistas =
            $resposta->json(
                'artists',
            );

        if (
            ! is_array($artistas)
            || $artistas === []
        ) {
            return null;
        }

        foreach ($artistas as $artista) {
            if (! is_array($artista)) {
                continue;
            }

            $identificador =
                $this->obterTexto(
                    $artista['strMusicBrainzID'] ?? null,
                );

            if (
                $identificador === null
                || strcasecmp($identificador, $mbid) !== 0
            ) {
                continue;
            }

            return $this->normalizarArtista(
                $artista,
            );
        }

        return null;
    }

    /**
     * Executa um pedido ao TheAudioDB com controlo global de frequência e
     * repetição perante falhas transitórias.
     *
     * @param  string  $caminho  Caminho relativo da API.
     * @param  array<string, mixed>  $parametros  Parâmetros da consulta.
     * @return Response Resposta HTTP válida.
     *
     * @throws RuntimeException Quando não é possível comunicar com o serviço
     *                          ou este devolve uma resposta de erro.
     *
     * @since 2.0.0
     */
    private function executarPedido(
        string $caminho,
        array $parametros,
    ): Response {
        $enderecoBase =
            rtrim(
                $this->obterConfiguracaoTexto(
                    'theaudiodb.base_url',
                    'https://www.theaudiodb.com',
                ),
                '/',
            );

        $tentativas =
            max(
                1,
                $this->obterConfiguracaoInteira(
                    'theaudiodb.tentativas',
                    2,
                ),
            );

        $intervaloRepeticao =
            max(
                0,
                $this->obterConfiguracaoInteira(
                    'theaudiodb.intervalo_repeticao_ms',
                    500,
                ),
            );

        $intervaloMinimoPedidos =
            max(
                0,
                $this->obterConfiguracaoInteira(
                    'theaudiodb.intervalo_minimo_pedidos_ms',
                    2000,
                ),
            );

        $ultimaResposta = null;

        for (
            $tentativa = 1;
            $tentativa <= $tentativas;
            $tentativa++
        ) {
            $this
                ->limitadorPedidos
                ->aguardar(
                    'theaudiodb',
                    $intervaloMinimoPedidos,
                );

            try {
                $ultimaResposta =
                    Http::acceptJson()
                        ->withoutRedirecting()
                        ->timeout(
                            max(
                                1,
                                $this->obterConfiguracaoInteira(
                                    'theaudiodb.timeout',
                                    10,
                                ),
                            ),
                        )
                        ->get(
                            $enderecoBase.$caminho,
                            $parametros,
                        );

                if (
                    ! in_array(
                        $ultimaResposta->status(),
                        [
                            502,
                            503,
                            504,
                        ],
                        true,
                    )
                ) {
                    break;
                }
            } catch (ConnectionException) {
                /*
                 * A mensagem original pode conter o URL completo
                 * com a chave da API. Não a conservar na cadeia
                 * de exceções reportadas.
                 */
            }

            if (
                $tentativa < $tentativas
                && $intervaloRepeticao > 0
            ) {
                usleep(
                    $intervaloRepeticao
                        * 1000,
                );
            }
        }

        if (! $ultimaResposta instanceof Response) {
            throw new RuntimeException(
                'Não foi possível estabelecer ligação ao TheAudioDB.',
            );
        }

        if ($ultimaResposta->successful()) {
            return $ultimaResposta;
        }

        throw new RuntimeException(
            sprintf(
                'O TheAudioDB devolveu o código HTTP %d.',
                $ultimaResposta->status(),
            ),
        );
    }

    /**
     * Obtém uma configuração textual do TheAudioDB.
     */
    private function obterConfiguracaoTexto(
        string $chave,
        string $predefinido,
    ): string {
        $valor =
            config(
                $chave,
                $predefinido,
            );

        if (! is_string($valor)) {
            throw new RuntimeException(
                sprintf(
                    'A configuração %s do TheAudioDB não é válida.',
                    $chave,
                ),
            );
        }

        return $valor;
    }

    /**
     * Obtém uma configuração inteira do TheAudioDB.
     */
    private function obterConfiguracaoInteira(
        string $chave,
        int $predefinido,
    ): int {
        $valor =
            config(
                $chave,
                $predefinido,
            );

        if (! is_int($valor)) {
            throw new RuntimeException(
                sprintf(
                    'A configuração %s do TheAudioDB não é válida.',
                    $chave,
                ),
            );
        }

        return $valor;
    }

    /**
     * Normaliza a ficha do artista.
     *
     * @param  array<mixed, mixed>  $artista  Ficha original.
     * @return DadosArtistaTheAudioDB Ficha normalizada.
     *
     * @since 2.0.0
     */
    private function normalizarArtista(
        array $artista,
    ): array {
        $biografiaPortugues =
            $this->obterTexto(
                $artista['strBiographyPT'] ?? null,
                true,
            );

        $biografiaIngles =
            $this->obterTexto(
                $artista['strBiographyEN'] ?? null,
                true,
            );

        if (
            $biografiaPortugues !== null
            && ! $this->eBiografiaImportavel($biografiaPortugues)
        ) {
            $biografiaPortugues = null;
        }

        if (
            $biografiaIngles !== null
            && ! $this->eBiografiaImportavel($biografiaIngles)
        ) {
            $biografiaIngles = null;
        }

        $biografia =
            $biografiaPortugues
            ?? $biografiaIngles;

        return [
            'id' => $this->normalizarIdentificadorPositivo(
                $artista['idArtist'] ?? null,
            ),

            'nome' => $this->obterTexto(
                $artista['strArtist'] ?? null,
            ),

            'mbid' => $this->obterTexto(
                $artista['strMusicBrainzID'] ?? null,
            ),

            'pais' => $this->obterTexto(
                $artista['strCountry'] ?? null,
            ),

            'codigo_pais' => $this->obterTexto(
                $artista['strCountryCode'] ?? null,
            ),

            'ano_inicio' => $this->obterAno(
                $artista['intFormedYear'] ?? null,
            ),

            'ano_fim' => $this->obterAno(
                $artista['intDiedYear'] ?? null,
            ),

            'dissolvido' => $this->normalizarBooleano(
                $artista['strDisbanded'] ?? null,
            ),

            'biografia' => $biografia,

            'idioma_biografia' => $biografiaPortugues !== null
                ? 'pt'
                : (
                    $biografiaIngles !== null
                    ? 'en'
                    : null
                ),

            'imagem' => $this->normalizarUrl(
                $artista['strArtistThumb'] ?? null,
            ),

            'logo' => $this->normalizarUrl(
                $artista['strArtistLogo'] ?? null,
            ),

            'ligacoes' => $this->obterLigacoes(
                $artista,
            ),
        ];
    }

    /**
     * Obtém as ligações externas úteis devolvidas pelo TheAudioDB.
     *
     * @param  array<string, mixed>  $artista  Dados do artista.
     * @return list<array{titulo: string, url: string}> Ligações válidas.
     *
     * @since 2.0.0
     */
    private function obterLigacoes(
        array $artista,
    ): array {
        $candidatas = [
            [
                'titulo' => 'Site oficial',

                'url' => $this->normalizarUrl(
                    $artista['strWebsite'] ?? null,
                ),
            ],
            [
                'titulo' => 'Facebook',

                'url' => $this->normalizarLigacaoSocial(
                    $artista['strFacebook'] ?? null,
                    'https://www.facebook.com/',
                ),
            ],
            [
                'titulo' => 'X',

                'url' => $this->normalizarLigacaoSocial(
                    $artista['strTwitter'] ?? null,
                    'https://x.com/',
                ),
            ],
            [
                'titulo' => 'Instagram',

                'url' => $this->normalizarLigacaoSocial(
                    $artista['strInstagram'] ?? null,
                    'https://www.instagram.com/',
                ),
            ],
            [
                'titulo' => 'YouTube',

                'url' => $this->normalizarLigacaoSocial(
                    $artista['strYoutube'] ?? null,
                    'https://www.youtube.com/',
                ),
            ],
            [
                'titulo' => 'TikTok',

                'url' => $this->normalizarLigacaoSocial(
                    $artista['strTikTok'] ?? null,
                    'https://www.tiktok.com/@',
                ),
            ],
        ];

        $ligacoes = [];
        $enderecos = [];

        foreach ($candidatas as $candidata) {
            $endereco =
                $candidata['url'];

            if (
                ! is_string($endereco)
                || $endereco === ''
                || array_key_exists(
                    $endereco,
                    $enderecos,
                )
            ) {
                continue;
            }

            $enderecos[$endereco] =
                true;

            $ligacoes[] = [
                'titulo' => $candidata['titulo'],

                'url' => $endereco,
            ];
        }

        return $ligacoes;
    }

    /**
     * Normaliza uma ligação de rede social.
     *
     * @param  mixed  $valor  URL ou identificador.
     * @param  string  $prefixo  Endereço base da rede.
     * @return string|null URL normalizada.
     *
     * @since 2.0.0
     */
    private function normalizarLigacaoSocial(
        mixed $valor,
        string $prefixo,
    ): ?string {
        $texto =
            $this->obterTexto(
                $valor,
            );

        if (
            $texto === null
            || in_array(
                strtolower(
                    $texto,
                ),
                [
                    '0',
                    '1',
                    'false',
                    'true',
                    'null',
                    'none',
                    'n/a',
                ],
                true,
            )
        ) {
            return null;
        }

        $dominioPlataforma =
            mb_strtolower(
                (string) parse_url(
                    $prefixo,
                    PHP_URL_HOST,
                ),
            );

        $dominioPlataforma = preg_replace(
            '/^www\./',
            '',
            $dominioPlataforma,
        ) ?? $dominioPlataforma;

        $valorMinusculas =
            mb_strtolower(
                $texto,
            );

        $dominiosPermitidos = [$dominioPlataforma];

        if ($dominioPlataforma === 'youtube.com') {
            $dominiosPermitidos[] = 'youtu.be';
        }

        if ($dominioPlataforma === 'x.com') {
            $dominiosPermitidos[] = 'twitter.com';
        }

        if (
            str_starts_with($valorMinusculas, 'http://')
            || str_starts_with($valorMinusculas, 'https://')
            || str_starts_with($valorMinusculas, 'www.')
            || str_contains($texto, '/')
            || in_array($valorMinusculas, $dominiosPermitidos, true)
            || str_ends_with(
                $valorMinusculas,
                '.'.$dominioPlataforma,
            )
        ) {
            $url = $this->normalizarUrl($texto);

            if ($url === null) {
                return null;
            }

            $host = mb_strtolower(
                (string) parse_url($url, PHP_URL_HOST),
            );

            foreach ($dominiosPermitidos as $dominioPermitido) {
                if (
                    $host === $dominioPermitido
                    || str_ends_with($host, '.'.$dominioPermitido)
                ) {
                    return $url;
                }
            }

            return null;
        }

        $identificador =
            trim(
                $texto,
                '@/ ',
            );

        if (
            $identificador === ''
            || mb_strlen(
                $identificador,
            ) < 2
            || preg_match(
                '/\A[\p{L}\p{N}][\p{L}\p{N}._-]*\z/u',
                $identificador,
            ) !== 1
        ) {
            return null;
        }

        if (
            $dominioPlataforma === 'youtube.com'
            && str_starts_with($texto, '@')
        ) {
            $identificador = '@'.$identificador;
        }

        return $this->normalizarUrl(
            $prefixo.$identificador,
        );
    }

    /**
     * Verifica se uma biografia externa respeita o contrato do artista.
     *
     * @since 2.0.0
     */
    private function eBiografiaImportavel(string $biografia): bool
    {
        return preg_match('//u', $biografia) === 1
            && preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $biografia,
            ) !== 1
            && mb_strlen($biografia)
                <= Artista::COMPRIMENTO_MAXIMO_BIOGRAFIA;
    }

    /**
     * Normaliza um endereço HTTP/HTTPS.
     *
     * Aceita igualmente domínios sem esquema, acrescentando HTTPS.
     *
     * @param  mixed  $valor  Valor original.
     * @return string|null URL normalizada.
     *
     * @since 2.0.0
     */
    private function normalizarUrl(
        mixed $valor,
    ): ?string {
        $valor =
            $this->obterTexto(
                $valor,
            );

        if ($valor === null) {
            return null;
        }

        if (
            ! str_starts_with(
                strtolower(
                    $valor,
                ),
                'http://',
            )
            && ! str_starts_with(
                strtolower(
                    $valor,
                ),
                'https://',
            )
        ) {
            if (
                ! str_contains(
                    $valor,
                    '.',
                )
            ) {
                return null;
            }

            $valor =
                'https://'.$valor;
        }

        if (
            filter_var(
                $valor,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            return null;
        }

        if (
            parse_url($valor, PHP_URL_USER) !== null
            || parse_url($valor, PHP_URL_PASS) !== null
        ) {
            return null;
        }

        $esquema =
            strtolower(
                (string) parse_url(
                    $valor,
                    PHP_URL_SCHEME,
                ),
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
        ) {
            return null;
        }

        if (
            mb_strlen($valor) > Ligacao::COMPRIMENTO_MAXIMO_URL
        ) {
            return null;
        }

        return $valor;
    }

    /**
     * Normaliza um identificador externo como inteiro positivo.
     *
     * Rejeita valores não decimais ou superiores ao limite inteiro PHP.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificadorPositivo(
        mixed $valor,
    ): ?int {
        if (
            ! is_string($valor)
            && ! is_int($valor)
        ) {
            return null;
        }

        $texto = (string) $valor;

        if (
            preg_match(
                '/\A[0-9]+\z/',
                $texto,
            ) !== 1
        ) {
            return null;
        }

        $identificador = filter_var(
            ltrim($texto, '0'),
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ],
        );

        return is_int($identificador)
            ? $identificador
            : null;
    }

    /**
     * Obtém texto não vazio.
     *
     * @param  mixed  $valor  Valor original.
     * @return string|null Texto normalizado.
     *
     * @since 2.0.0
     */
    private function obterTexto(
        mixed $valor,
        bool $textoLongo = false,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $valor =
            trim(
                $valor,
            );

        $padraoCaracteresInvalidos = $textoLongo
            ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/'
            : '/[\x00-\x1F\x7F]/';

        if (
            preg_match('//u', $valor) !== 1
            || preg_match($padraoCaracteresInvalidos, $valor) === 1
        ) {
            return null;
        }

        return $valor === ''
            ? null
            : $valor;
    }

    /**
     * Normaliza um ano.
     *
     * @param  mixed  $valor  Valor original.
     * @return int|null Ano.
     *
     * @since 2.0.0
     */
    private function obterAno(
        mixed $valor,
    ): ?int {
        if (
            ! is_string($valor)
            && ! is_int($valor)
        ) {
            return null;
        }

        $valor =
            trim(
                (string) $valor,
            );

        if (
            preg_match(
                '/^\d{4}$/',
                $valor,
            ) !== 1
        ) {
            return null;
        }

        $ano = (int) $valor;

        return $ano >= Artista::ANO_MINIMO_ATIVIDADE
            && $ano <= now()->year
            ? $ano
            : null;
    }

    /**
     * Normaliza valores booleanos textuais.
     *
     * @param  mixed  $valor  Valor original.
     * @return bool|null Valor normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarBooleano(
        mixed $valor,
    ): ?bool {
        if (is_bool($valor)) {
            return $valor;
        }

        if (! is_string($valor)) {
            return null;
        }

        return match (strtolower(
            trim(
                $valor,
            ),
        )) {
            'yes',
            'true',
            '1' => true,

            'no',
            'false',
            '0' => false,

            default => null,
        };
    }

    /**
     * Valida um identificador MusicBrainz.
     *
     * @param  string  $mbid  Identificador original.
     * @return string Identificador normalizado.
     *
     * @throws RuntimeException Quando o identificador não possui formato válido.
     *
     * @since 2.0.0
     */
    private function validarMbid(
        string $mbid,
    ): string {
        $mbid =
            strtolower(
                trim(
                    $mbid,
                ),
            );

        if (
            preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
                $mbid,
            ) !== 1
        ) {
            throw new RuntimeException(
                'O identificador MusicBrainz indicado não é válido.',
            );
        }

        return $mbid;
    }
}
