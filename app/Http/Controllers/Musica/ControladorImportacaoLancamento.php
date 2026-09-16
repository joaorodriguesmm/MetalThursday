<?php

declare(strict_types=1);

namespace App\Http\Controllers\Musica;

use App\Http\Controllers\Controller;
use App\Models\MetalThursday\MetalThursday;
use App\Models\Musica\FaixaLancamento;
use App\Servicos\Musica\ServicoDiscogs;
use App\Servicos\Musica\ServicoImportacaoLancamento;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Disponibiliza a pesquisa e a importação de lançamentos do Discogs.
 *
 * @since 2.0.0
 */
final class ControladorImportacaoLancamento extends Controller
{
    use AuthorizesRequests;

    /**
     * Cria o controlador.
     *
     * @param  ServicoDiscogs  $discogs  Integração com o Discogs.
     * @param  ServicoImportacaoLancamento  $servicoImportacao  Serviço de
     *                                                          importação.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoDiscogs $discogs,
        private readonly ServicoImportacaoLancamento $servicoImportacao,
    ) {}

    /**
     * Pesquisa Releases no Discogs.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return JsonResponse Resultados encontrados.
     *
     * @since 2.0.0
     */
    public function pesquisar(
        Request $pedido,
    ): JsonResponse {
        $this->autorizarUtilizacao(
            $pedido,
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
                    'pesquisa.required' => 'Indica o lançamento a pesquisar.',

                    'pesquisa.string' => 'O lançamento a pesquisar não é válido.',

                    'pesquisa.max' => 'A pesquisa não pode exceder 100 caracteres.',
                ],
            );

        try {
            $resultados =
                $this
                    ->discogs
                    ->pesquisarLancamentos(
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
     * Importa uma Release concreta do Discogs.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  string  $identificadorDiscogs  Identificador da Release.
     * @return JsonResponse Lançamento local importado.
     *
     * @since 2.0.0
     */
    public function importar(
        Request $pedido,
        string $identificadorDiscogs,
    ): JsonResponse {
        $this->autorizarUtilizacao(
            $pedido,
        );

        try {
            $lancamento =
                $this
                    ->servicoImportacao
                    ->importar(
                        (int) $identificadorDiscogs,
                    );
        } catch (RuntimeException $excecao) {
            return response()->json(
                [
                    'mensagem' => $excecao->getMessage(),
                ],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        $lancamento->load(
            'faixas.musica',
        );

        return response()->json([
            'lancamento' => [
                'id' => (int) $lancamento->getKey(),
                'discogs_release_id' => (int) $lancamento->discogs_release_id,
                'titulo' => $lancamento->titulo,
                'tipo' => $lancamento->tipo?->value,
                'ano_original' => $lancamento->ano_original,

                'faixas' => $lancamento
                    ->faixas
                    ->map(
                        static fn (
                            FaixaLancamento $faixa,
                        ): array => [
                            'id' => (int) $faixa->getKey(),
                            'musica_id' => (int) $faixa->musica_id,
                            'titulo' => $faixa->musica?->titulo,
                            'posicao' => $faixa->posicao,
                            'ordem' => $faixa->ordem,
                        ],
                    )
                    ->values()
                    ->all(),
            ],
        ]);
    }

    /**
     * Autoriza a utilização da integração Discogs no contexto atual.
     *
     * Durante a criação é aplicada a autorização de criação de MetalThursdays.
     * Durante a edição é aplicada a autorização de alteração da MetalThursday
     * indicada pelo pedido.
     *
     * @param  Request  $pedido  Pedido HTTP.
     *
     * @since 2.0.0
     */
    private function autorizarUtilizacao(
        Request $pedido,
    ): void {
        if (! $pedido->has('metal_thursday_id')) {
            $this->authorize(
                'create',
                MetalThursday::class,
            );

            return;
        }

        $metalThursday =
            MetalThursday::query()
                ->findOrFail(
                    $pedido->integer(
                        'metal_thursday_id',
                    ),
                );

        $this->authorize(
            'update',
            $metalThursday,
        );
    }
}
