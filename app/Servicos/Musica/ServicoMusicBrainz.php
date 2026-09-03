<?php

declare(strict_types=1);

namespace App\Servicos\Musica;

use App\Servicos\Integracoes\LimitadorPedidosExternos;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Consulta e normaliza informação de artistas disponibilizada pelo
 * MusicBrainz.
 *
 * O serviço não persiste dados. A aplicação decide posteriormente quais os
 * valores propostos ao utilizador e quais os dados efetivamente guardados.
 *
 * @since 2.0.0
 */
final class ServicoMusicBrainz
{
    /**
     * Número máximo de resultados devolvidos numa pesquisa.
     *
     * @since 2.0.0
     */
    private const LIMITE_RESULTADOS =
        10;

    /**
     * Comprimento máximo do termo enviado ao MusicBrainz.
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_MAXIMO_PESQUISA =
        100;

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
     * Pesquisa artistas pelo respetivo nome.
     *
     * @param  string  $termo  Nome pesquisado.
     * @return list<array<string, mixed>> Artistas encontrados.
     *
     * @throws RuntimeException Quando a integração não está configurada ou o
     *                          MusicBrainz não pode ser consultado.
     *
     * @since 2.0.0
     */
    public function pesquisarArtistas(
        string $termo,
    ): array {
        $termoNormalizado =
            $this->normalizarTermoPesquisa(
                $termo,
            );

        if ($termoNormalizado === '') {
            return [];
        }

        $resposta =
            $this->executarPedido(
                '/ws/2/artist/',
                [
                    'query' => sprintf(
                        'artist:"%s"',
                        $this->escaparConsulta(
                            $termoNormalizado,
                        ),
                    ),

                    'fmt' => 'json',

                    'limit' => self::LIMITE_RESULTADOS,
                ],
            );

        $artistas =
            $resposta->json(
                'artists',
                [],
            );

        if (! is_array($artistas)) {
            return [];
        }

        $resultados = [];

        foreach ($artistas as $artista) {
            if (! is_array($artista)) {
                continue;
            }

            $normalizado =
                $this->normalizarArtista(
                    $artista,
                );

            if ($normalizado === null) {
                continue;
            }

            $resultados[] =
                $normalizado;
        }

        return $resultados;
    }

    /**
     * Obtém a ficha estruturada de um artista pelo respetivo MBID.
     *
     * São também solicitadas as relações URL, permitindo identificar páginas
     * oficiais, redes sociais e identificadores noutras bases de dados.
     *
     * @param  string  $mbid  Identificador MusicBrainz.
     * @return array<string, mixed> Artista normalizado.
     *
     * @throws RuntimeException Quando o MBID é inválido ou o MusicBrainz não
     *                          devolve uma ficha válida.
     *
     * @since 2.0.0
     */
    public function obterArtista(
        string $mbid,
    ): array {
        $mbidNormalizado =
            $this->validarMbid(
                $mbid,
            );

        $resposta =
            $this->executarPedido(
                '/ws/2/artist/'.$mbidNormalizado,
                [
                    'inc' => 'url-rels',

                    'fmt' => 'json',
                ],
            );

        $dados =
            $resposta->json();

        if (! is_array($dados)) {
            throw new RuntimeException(
                'O MusicBrainz devolveu uma resposta de artista inválida.',
            );
        }

        $artista =
            $this->normalizarArtista(
                $dados,
            );

        if ($artista === null) {
            throw new RuntimeException(
                'O MusicBrainz não devolveu um artista válido.',
            );
        }

        return $artista;
    }

