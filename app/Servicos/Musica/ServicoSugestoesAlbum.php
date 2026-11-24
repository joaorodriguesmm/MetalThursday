<?php

declare(strict_types=1);

namespace App\Servicos\Musica;

use App\Models\Musica\Artista;
use App\Servicos\Integracoes\LimitadorPedidosExternos;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Obtém ligações de um álbum identificado com segurança.
 *
 * Nenhuma resposta externa é persistida. Os serviços consultados são fixos e
 * os URLs sugeridos são validados antes de chegar ao formulário.
 *
 * @since 2.0.0
 */
final class ServicoSugestoesAlbum
{
    public function __construct(
        private readonly LimitadorPedidosExternos $limitador,
    ) {}

    /**
     * @param  array<string, mixed>  $discogs  Edição Discogs já normalizada.
     * @return array{descricao: string|null, descricao_fonte: string|null, ligacoes: list<array{etiqueta: string, url: string}>}
     */
    public function obter(Artista $artista, array $discogs): array
    {
        $resultado = [
            'descricao' => null,
            'descricao_fonte' => null,
            'ligacoes' => [],
        ];

        // Nunca associar sugestões de outro músico a um artista selecionado.
        if (! $this->correspondeArtista($artista, $discogs)) {
            return $resultado;
        }

        $titulo = $discogs['titulo'] ?? null;
        if (! is_string($titulo) || trim($titulo) === '') {
            return $resultado;
        }

        $ano = $discogs['ano_original'] ?? $discogs['ano'] ?? null;
        $ano = is_int($ano) && $ano >= 1000 ? $ano : null;
        $mbid = $artista->musicbrainz_id;

        if (is_string($mbid) && Str::isUuid($mbid)) {
            try {
                $grupo = $this->pesquisarGrupoMusicBrainz($mbid, $titulo, $ano);

                if ($grupo !== null) {
                    $resultado['ligacoes'] = $this->obterLigacoesMusicBrainz($grupo);

                }
            } catch (Throwable) {
                // A importação Discogs continua possível sem o MusicBrainz.
            }
        }

        try {
            $apple = $this->pesquisarAppleMusic($artista->nome, $titulo, $ano);
            if ($apple !== null) {
                $resultado['ligacoes'][] = [
                    'etiqueta' => 'Apple Music — álbum',
                    'url' => $apple,
                ];
            }
        } catch (Throwable) {
            // Serviço opcional, sem efeitos na persistência do lançamento.
        }

        $vistos = [];
        $resultado['ligacoes'] = array_values(array_filter(
            $resultado['ligacoes'],
            static function (array $ligacao) use (&$vistos): bool {
                $url = $ligacao['url'];
                if (isset($vistos[$url])) {
                    return false;
                }
                $vistos[$url] = true;

                return true;
            },
        ));

        return $resultado;
    }

