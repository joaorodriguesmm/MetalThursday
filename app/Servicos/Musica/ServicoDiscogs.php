<?php

declare(strict_types=1);

namespace App\Servicos\Musica;

use App\Enumeracoes\TipoLancamento;
use App\Servicos\Integracoes\LimitadorPedidosExternos;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Consulta e normaliza informação de lançamentos disponibilizada pelo Discogs.
 *
 * O serviço não persiste dados. A aplicação decide posteriormente quais os
 * valores obtidos que devem ser utilizados.
 *
 * @since 2.0.0
 */
final class ServicoDiscogs
{
    /**
     * Número máximo de resultados devolvidos numa pesquisa.
     *
     * @since 2.0.0
     */
    private const LIMITE_RESULTADOS =
        10;

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
     * Pesquisa edições concretas no catálogo Discogs.
     *
     * @param  string  $termo  Termo de pesquisa.
     * @param  string|null  $artista  Artista pelo qual restringir a pesquisa.
     * @return list<array{
     *     discogs_release_id: int,
     *     titulo: string,
     *     ano: int|null,
     *     pais: string|null,
     *     formatos: list<string>
     * }> Edições encontradas.
     *
     * @throws RuntimeException Quando a integração não está configurada ou o
     *                          Discogs não pode ser consultado.
     *
     * @since 2.0.0
     */
    public function pesquisarLancamentos(
        string $termo,
        ?string $artista = null,
    ): array {
        $termo =
            trim(
                $termo,
            );

        if ($termo === '') {
            return [];
        }

        $artista =
            $this->normalizarTextoOpcional(
                $artista,
            );

        $parametros = [
            'q' => $termo,
            'type' => 'release',
            'per_page' => self::LIMITE_RESULTADOS,
        ];

        if ($artista !== null) {
            $parametros['artist'] =
                $artista;
        }

        $resposta =
            $this->executarPedido(
                '/database/search',
                $parametros,
                true,
            );

        $resultados =
            $resposta->json(
                'results',
                [],
            );

        if (! is_array($resultados)) {
            return [];
        }

        $normalizados = [];

        foreach ($resultados as $resultado) {
            if (! is_array($resultado)) {
                continue;
            }

            $identificador =
                $resultado['id']
                ?? null;

            $tipo =
                $resultado['type']
                ?? null;

            $titulo =
                $resultado['title']
                ?? null;

            if (
                ! is_int($identificador)
                || $identificador < 1
                || $tipo !== 'release'
                || ! is_string($titulo)
                || trim($titulo) === ''
            ) {
                continue;
            }

            $ano =
                $this->normalizarAno(
                    $resultado['year']
                        ?? null,
                );

            $pais =
                $this->normalizarTextoOpcional(
                    $resultado['country']
                        ?? null,
                );

            $formatos =
                $this->normalizarFormatos(
                    $resultado['format']
                        ?? null,
                );

            $normalizados[] = [
                'discogs_release_id' => $identificador,

                'titulo' => trim(
                    $titulo,
                ),

                'ano' => $ano,

                'pais' => $pais,

                'formatos' => $formatos,
            ];
        }

        return $normalizados;
    }

