<?php

declare(strict_types=1);

namespace App\Servicos\Musica;

use App\Enumeracoes\EstadoAtividadeArtista;
use RuntimeException;

/**
 * Agrega informação de várias fontes externas para propor o preenchimento
 * do perfil de um artista.
 *
 * O MusicBrainz constitui a fonte principal de identificação e o TheAudioDB
 * complementa os dados quando possui uma ficha para o MBID selecionado.
 *
 * O identificador Discogs pode ser extraído de uma relação confirmada pelo
 * MusicBrainz, mas esta fase não consulta a API do Discogs.
 *
 * Nenhum dado é persistido por este serviço.
 *
 * @since 2.0.0
 */
final class ServicoImportacaoArtista
{
    /**
     * Categorias de ligações que podem ser importadas automaticamente.
     *
     * Cada categoria é importada no máximo uma vez para evitar perfis
     * preenchidos com dezenas de ligações redundantes provenientes das bases
     * externas.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    private const CATEGORIAS_LIGACOES_PERMITIDAS = [
        'site_oficial',
        'bandcamp',
        'youtube',
        'spotify',
        'apple_music',
        'metal_archives',
    ];

    /**
     * Cria o serviço.
     *
     * @param  ServicoMusicBrainz  $musicBrainz  Integração MusicBrainz.
     * @param  ServicoTheAudioDB  $theAudioDB  Integração TheAudioDB.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoMusicBrainz $musicBrainz,
        private readonly ServicoTheAudioDB $theAudioDB,
    ) {}

    /**
     * Pesquisa artistas na fonte principal.
     *
     * @param  string  $termo  Termo de pesquisa.
     * @return list<array<string, mixed>> Resultados normalizados.
     *
     * @throws RuntimeException Quando não é possível consultar o MusicBrainz.
     *
     * @since 2.0.0
     */
    public function pesquisar(
        string $termo,
    ): array {
        return $this
            ->musicBrainz
            ->pesquisarArtistas(
                $termo,
            );
    }

    /**
     * Obtém uma proposta de preenchimento para um artista MusicBrainz.
     *
     * O MusicBrainz prevalece nos dados estruturados. O TheAudioDB pode
     * complementar a biografia, a imagem e algumas ligações. O perfil Discogs
     * é apenas associado através do identificador publicado pelo MusicBrainz;
     * a API Discogs não é consultada nesta fase.
     *
     * @param  string  $mbid  Identificador MusicBrainz selecionado.
     * @return array<string, mixed> Proposta agregada.
     *
     * @throws RuntimeException Quando a ficha principal do MusicBrainz não pode
     *                          ser obtida ou validada.
     *
     * @since 2.0.0
     */
    public function obterProposta(
        string $mbid,
    ): array {
        $musicBrainz =
            $this
                ->musicBrainz
                ->obterArtista(
                    $mbid,
                );

        $theAudioDB =
            $this->obterTheAudioDBSeguro(
                $musicBrainz['mbid'],
            );

        $biografia =
            $this->obterBiografiaTheAudioDB(
                $theAudioDB,
            );

        $imagem =
            $this->obterImagemTheAudioDB(
                $theAudioDB,
            );

        $discogsId =
            $musicBrainz['discogs_id']
            ?? null;

        return [
            'musicbrainz_id' => $musicBrainz['mbid'],

            'discogs_id' => is_int($discogsId)
                && $discogsId > 0
                ? $discogsId
                : null,

            'nome' => $musicBrainz['nome'],

            'tipo' => $musicBrainz['tipo'],

            'desambiguacao' => $musicBrainz['desambiguacao'],

            'origem' => [
                'codigo_pais' => $musicBrainz['codigo_pais']
                    ?? $theAudioDB['codigo_pais']
                    ?? null,

                'area' => $musicBrainz['area'],

                'area_inicio' => $musicBrainz['area_inicio'],

                'descricao_audiodb' => $theAudioDB['pais']
                    ?? null,
            ],

            'ano_inicio_atividade' => $musicBrainz['ano_inicio']
                ?? $theAudioDB['ano_inicio']
                ?? null,

            'ano_fim_atividade' => $musicBrainz['ano_fim']
                ?? $theAudioDB['ano_fim']
                ?? null,

            'estado_atividade' => $this->obterEstadoAtividade(
                $musicBrainz,
                $theAudioDB,
            ),

            'biografia' => $biografia,

            'imagem' => $imagem,

            'ligacoes' => $this->obterLigacoes(
                $musicBrainz,
                $theAudioDB,
            ),

            'url_musicbrainz' => $musicBrainz['url_musicbrainz'],

            'url_discogs' => is_int($discogsId)
                && $discogsId > 0
                ? 'https://www.discogs.com/artist/'.$discogsId
                : null,

            'fontes' => [
                'musicbrainz' => true,

                'theaudiodb' => $theAudioDB !== null,

                'biografia' => $biografia !== null
                    ? 'theaudiodb'
                    : null,

                'imagem' => $imagem !== null
                    ? 'theaudiodb'
                    : null,
            ],
        ];
    }