    /**
     * Executa um pedido ao MusicBrainz com controlo global de frequência e
     * repetição perante indisponibilidades transitórias.
     *
     * O intervalo mínimo entre pedidos é coordenado pela cache partilhada da
     * aplicação. As repetições por falha transitória continuam sujeitas ao
     * mesmo limite, pelo que nunca contornam a frequência configurada.
     *
     * @param  string  $caminho  Caminho relativo da API.
     * @param  array<string, mixed>  $parametros  Parâmetros da consulta.
     * @return Response Resposta HTTP válida.
     *
     * @throws RuntimeException Quando a configuração é inválida, não é possível
     *                          estabelecer comunicação ou a API devolve um erro.
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
                    'musicbrainz.base_url',
                    'https://musicbrainz.org',
                ),
                '/',
            );

        $userAgent =
            trim(
                (string) config(
                    'musicbrainz.user_agent',
                    '',
                ),
            );

        if ($userAgent === '') {
            throw new RuntimeException(
                'O User-Agent do MusicBrainz não está configurado.',
            );
        }

        $tentativas =
            max(
                1,
                (int) config(
                    'musicbrainz.tentativas',
                    3,
                ),
            );

        $intervaloRepeticao =
            max(
                0,
                (int) config(
                    'musicbrainz.intervalo_repeticao_ms',
                    1000,
                ),
            );

        $intervaloMinimoPedidos =
            max(
                0,
                (int) config(
                    'musicbrainz.intervalo_minimo_pedidos_ms',
                    1000,
                ),
            );

        $ultimaExcecao = null;
        $ultimaResposta = null;

        for (
            $tentativa = 1;
            $tentativa <= $tentativas;
            $tentativa++
        ) {
            $this
                ->limitadorPedidos
                ->aguardar(
                    'musicbrainz',
                    $intervaloMinimoPedidos,
                );

            try {
                $ultimaResposta =
                    Http::acceptJson()
                        ->withHeaders([
                            'User-Agent' => $userAgent,
                        ])
                        ->timeout(
                            max(
                                1,
                                (int) config(
                                    'musicbrainz.timeout',
                                    10,
                                ),
                            ),
                        )
                        ->get(
                            $enderecoBase.$caminho,
                            $parametros,
                        );

                if (
                    ! $this->deveRepetir(
                        $ultimaResposta,
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
                'Não foi possível estabelecer ligação ao MusicBrainz.',
                previous: $ultimaExcecao,
            );
        }

        if ($ultimaResposta->successful()) {
            return $ultimaResposta;
        }

        throw match ($ultimaResposta->status()) {
            429 => new RuntimeException(
                'O MusicBrainz atingiu temporariamente o limite de pedidos.',
            ),

            503 => new RuntimeException(
                'O MusicBrainz está temporariamente indisponível.',
            ),

            default => new RuntimeException(
                sprintf(
                    'O MusicBrainz devolveu o código HTTP %d.',
                    $ultimaResposta->status(),
                ),
            ),
        };
    }

    /**
     * Determina se uma resposta deve ser repetida.
     *
     * @param  Response  $resposta  Resposta recebida.
     * @return bool Verdadeiro quando a falha é considerada transitória.
     *
     * @since 2.0.0
     */
    private function deveRepetir(
        Response $resposta,
    ): bool {
        return in_array(
            $resposta->status(),
            [
                429,
                502,
                503,
                504,
            ],
            true,
        );
    }

    /**
     * Normaliza uma ficha de artista do MusicBrainz.
     *
     * @param  array<string, mixed>  $artista  Dados originais.
     * @return array<string, mixed>|null Artista normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarArtista(
        array $artista,
    ): ?array {
        $mbid =
            $this->obterTexto(
                $artista['id'] ?? null,
            );

        $nome =
            $this->obterTexto(
                $artista['name'] ?? null,
            );

        if (
            $mbid === null
            || $nome === null
            || ! $this->eMbidValido(
                $mbid,
            )
        ) {
            return null;
        }

        $inicio =
            $this->obterTexto(
                data_get(
                    $artista,
                    'life-span.begin',
                ),
            );

        $fim =
            $this->obterTexto(
                data_get(
                    $artista,
                    'life-span.end',
                ),
            );

        $terminado =
            data_get(
                $artista,
                'life-span.ended',
            );

        if (! is_bool($terminado)) {
            $terminado = null;
        }

        $relacoes =
            $this->normalizarRelacoes(
                $artista['relations'] ?? [],
            );

        return [
            'mbid' => strtolower(
                $mbid,
            ),

            'nome' => $nome,

            'pontuacao' => is_numeric(
                $artista['score'] ?? null,
            )
                ? (int) $artista['score']
                : null,

            'tipo' => $this->obterTexto(
                $artista['type'] ?? null,
            ),

            'desambiguacao' => $this->obterTexto(
                $artista['disambiguation'] ?? null,
            ),

            'codigo_pais' => $this->obterTexto(
                $artista['country'] ?? null,
            ),

            'area' => $this->obterTexto(
                data_get(
                    $artista,
                    'area.name',
                ),
            ),

            'area_inicio' => $this->obterTexto(
                data_get(
                    $artista,
                    'begin-area.name',
                ),
            ),

            'inicio' => $inicio,

            'fim' => $fim,

            'ano_inicio' => $this->obterAno(
                $inicio,
            ),

            'ano_fim' => $this->obterAno(
                $fim,
            ),

            'terminado' => $terminado,

            'relacoes' => $relacoes,

            'discogs_id' => $this->obterDiscogsId(
                $relacoes,
            ),

            'url_musicbrainz' => 'https://musicbrainz.org/artist/'.strtolower(
                $mbid,
            ),
        ];
    }

    /**
     * Normaliza as relações URL de um artista.
     *
     * @param  mixed  $relacoes  Relações originais.
     * @return list<array{tipo: string|null, url: string}> Relações válidas.
     *
     * @since 2.0.0
     */
    private function normalizarRelacoes(
        mixed $relacoes,
    ): array {
        if (! is_array($relacoes)) {
            return [];
        }

        $normalizadas = [];
        $enderecosEncontrados = [];

        foreach ($relacoes as $relacao) {
            if (! is_array($relacao)) {
                continue;
            }

            $endereco =
                $this->obterTexto(
                    data_get(
                        $relacao,
                        'url.resource',
                    ),
                );

            if (
                $endereco === null
                || ! $this->eUrlHttpValida(
                    $endereco,
                )
                || array_key_exists(
                    $endereco,
                    $enderecosEncontrados,
                )
            ) {
                continue;
            }

            $enderecosEncontrados[$endereco] =
                true;

            $normalizadas[] = [
                'tipo' => $this->obterTexto(
                    $relacao['type'] ?? null,
                ),

                'url' => $endereco,
            ];
        }

        return $normalizadas;
    }