    /**
     * Obtém uma edição concreta pelo identificador Discogs.
     *
     * @param  int  $identificador  Identificador da Release.
     * @return array<string, mixed> Lançamento normalizado.
     *
     * @throws RuntimeException Quando o identificador é inválido ou o Discogs
     *                          não devolve uma edição válida.
     *
     * @since 2.0.0
     */
    public function obterLancamento(
        int $identificador,
    ): array {
        if ($identificador < 1) {
            throw new RuntimeException(
                'O identificador Discogs do lançamento não é válido.',
            );
        }

        $resposta =
            $this->executarPedido(
                '/releases/'.$identificador,
            );

        $dados =
            $resposta->json();

        if (! is_array($dados)) {
            throw new RuntimeException(
                'O Discogs devolveu uma resposta de lançamento inválida.',
            );
        }

        $discogsReleaseId =
            $dados['id']
            ?? null;

        $titulo =
            $dados['title']
            ?? null;

        if (
            ! is_int($discogsReleaseId)
            || $discogsReleaseId < 1
            || ! is_string($titulo)
            || trim($titulo) === ''
        ) {
            throw new RuntimeException(
                'O Discogs não devolveu um lançamento válido.',
            );
        }

        $formatos =
            $this->normalizarFormatos(
                $dados['formats']
                    ?? null,
            );

        return [
            'discogs_release_id' => $discogsReleaseId,

            'titulo' => trim(
                $titulo,
            ),

            'ano' => $this->normalizarAno(
                $dados['year']
                    ?? null,
            ),

            'ano_original' => $this->obterAnoOriginal(
                $dados['master_id']
                    ?? null,
                $dados['year']
                    ?? null,
            ),

            'tipo_sugerido' => $this->sugerirTipoLancamento(
                $formatos,
            ),

            'pais' => $this->normalizarTextoOpcional(
                $dados['country']
                    ?? null,
            ),

            'formatos' => $formatos,

            'artistas' => $this->normalizarArtistas(
                $dados['artists']
                    ?? [],
            ),

            'faixas' => $this->normalizarFaixas(
                $dados['tracklist']
                    ?? [],
            ),
        ];
    }

    /**
     * Obtém o melhor ano disponível para pré-preencher o lançamento.
     *
     * O ano do Master continua a ter prioridade por representar a edição
     * original. Quando o Master não existe, falha ou não possui um ano válido,
     * é utilizado o ano da Release como sugestão editável pelo utilizador.
     *
     * @param  mixed  $identificadorMaster  Identificador Master recebido.
     * @param  mixed  $anoRelease  Ano devolvido pela Release.
     * @return int|null Melhor ano disponível ou nulo.
     *
     * @since 2.0.0
     */
    private function obterAnoOriginal(
        mixed $identificadorMaster,
        mixed $anoRelease,
    ): ?int {
        $anoReleaseNormalizado =
            $this->normalizarAno(
                $anoRelease,
            );

        if (
            ! is_int($identificadorMaster)
            || $identificadorMaster < 1
        ) {
            return $anoReleaseNormalizado;
        }

        try {
            $resposta =
                $this->executarPedido(
                    '/masters/'.$identificadorMaster,
                );
        } catch (RuntimeException) {
            return $anoReleaseNormalizado;
        }

        $dados =
            $resposta->json();

        if (! is_array($dados)) {
            return $anoReleaseNormalizado;
        }

        return $this->normalizarAno(
            $dados['year']
                ?? null,
        ) ?? $anoReleaseNormalizado;
    }

    /**
     * Sugere o tipo interno de lançamento a partir dos formatos Discogs.
     *
     * Só são consideradas descrições inequívocas. Formatos físicos como LP,
     * CD ou Vinyl, isoladamente, não permitem inferir o tipo editorial.
     *
     * @param  list<string>  $formatos  Formatos normalizados da Release.
     * @return string|null Valor da enumeração sugerido ou nulo.
     *
     * @since 2.0.0
     */
    private function sugerirTipoLancamento(
        array $formatos,
    ): ?string {
        $normalizados =
            array_map(
                static fn (string $formato): string => strtolower(
                    trim(
                        $formato,
                    ),
                ),
                $formatos,
            );

        $sugestoes = [
            'soundtrack' => TipoLancamento::BandaSonora,
            'live' => TipoLancamento::AlbumAoVivo,
            'compilation' => TipoLancamento::Compilacao,
            'demo' => TipoLancamento::Demo,
            'split' => TipoLancamento::Split,
            'ep' => TipoLancamento::EP,
            'single' => TipoLancamento::Single,
            'album' => TipoLancamento::AlbumEstudio,
        ];

        foreach ($sugestoes as $formato => $tipo) {
            if (
                in_array(
                    $formato,
                    $normalizados,
                    true,
                )
            ) {
                return $tipo->value;
            }
        }

        return null;
    }

    /**
     * Normaliza um ano devolvido pelo Discogs.
     *
     * @param  mixed  $valor  Valor original.
     * @return int|null Ano válido ou nulo.
     *
     * @since 2.0.0
     */
    private function normalizarAno(
        mixed $valor,
    ): ?int {
        if (! is_numeric($valor)) {
            return null;
        }

        $ano =
            (int) $valor;

        return $ano > 0
            ? $ano
            : null;
    }

