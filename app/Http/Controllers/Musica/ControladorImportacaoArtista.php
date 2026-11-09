<?php

declare(strict_types=1);

namespace App\Http\Controllers\Musica;

use App\Http\Controllers\Controller;
use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use App\Servicos\Musica\ServicoImportacaoArtista;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Disponibiliza a pesquisa e a preparação de dados externos para artistas.
 *
 * @since 2.0.0
 */
final class ControladorImportacaoArtista extends Controller
{
    use AuthorizesRequests;

    /**
     * Cria o controlador.
     *
     * @param  ServicoImportacaoArtista  $servicoImportacao  Serviço agregador.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoImportacaoArtista $servicoImportacao,
    ) {}

    /**
     * Pesquisa artistas no MusicBrainz.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return JsonResponse Resultados encontrados.
     *
     * @since 2.0.0
     */
    public function pesquisar(
        Request $pedido,
    ): JsonResponse {
        $this->authorize(
            'create',
            Artista::class,
        );

        $dados =
            $pedido->validate(
                [
                    'pesquisa' => [
                        'required',
                        'string',
                        'max:100',
                    ],
                ],
                [
                    'pesquisa.required' => 'Indica o nome do artista a pesquisar.',

                    'pesquisa.string' => 'O nome a pesquisar não é válido.',

                    'pesquisa.max' => 'O nome a pesquisar não pode exceder 100 caracteres.',
                ],
            );

        try {
            $resultados =
                $this
                    ->servicoImportacao
                    ->pesquisar(
                        $dados['pesquisa'],
                    );
        } catch (RuntimeException) {
            return response()->json(
                [
                    'mensagem' => 'Não foi possível pesquisar artistas no MusicBrainz. Tenta novamente mais tarde.',
                ],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        return response()->json([
            'resultados' => $resultados,
        ]);
    }

    /**
     * Obtém a proposta de preenchimento para o artista escolhido.
     *
     * @param  string  $mbid  Identificador MusicBrainz.
     * @return JsonResponse Proposta agregada.
     *
     * @since 2.0.0
     */
    public function obter(
        string $mbid,
    ): JsonResponse {
        $this->authorize(
            'create',
            Artista::class,
        );

        try {
            $artista =
                $this
                    ->servicoImportacao
                    ->obterProposta(
                        $mbid,
                    );
        } catch (RuntimeException) {
            return response()->json(
                [
                    'mensagem' => 'Não foi possível obter os dados do artista no MusicBrainz. Tenta novamente mais tarde.',
                ],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        $artista =
            $this->associarOrigemGeograficaLocal(
                $artista,
            );

        $artista = $this->associarGenerosLocais($artista);

        return response()->json([
            'artista' => $artista,
        ]);
    }

    /**
     * Associa correspondências locais às sugestões externas de géneros.
     *
     * Não modifica associações nem persiste informação.
     *
     * @param  array<string, mixed>  $artista
     * @return array<string, mixed>
     */
    private function associarGenerosLocais(array $artista): array
    {
        $sugestoes = $artista['sugestoes_generos'] ?? [];

        if (! is_array($sugestoes) || $sugestoes === []) {
            return $artista;
        }

        $nomes = [];

        foreach ($sugestoes as $sugestao) {
            if (
                ! is_array($sugestao)
                || ! is_string($sugestao['nome'] ?? null)
            ) {
                continue;
            }

            $nome = mb_strtolower(trim($sugestao['nome']));

            if ($nome !== '') {
                $nomes[$nome] = true;
            }
        }

        $generos = collect();

        if ($nomes !== []) {
            $marcadores = implode(
                ', ',
                array_fill(0, count($nomes), '?'),
            );

            $generos = Genero::query()
                ->with('generosPais')
                ->whereRaw(
                    'LOWER(generos.nome) IN ('.$marcadores.')',
                    array_keys($nomes),
                )
                ->get()
                ->keyBy(
                    static fn (Genero $genero): string => mb_strtolower(trim($genero->nome)),
                );
        }

        foreach ($sugestoes as &$sugestao) {
            if (
                ! is_array($sugestao)
                || ! is_string($sugestao['nome'] ?? null)
            ) {
                continue;
            }

            $nome = mb_strtolower(trim($sugestao['nome']));
            $genero = $generos->get($nome);

            $sugestao['disponivel'] = $genero !== null;

            $sugestao['genero_local'] = $genero === null
                ? null
                : [
                    'id' => $genero->getKey(),
                    'nome' => $genero->nome,
                ];

            $sugestao['generos_pais'] = $genero === null
                ? []
                : $genero->generosPais
                    ->map(
                        static fn (Genero $pai): array => [
                            'id' => $pai->getKey(),
                            'nome' => $pai->nome,
                        ],
                    )
                    ->values()
                    ->all();
        }

        unset($sugestao);

        $artista['sugestoes_generos'] = $sugestoes;

        return $artista;
    }

    /**
     * Procura a origem geográfica local correspondente ao código ISO recebido.
     *
     * @param  array<string, mixed>  $artista  Proposta agregada.
     * @return array<string, mixed> Proposta com correspondência local.
     *
     * @since 2.0.0
     */
    private function associarOrigemGeograficaLocal(
        array $artista,
    ): array {
        $artista['origem_geografica_id'] =
            null;

        $artista['origem_geografica'] =
            null;

        $codigo =
            data_get(
                $artista,
                'origem.codigo_pais',
            );

        if (! is_string($codigo)) {
            return $artista;
        }

        $codigo =
            mb_strtoupper(
                trim(
                    $codigo,
                ),
            );

        if ($codigo === '') {
            return $artista;
        }

        $origemGeografica =
            OrigemGeografica::query()
                ->select([
                    'id',
                    'nome',
                    'codigo',
                ])
                ->where(
                    'codigo',
                    $codigo,
                )
                ->first();

        if (! $origemGeografica instanceof OrigemGeografica) {
            return $artista;
        }

        $artista['origem_geografica_id'] =
            $origemGeografica->id;

        $artista['origem_geografica'] = [
            'id' => $origemGeografica->id,

            'nome' => $origemGeografica->nome,

            'codigo' => $origemGeografica->codigo,
        ];

        return $artista;
    }
}