    /**
     * Obtém o identificador Discogs a partir das relações MusicBrainz.
     *
     * @param  list<array{tipo: string|null, url: string}>  $relacoes  Relações.
     * @return int|null Identificador Discogs.
     *
     * @since 2.0.0
     */
    private function obterDiscogsId(
        array $relacoes,
    ): ?int {
        foreach ($relacoes as $relacao) {
            if (
                preg_match(
                    '~^https?://(?:www\.)?discogs\.com/(?:[a-z]{2}/)?artist/(\d+)(?:[-/?#]|$)~i',
                    $relacao['url'],
                    $correspondencias,
                ) !== 1
            ) {
                continue;
            }

            $identificador =
                (int) $correspondencias[1];

            if ($identificador > 0) {
                return $identificador;
            }
        }

        return null;
    }

    /**
     * Normaliza o termo pesquisado.
     *
     * @param  string  $termo  Termo original.
     * @return string Termo normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarTermoPesquisa(
        string $termo,
    ): string {
        $termo =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $termo,
                ),
            ) ?? '';

        return mb_substr(
            $termo,
            0,
            self::COMPRIMENTO_MAXIMO_PESQUISA,
        );
    }

    /**
     * Escapa caracteres especiais da expressão de pesquisa.
     *
     * @param  string  $valor  Valor original.
     * @return string Valor escapado.
     *
     * @since 2.0.0
     */
    private function escaparConsulta(
        string $valor,
    ): string {
        return str_replace(
            [
                '\\',
                '"',
            ],
            [
                '\\\\',
                '\\"',
            ],
            $valor,
        );
    }

    /**
     * Valida e normaliza um MBID.
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

        if (! $this->eMbidValido($mbid)) {
            throw new RuntimeException(
                'O identificador MusicBrainz indicado não é válido.',
            );
        }

        return $mbid;
    }

    /**
     * Verifica a estrutura de um MBID.
     *
     * @param  string  $mbid  Identificador.
     * @return bool Verdadeiro quando possui formato UUID válido.
     *
     * @since 2.0.0
     */
    private function eMbidValido(
        string $mbid,
    ): bool {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $mbid,
        ) === 1;
    }

    /**
     * Obtém um ano a partir de uma data MusicBrainz.
     *
     * @param  string|null  $valor  Data ou ano.
     * @return int|null Ano encontrado.
     *
     * @since 2.0.0
     */
    private function obterAno(
        ?string $valor,
    ): ?int {
        if (
            $valor === null
            || preg_match(
                '/^(\d{4})(?:-|$)/',
                $valor,
                $correspondencias,
            ) !== 1
        ) {
            return null;
        }

        return (int) $correspondencias[1];
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
     * Confirma que um endereço utiliza HTTP ou HTTPS.
     *
     * @param  string  $endereco  Endereço.
     * @return bool Verdadeiro quando o endereço é válido.
     *
     * @since 2.0.0
     */
    private function eUrlHttpValida(
        string $endereco,
    ): bool {
        if (
            filter_var(
                $endereco,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            return false;
        }

        $esquema =
            strtolower(
                (string) parse_url(
                    $endereco,
                    PHP_URL_SCHEME,
                ),
            );

        return in_array(
            $esquema,
            [
                'http',
                'https',
            ],
            true,
        );
    }
}