    /**
     * Normaliza um texto opcional devolvido pelo Discogs.
     *
     * @param  mixed  $valor  Valor original.
     * @return string|null Texto normalizado ou nulo.
     *
     * @since 2.0.0
     */
    private function normalizarTextoOpcional(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $valor =
            trim(
                $valor,
            );

        return $valor !== ''
            ? $valor
            : null;
    }

    /**
     * Normaliza os formatos associados a um resultado Discogs.
     *
     * São suportados tanto os formatos simplificados devolvidos pela pesquisa
     * como a estrutura detalhada presente numa Release concreta.
     *
     * @param  mixed  $valor  Formatos originais.
     * @return list<string> Formatos válidos.
     *
     * @since 2.0.0
     */
    private function normalizarFormatos(
        mixed $valor,
    ): array {
        if (! is_array($valor)) {
            return [];
        }

        $formatos = [];

        foreach ($valor as $formato) {
            if (is_string($formato)) {
                $normalizado =
                    $this->normalizarTextoOpcional(
                        $formato,
                    );

                if ($normalizado !== null) {
                    $formatos[] =
                        $normalizado;
                }

                continue;
            }

            if (! is_array($formato)) {
                continue;
            }

            $nome =
                $this->normalizarTextoOpcional(
                    $formato['name']
                        ?? null,
                );

            if ($nome !== null) {
                $formatos[] =
                    $nome;
            }

            $descricoes =
                $formato['descriptions']
                ?? [];

            if (! is_array($descricoes)) {
                continue;
            }

            foreach ($descricoes as $descricao) {
                $normalizada =
                    $this->normalizarTextoOpcional(
                        $descricao,
                    );

                if ($normalizada === null) {
                    continue;
                }

                $formatos[] =
                    $normalizada;
            }
        }

        return $formatos;
    }

    /**
     * Normaliza os artistas principais associados a uma Release Discogs.
     *
     * @param  mixed  $artistas  Artistas originais.
     * @return list<array{
     *     discogs_id: int,
     *     nome: string
     * }> Artistas normalizados.
     *
     * @since 2.0.0
     */
    private function normalizarArtistas(
        mixed $artistas,
    ): array {
        if (! is_array($artistas)) {
            return [];
        }

        $resultado = [];

        foreach ($artistas as $artista) {
            if (! is_array($artista)) {
                continue;
            }

            $identificador =
                $artista['id']
                ?? null;

            $nome =
                $artista['name']
                ?? null;

            if (
                ! is_int($identificador)
                || $identificador < 1
                || ! is_string($nome)
                || trim($nome) === ''
            ) {
                continue;
            }

            $resultado[] = [
                'discogs_id' => $identificador,

                'nome' => trim(
                    $nome,
                ),
            ];
        }

        return $resultado;
    }

    /**
     * Normaliza as faixas musicais de uma tracklist Discogs.
     *
     * Elementos descritivos são ignorados. Elementos de índice são percorridos
     * para preservar as respetivas subfaixas musicais.
     *
     * @param  mixed  $tracklist  Tracklist original.
     * @return list<array{
     *     titulo: string,
     *     posicao: string|null,
     *     ordem: int,
     *     artistas: list<array{
     *         discogs_id: int,
     *         nome: string
     *     }>
     * }> Faixas normalizadas.
     *
     * @since 2.0.0
     */
    private function normalizarFaixas(
        mixed $tracklist,
    ): array {
        $resultado = [];
        $ordem = 1;

        $this->adicionarFaixasNormalizadas(
            $tracklist,
            $resultado,
            $ordem,
        );

        return $resultado;
    }

