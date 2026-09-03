<?php

declare(strict_types=1);

namespace App\Servicos\Musica;

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
     * @return array<string, mixed>|null Artista normalizado ou nulo.
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
                (string) config(
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

        $artista =
            $artistas[0] ?? null;

        if (! is_array($artista)) {
            return null;
        }

        return $this->normalizarArtista(
            $artista,
        );
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
                (string) config(
                    'theaudiodb.base_url',
                    'https://www.theaudiodb.com',
                ),
                '/',
            );

        $tentativas =
            max(
                1,
                (int) config(
                    'theaudiodb.tentativas',
                    2,
                ),
            );

        $intervaloRepeticao =
            max(
                0,
                (int) config(
                    'theaudiodb.intervalo_repeticao_ms',
                    500,
                ),
            );

        $intervaloMinimoPedidos =
            max(
                0,
                (int) config(
                    'theaudiodb.intervalo_minimo_pedidos_ms',
                    2000,
                ),
            );

        $ultimaResposta = null;
        $ultimaExcecao = null;

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
                        ->timeout(
                            max(
                                1,
                                (int) config(
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
                            429,
                            502,
                            503,
                            504,
                        ],
                        true,
                    )
                ) {
                    break;
                }
            } catch (ConnectionException $excecao) {
                $ultimaExcecao =
                    $excecao;
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
                previous: $ultimaExcecao,
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
     * Normaliza a ficha do artista.
     *
     * @param  array<string, mixed>  $artista  Ficha original.
     * @return array<string, mixed> Ficha normalizada.
     *
     * @since 2.0.0
     */
    private function normalizarArtista(
        array $artista,
    ): array {
        $biografiaPortugues =
            $this->obterTexto(
                $artista['strBiographyPT'] ?? null,
            );

        $biografiaIngles =
            $this->obterTexto(
                $artista['strBiographyEN'] ?? null,
            );

        $biografia =
            $biografiaPortugues
            ?? $biografiaIngles;

        return [
            'id' => is_numeric(
                $artista['idArtist'] ?? null,
            )
                ? (int) $artista['idArtist']
                : null,

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

        $url =
            $this->normalizarUrl(
                $texto,
            );

        if ($url !== null) {
            return $url;
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
        ) {
            return null;
        }

        return $this->normalizarUrl(
            $prefixo.$identificador,
        );
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

        return $valor;
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
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $valor =
            trim(
                $valor,
            );

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

        return (int) $valor;
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
