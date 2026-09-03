<?php

declare(strict_types=1);

namespace App\Http\Controllers\Musica;

use App\Http\Controllers\Controller;
use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Artista;
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
        } catch (RuntimeException $excecao) {
            return response()->json(
                [
                    'mensagem' => $excecao->getMessage(),
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
        } catch (RuntimeException $excecao) {
            return response()->json(
                [
                    'mensagem' => $excecao->getMessage(),
                ],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        $artista =
            $this->associarOrigemGeograficaLocal(
                $artista,
            );

        return response()->json([
            'artista' => $artista,
        ]);
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
            (int) $origemGeografica->getKey();

        $artista['origem_geografica'] = [
            'id' => (int) $origemGeografica->getKey(),

            'nome' => $origemGeografica->nome,

            'codigo' => $origemGeografica->codigo,
        ];

        return $artista;
    }
}