    /**
     * Consulta o TheAudioDB sem tornar esta fonte complementar obrigatória.
     *
     * Uma indisponibilidade do TheAudioDB não impede que o utilizador continue
     * a importar os restantes dados provenientes do MusicBrainz.
     *
     * @param  string  $mbid  Identificador MusicBrainz.
     * @return array<string, mixed>|null Resultado normalizado ou nulo.
     *
     * @since 2.0.0
     */
    private function obterTheAudioDBSeguro(
        string $mbid,
    ): ?array {
        try {
            return $this
                ->theAudioDB
                ->obterArtistaPorMusicBrainz(
                    $mbid,
                );
        } catch (RuntimeException $excecao) {
            report(
                $excecao,
            );

            return null;
        }
    }

    /**
     * Obtém a biografia proposta pelo TheAudioDB.
     *
     * @param  array<string, mixed>|null  $theAudioDB  Dados TheAudioDB.
     * @return string|null Biografia normalizada ou nulo.
     *
     * @since 2.0.0
     */
    private function obterBiografiaTheAudioDB(
        ?array $theAudioDB,
    ): ?string {
        $biografia =
            $theAudioDB['biografia']
            ?? null;

        if (
            ! is_string(
                $biografia,
            )
            || trim(
                $biografia,
            ) === ''
        ) {
            return null;
        }

        return trim(
            $biografia,
        );
    }

    /**
     * Obtém a imagem proposta pelo TheAudioDB.
     *
     * @param  array<string, mixed>|null  $theAudioDB  Dados TheAudioDB.
     * @return string|null Endereço externo da imagem ou nulo.
     *
     * @since 2.0.0
     */
    private function obterImagemTheAudioDB(
        ?array $theAudioDB,
    ): ?string {
        $imagem =
            $theAudioDB['imagem']
            ?? null;

        if (
            ! is_string(
                $imagem,
            )
            || trim(
                $imagem,
            ) === ''
        ) {
            return null;
        }

        return trim(
            $imagem,
        );
    }

    /**
     * Determina o estado de atividade apenas a partir de informação explícita.
     *
     * A ausência de informação não é interpretada automaticamente como
     * atividade atual.
     *
     * @param  array<string, mixed>  $musicBrainz  Dados MusicBrainz.
     * @param  array<string, mixed>|null  $theAudioDB  Dados TheAudioDB.
     * @return string|null Estado proposto.
     *
     * @since 2.0.0
     */
    private function obterEstadoAtividade(
        array $musicBrainz,
        ?array $theAudioDB,
    ): ?string {
        if (
            ($musicBrainz['terminado'] ?? null) === true
            || ($theAudioDB['dissolvido'] ?? null) === true
        ) {
            return EstadoAtividadeArtista::Terminado->value;
        }

        if (
            ($musicBrainz['terminado'] ?? null) === false
            || ($theAudioDB['dissolvido'] ?? null) === false
        ) {
            return EstadoAtividadeArtista::Ativo->value;
        }

        return null;
    }

    /**
     * Agrega apenas as ligações externas consideradas relevantes.
     *
     * São aceites o site oficial, Bandcamp, YouTube, Spotify, Apple Music e
     * Metal Archives. Cada categoria aparece no máximo uma vez. MusicBrainz e
     * Discogs são excluídos porque possuem associações próprias através dos
     * respetivos identificadores.
     *
     * @param  array<string, mixed>  $musicBrainz  Dados MusicBrainz.
     * @param  array<string, mixed>|null  $theAudioDB  Dados TheAudioDB.
     * @return list<array{titulo: string, url: string}> Ligações propostas.
     *
     * @since 2.0.0
     */
    private function obterLigacoes(
        array $musicBrainz,
        ?array $theAudioDB,
    ): array {
        $candidatas = [];

        foreach (
            $musicBrainz['relacoes']
                ?? [] as $relacao
        ) {
            if (
                ! is_array(
                    $relacao,
                )
                || ! is_string(
                    $relacao['url']
                        ?? null,
                )
            ) {
                continue;
            }

            $candidatas[] = [
                'url' => $relacao['url'],

                'tipo_musicbrainz' => is_string(
                    $relacao['tipo']
                        ?? null,
                )
                    ? $relacao['tipo']
                    : null,
            ];
        }

        foreach (
            $theAudioDB['ligacoes']
                ?? [] as $ligacao
        ) {
            if (
                ! is_array(
                    $ligacao,
                )
                || ! is_string(
                    $ligacao['url']
                        ?? null,
                )
            ) {
                continue;
            }

            $candidatas[] = [
                'url' => $ligacao['url'],

                'tipo_musicbrainz' => null,
            ];
        }

        return $this->filtrarLigacoesRelevantes(
            $candidatas,
        );
    }