    /**
     * Acrescenta recursivamente as faixas musicais de uma tracklist.
     *
     * @param  mixed  $tracklist  Tracklist ou conjunto de subfaixas.
     * @param  list<array{
     *     titulo: string,
     *     posicao: string|null,
     *     ordem: int,
     *     artistas: list<array{
     *         discogs_id: int,
     *         nome: string
     *     }>
     * }>  $resultado  Faixas já normalizadas.
     * @param  int  $ordem  Próxima ordem disponível.
     *
     * @since 2.0.0
     */
    private function adicionarFaixasNormalizadas(
        mixed $tracklist,
        array &$resultado,
        int &$ordem,
    ): void {
        if (! is_array($tracklist)) {
            return;
        }

        foreach ($tracklist as $faixa) {
            if (! is_array($faixa)) {
                continue;
            }

            $tipo =
                $faixa['type_']
                ?? null;

            if ($tipo === 'index') {
                $this->adicionarFaixasNormalizadas(
                    $faixa['sub_tracks']
                        ?? [],
                    $resultado,
                    $ordem,
                );

                continue;
            }

            if ($tipo !== 'track') {
                continue;
            }

            $titulo =
                $faixa['title']
                ?? null;

            if (
                ! is_string($titulo)
                || trim($titulo) === ''
            ) {
                continue;
            }

            $posicao =
                $faixa['position']
                ?? null;

            if (! is_string($posicao)) {
                $posicao = null;
            } else {
                $posicao =
                    trim(
                        $posicao,
                    );

                if ($posicao === '') {
                    $posicao = null;
                }
            }

            $resultado[] = [
                'titulo' => trim(
                    $titulo,
                ),

                'posicao' => $posicao,

                'ordem' => $ordem,

                'artistas' => $this->normalizarArtistas(
                    $faixa['artists']
                        ?? [],
                ),
            ];

            $ordem++;
        }
    }

    /**
     * Executa um pedido ao Discogs com controlo global de frequência.
     *
     * @param  string  $caminho  Caminho relativo da API.
     * @param  array<string, mixed>  $parametros  Parâmetros da consulta.
     * @param  bool  $requerAutenticacao  Indica se o pedido exige token.
     * @return Response Resposta HTTP válida.
     *
     * @throws RuntimeException Quando não é possível comunicar com o Discogs.
     *
     * @since 2.0.0
     */
    private function executarPedido(
        string $caminho,
        array $parametros = [],
        bool $requerAutenticacao = false,
    ): Response {
        $enderecoBase =
            rtrim(
                (string) config(
                    'discogs.base_url',
                    'https://api.discogs.com',
                ),
                '/',
            );

        $userAgent =
            trim(
                (string) config(
                    'discogs.user_agent',
                    '',
                ),
            );

        if ($userAgent === '') {
            throw new RuntimeException(
                'O User-Agent do Discogs não está configurado.',
            );
        }

        $token = null;

        if ($requerAutenticacao) {
            $token =
                trim(
                    (string) config(
                        'discogs.token',
                        '',
                    ),
                );

            if ($token === '') {
                throw new RuntimeException(
                    'O token da API do Discogs não está configurado.',
                );
            }
        }

        $tentativas =
            max(
                1,
                (int) config(
                    'discogs.tentativas',
                    3,
                ),
            );

        $intervaloRepeticao =
            max(
                0,
                (int) config(
                    'discogs.intervalo_repeticao_ms',
                    1000,
                ),
            );

        $intervaloMinimoPedidos =
            max(
                0,
                (int) config(
                    'discogs.intervalo_minimo_pedidos_ms',
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
                    'discogs',
                    $intervaloMinimoPedidos,
                );

            try {
                $pedido =
                    Http::acceptJson()
                        ->withHeaders([
                            'User-Agent' => $userAgent,
                        ]);

                if ($token !== null) {
                    $pedido =
                        $pedido->withHeaders([
                            'Authorization' => 'Discogs token='.$token,
                        ]);
                }

                $ultimaResposta =
                    $pedido
                        ->timeout(
                            max(
                                1,
                                (int) config(
                                    'discogs.timeout',
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
                'Não foi possível estabelecer ligação ao Discogs.',
                previous: $ultimaExcecao,
            );
        }

        if ($ultimaResposta->successful()) {
            return $ultimaResposta;
        }

        throw match ($ultimaResposta->status()) {
            429 => new RuntimeException(
                'O Discogs atingiu temporariamente o limite de pedidos.',
            ),

            503 => new RuntimeException(
                'O Discogs está temporariamente indisponível.',
            ),

            default => new RuntimeException(
                sprintf(
                    'O Discogs devolveu o código HTTP %d.',
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
                502,
                503,
                504,
            ],
            true,
        );
    }
}