    /**
     * @param  array<string, mixed>  $discogs
     */
    private function correspondeArtista(Artista $artista, array $discogs): bool
    {
        $creditos = $discogs['artistas'] ?? null;
        if (! is_array($creditos) || $creditos === []) {
            return false;
        }

        foreach ($creditos as $credito) {
            if (! is_array($credito)) {
                continue;
            }

            if ($artista->discogs_id !== null) {
                if (($credito['discogs_id'] ?? null) === $artista->discogs_id) {
                    return true;
                }

                continue;
            }

            if (
                is_string($credito['nome'] ?? null)
                && $this->normalizar($credito['nome']) === $this->normalizar($artista->nome)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pesquisa um grupo de lançamento associado ao MBID exato do artista.
     * Abandona resultados ambíguos, com ano diferente ou créditos incorretos.
     *
     * @return array<string, mixed>|null
     */
    private function pesquisarGrupoMusicBrainz(string $artistaMbid, string $titulo, ?int $ano): ?array
    {
        $agent = config('musicbrainz.user_agent');
        if (! is_string($agent) || trim($agent) === '') {
            return null;
        }

        $tituloPesquisa = addcslashes(trim($titulo), '\\"');
        $pesquisa = $this->pedirMusicBrainz(
            '/ws/2/release-group/',
            [
                'query' => sprintf('releasegroup:"%s" AND arid:%s', $tituloPesquisa, $artistaMbid),
                'fmt' => 'json',
                'limit' => 10,
            ],
        );

        $candidatos = $pesquisa['release-groups'] ?? null;
        if (! is_array($candidatos)) {
            return null;
        }

        $aceites = [];
        foreach ($candidatos as $candidato) {
            if (! is_array($candidato)) {
                continue;
            }

            $id = $candidato['id'] ?? null;
            $tituloCandidato = $candidato['title'] ?? null;

            if (
                ! is_string($id)
                || ! Str::isUuid($id)
                || ! is_string($tituloCandidato)
                || $this->normalizar($tituloCandidato) !== $this->normalizar($titulo)
            ) {
                continue;
            }

            $creditos = $candidato['artist-credit'] ?? null;
            if (! is_array($creditos)) {
                continue;
            }

            $identidadeConfirmada = false;
            foreach ($creditos as $credito) {
                if (! is_array($credito)) {
                    continue;
                }

                $artistaCredito = $credito['artist'] ?? null;
                $idArtistaCredito = is_array($artistaCredito)
                    ? ($artistaCredito['id'] ?? null)
                    : null;

                if (
                    is_string($idArtistaCredito)
                    && strcasecmp($idArtistaCredito, $artistaMbid) === 0
                ) {
                    $identidadeConfirmada = true;
                    break;
                }
            }

            if (! $identidadeConfirmada) {
                continue;
            }

            $data = $candidato['first-release-date'] ?? null;
            if (
                $ano !== null
                && is_string($data)
                && preg_match('/^\d{4}/', $data) === 1
                && abs((int) substr($data, 0, 4) - $ano) > 1
            ) {
                continue;
            }

            $aceites[$id] = $candidato;
        }

        if (count($aceites) !== 1) {
            return null;
        }

        $id = array_key_first($aceites);
        if (! is_string($id)) {
            return null;
        }

        $grupo = $this->pedirMusicBrainz(
            '/ws/2/release-group/'.$id,
            [
                'inc' => 'url-rels releases artist-credits',
                'fmt' => 'json',
            ],
        );

        $idGrupo = $grupo['id'] ?? null;

        return is_string($idGrupo) && strcasecmp($idGrupo, $id) === 0
            ? $grupo
            : null;
    }

    /**
     * @param  array<string, scalar>  $parametros
     * @return array<string, mixed>
     */
    private function pedirMusicBrainz(string $caminho, array $parametros): array
    {
        $intervalo = config('musicbrainz.intervalo_minimo_pedidos_ms', 1100);
        $agente = config('musicbrainz.user_agent');
        $origem = config('musicbrainz.base_url', 'https://musicbrainz.org');

        if (! is_string($agente) || trim($agente) === '') {
            return [];
        }

        if (! is_string($origem) || trim($origem) === '') {
            return [];
        }

        $this->limitador->aguardar(
            'musicbrainz',
            is_int($intervalo) ? $intervalo : 1100,
        );

        $resposta = Http::acceptJson()
            ->withoutRedirecting()
            ->withHeaders(['User-Agent' => $agente])
            ->timeout(8)
            ->get(
                rtrim($origem, '/').$caminho,
                $parametros,
            );

        return $resposta->successful() && is_array($resposta->json())
            ? $resposta->json()
            : [];
    }

    /**
     * @param  array<string, mixed>  $grupo
     * @return list<array{etiqueta: string, url: string}>
     */
    private function obterLigacoesMusicBrainz(array $grupo): array
    {
        $ligacoes = [];
        $relacoes = $grupo['relations'] ?? [];
        if (! is_array($relacoes)) {
            return [];
        }

        foreach ($relacoes as $relacao) {
            if (! is_array($relacao)) {
                continue;
            }
            $referencia = $relacao['url'] ?? null;
            $url = is_array($referencia) ? ($referencia['resource'] ?? null) : null;
            $etiqueta = is_string($url) ? $this->classificarUrlAlbum($url) : null;
            if ($etiqueta !== null) {
                $ligacoes[] = ['etiqueta' => $etiqueta, 'url' => $url];
            }
        }

        // Streaming costuma ser associado às edições digitais, não ao grupo.
        // Evita multiplicar pedidos: apenas a primeira edição é consultada.
        $edicoes = $grupo['releases'] ?? [];
        if (is_array($edicoes)) {
            foreach ($edicoes as $edicao) {
                $id = is_array($edicao) ? ($edicao['id'] ?? null) : null;
                if (! is_string($id) || ! Str::isUuid($id)) {
                    continue;
                }
                try {
                    $detalhes = $this->pedirMusicBrainz(
                        '/ws/2/release/'.$id,
                        ['inc' => 'url-rels', 'fmt' => 'json'],
                    );
                    $relacoesEdicao = $detalhes['relations'] ?? null;
                    if (is_array($relacoesEdicao)) {
                        foreach ($relacoesEdicao as $relacao) {
                            if (! is_array($relacao)) {
                                continue;
                            }

                            $referencia = $relacao['url'] ?? null;
                            $url = is_array($referencia) ? ($referencia['resource'] ?? null) : null;
                            $etiqueta = is_string($url) ? $this->classificarUrlAlbum($url) : null;

                            if ($etiqueta !== null) {
                                $ligacoes[] = ['etiqueta' => $etiqueta, 'url' => $url];
                            }
                        }
                    }
                } catch (Throwable) {
                    // A pesquisa de outras relações não é obrigatória.
                }
                break;
            }
        }

        return $ligacoes;
    }

    private function classificarUrlAlbum(string $url): ?string
    {
        if (strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $caminho = (string) parse_url($url, PHP_URL_PATH);
        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return null;
        }

        if ($host === 'open.spotify.com' && preg_match('~^/(?:intl-[a-z-]+/)?album/[A-Za-z0-9]{22}/?$~', $caminho)) {
            return 'Spotify — álbum';
        }
        if ($host === 'music.apple.com' && preg_match('~/album/[^/]+/[0-9]+/?$~', $caminho)) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $parametros);
            if (! isset($parametros['i'])) {
                return 'Apple Music — álbum';
            }
        }
        if (preg_match('~(?:^|\.)bandcamp\.com$~', $host) && preg_match('~^/album/[^/]+/?$~', $caminho)) {
            return 'Bandcamp — álbum';
        }

        return null;
    }

    private function pesquisarAppleMusic(string $artista, string $titulo, ?int $ano): ?string
    {
        $intervalo = config('itunes.intervalo_minimo_pedidos_ms', 1000);
        $this->limitador->aguardar(
            'itunes',
            is_int($intervalo) ? $intervalo : 1000,
        );
        $resposta = Http::acceptJson()->withoutRedirecting()->timeout(8)->get(
            'https://itunes.apple.com/search',
            [
                'term' => $artista.' '.$titulo,
                'entity' => 'album',
                'media' => 'music',
                'country' => 'PT',
                'limit' => 25,
            ],
        );
        if (! $resposta->successful()) {
            return null;
        }
        $resultados = $resposta->json('results');
        if (! is_array($resultados)) {
            return null;
        }

        foreach ($resultados as $resultado) {
            if (! is_array($resultado)) {
                continue;
            }

            $artistaResultado = $resultado['artistName'] ?? null;
            $tituloResultado = $resultado['collectionName'] ?? null;

            if (
                ($resultado['wrapperType'] ?? null) !== 'collection'
                || ! is_string($artistaResultado)
                || ! is_string($tituloResultado)
                || $this->normalizar($artistaResultado) !== $this->normalizar($artista)
                || $this->normalizar($tituloResultado) !== $this->normalizar($titulo)
            ) {
                continue;
            }

            $data = $resultado['releaseDate'] ?? null;
            if ($ano !== null && is_string($data) && preg_match('/^\d{4}/', $data) === 1 && abs((int) substr($data, 0, 4) - $ano) > 1) {
                continue;
            }

            $url = $resultado['collectionViewUrl'] ?? null;
            if (! is_string($url) || strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false) {
                continue;
            }
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            if (
                parse_url($url, PHP_URL_SCHEME) === 'https'
                && in_array($host, ['music.apple.com', 'itunes.apple.com'], true)
                && str_contains((string) parse_url($url, PHP_URL_PATH), '/album/')
            ) {
                return $url;
            }
        }

        return null;
    }

    private function normalizar(string $texto): string
    {
        return mb_strtolower(Str::squish($texto));
    }
}