    /**
     * Filtra e ordena as ligações externas pela lista branca da aplicação.
     *
     * A primeira ligação válida de cada categoria prevalece. A ordem final é
     * estável e definida por CATEGORIAS_LIGACOES_PERMITIDAS.
     *
     * @param  list<array{
     *     url: mixed,
     *     tipo_musicbrainz: mixed
     * }>  $candidatas  Ligações candidatas.
     * @return list<array{titulo: string, url: string}> Ligações filtradas.
     *
     * @since 2.0.0
     */
    private function filtrarLigacoesRelevantes(
        array $candidatas,
    ): array {
        $porCategoria = [];

        foreach ($candidatas as $candidata) {
            $endereco =
                $candidata['url']
                ?? null;

            if (! is_string($endereco)) {
                continue;
            }

            $endereco =
                trim(
                    $endereco,
                );

            if (! $this->eUrlHttpValida($endereco)) {
                continue;
            }

            $tipoMusicBrainz =
                is_string(
                    $candidata['tipo_musicbrainz']
                        ?? null,
                )
                ? trim(
                    $candidata['tipo_musicbrainz'],
                )
                : null;

            $categoria =
                $this->obterCategoriaLigacao(
                    $endereco,
                    $tipoMusicBrainz,
                );

            if (
                $categoria === null
                || array_key_exists(
                    $categoria,
                    $porCategoria,
                )
            ) {
                continue;
            }

            $porCategoria[$categoria] = [
                'titulo' => $this->obterTituloCategoriaLigacao(
                    $categoria,
                ),

                'url' => $endereco,
            ];
        }

        $resultado = [];

        foreach (
            self::CATEGORIAS_LIGACOES_PERMITIDAS as $categoria
        ) {
            if (
                ! array_key_exists(
                    $categoria,
                    $porCategoria,
                )
            ) {
                continue;
            }

            $resultado[] =
                $porCategoria[$categoria];
        }

        return $resultado;
    }

    /**
     * Determina a categoria funcional de uma ligação externa.
     *
     * O site oficial é identificado preferencialmente pelo tipo estruturado
     * devolvido pelo MusicBrainz. As restantes categorias são reconhecidas
     * pelo domínio.
     *
     * @param  string  $endereco  Endereço externo.
     * @param  string|null  $tipoMusicBrainz  Tipo da relação MusicBrainz.
     * @return string|null Categoria permitida ou nulo.
     *
     * @since 2.0.0
     */
    private function obterCategoriaLigacao(
        string $endereco,
        ?string $tipoMusicBrainz,
    ): ?string {
        if (
            $tipoMusicBrainz !== null
            && mb_strtolower(
                $tipoMusicBrainz,
            ) === 'official homepage'
        ) {
            return 'site_oficial';
        }

        $host =
            $this->obterHostNormalizado(
                $endereco,
            );

        if ($host === '') {
            return null;
        }

        return match (true) {
            $this->hostCorresponde(
                $host,
                'bandcamp.com',
            ) => 'bandcamp',

            $this->hostCorresponde(
                $host,
                'youtube.com',
            ),
            $this->hostCorresponde(
                $host,
                'youtu.be',
            ) => 'youtube',

            $this->hostCorresponde(
                $host,
                'spotify.com',
            ) => 'spotify',

            $this->hostCorresponde(
                $host,
                'music.apple.com',
            ),
            $this->hostCorresponde(
                $host,
                'itunes.apple.com',
            ) => 'apple_music',

            $this->hostCorresponde(
                $host,
                'metal-archives.com',
            ) => 'metal_archives',

            default => null,
        };
    }

    /**
     * Obtém o título apresentado para uma categoria de ligação.
     *
     * @param  string  $categoria  Categoria interna.
     * @return string Título apresentado ao utilizador.
     *
     * @since 2.0.0
     */
    private function obterTituloCategoriaLigacao(
        string $categoria,
    ): string {
        return match ($categoria) {
            'site_oficial' => 'Site oficial',

            'bandcamp' => 'Bandcamp',

            'youtube' => 'YouTube',

            'spotify' => 'Spotify',

            'apple_music' => 'Apple Music',

            'metal_archives' => 'Metal Archives',

            default => 'Ligação externa',
        };
    }

    /**
     * Obtém o domínio normalizado de uma ligação externa.
     *
     * @param  string  $endereco  Endereço externo.
     * @return string Domínio em minúsculas e sem prefixo "www.".
     *
     * @since 2.0.0
     */
    private function obterHostNormalizado(
        string $endereco,
    ): string {
        $host =
            mb_strtolower(
                (string) parse_url(
                    $endereco,
                    PHP_URL_HOST,
                ),
            );

        return preg_replace(
            '/^www\./',
            '',
            $host,
        ) ?? $host;
    }

    /**
     * Confirma que um domínio corresponde ao domínio esperado ou a um dos
     * respetivos subdomínios.
     *
     * @param  string  $host  Domínio normalizado.
     * @param  string  $dominio  Domínio esperado.
     * @return bool Verdadeiro quando existe correspondência.
     *
     * @since 2.0.0
     */
    private function hostCorresponde(
        string $host,
        string $dominio,
    ): bool {
        return $host === $dominio
            || str_ends_with(
                $host,
                '.'.$dominio,
            );
    }

    /**
     * Confirma que um endereço utiliza HTTP ou HTTPS e possui formato válido.
     *
     * @param  string  $endereco  Endereço externo.
     * @return bool Verdadeiro quando o endereço é aceite.
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
            mb_strtolower(
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
