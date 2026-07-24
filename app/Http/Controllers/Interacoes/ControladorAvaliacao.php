<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interacoes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interacoes\GuardarAvaliacaoRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Avaliacao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Servicos\Notificacoes\NotificadorInteracoes;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Gere as avaliações atribuídas a MetalThursdays e respetivas secções.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class ControladorAvaliacao extends Controller
{
    /**
     * Cria o controlador.
     *
     * @param  NotificadorInteracoes  $notificadorInteracoes  Serviço responsável
     *                                                        pelas notificações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        private readonly NotificadorInteracoes $notificadorInteracoes,
    ) {}

    /**
     * Cria ou atualiza a avaliação do utilizador autenticado.
     *
     * A entidade avaliada é bloqueada durante a transação para impedir que
     * pedidos simultâneos criem avaliações duplicadas.
     *
     * @param  GuardarAvaliacaoRequest  $pedido  Pedido validado.
     * @param  string  $tipoAvaliavel  Tipo da entidade avaliada.
     * @param  int  $identificadorAvaliavel  Identificador da entidade.
     * @return JsonResponse Estado atualizado das avaliações.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function guardar(
        GuardarAvaliacaoRequest $pedido,
        string $tipoAvaliavel,
        int $identificadorAvaliavel,
    ): JsonResponse {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $pedido,
            );

        $pontuacao =
            $pedido->obterPontuacao();

        $avaliavel =
            $this->resolverAvaliavel(
                $tipoAvaliavel,
                $identificadorAvaliavel,
            );

        $resultado = DB::transaction(
            function () use (
                $avaliavel,
                $identificadorUtilizador,
                $pontuacao,
            ): array {
                $avaliavelBloqueado =
                    $this->bloquearAvaliavel(
                        $avaliavel,
                    );

                $avaliacao = $avaliavelBloqueado
                    ->avaliacoes()
                    ->where(
                        'utilizador_id',
                        $identificadorUtilizador,
                    )
                    ->first();

                $avaliacaoAlterada = false;

                if ($avaliacao instanceof Avaliacao) {
                    $pontuacaoAtual = round(
                        (float) $avaliacao->pontuacao,
                        1,
                    );

                    if ($pontuacaoAtual !== $pontuacao) {
                        $avaliacao->updateOrFail([
                            'pontuacao' => $pontuacao,
                        ]);

                        $avaliacaoAlterada = true;
                    }
                } else {
                    $avaliavelBloqueado
                        ->avaliacoes()
                        ->create([
                            'utilizador_id' => $identificadorUtilizador,

                            'pontuacao' => $pontuacao,
                        ]);

                    $avaliacaoAlterada = true;
                }

                return [
                    'avaliavel' => $avaliavelBloqueado,

                    'avaliacao_alterada' => $avaliacaoAlterada,
                ];
            },
        );

        /** @var MetalThursday|SeccaoMetalThursday $avaliavelAtualizado */
        $avaliavelAtualizado =
            $resultado['avaliavel'];

        if ($resultado['avaliacao_alterada'] === true) {
            $this->notificadorInteracoes
                ->notificarOutrosUtilizadores(
                    $avaliavelAtualizado,
                    'avaliou',
                );
        }

        $estatisticas =
            $this->obterEstatisticas(
                $avaliavelAtualizado,
            );

        return response()->json([
            'media_avaliacoes' => round(
                $estatisticas['media'],
                1,
            ),

            'numero_avaliacoes' => $estatisticas['numero'],

            'pontuacao_utilizador' => $pontuacao,

            'conteudo_indicador' => $this->obterConteudoIndicador(
                $avaliavelAtualizado,
            ),
        ]);
    }

    /**
     * Resolve a entidade que recebe a avaliação.
     *
     * Apenas são aceites os identificadores canónicos definidos pela
     * aplicação.
     *
     * @param  string  $tipo  Tipo recebido através da rota.
     * @param  int  $identificador  Identificador recebido através da rota.
     * @return MetalThursday|SeccaoMetalThursday Entidade encontrada.
     *
     * @throws NotFoundHttpException Quando o tipo não é reconhecido ou o
     *                               identificador não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function resolverAvaliavel(
        string $tipo,
        int $identificador,
    ): MetalThursday|SeccaoMetalThursday {
        if ($identificador < 1) {
            throw new NotFoundHttpException;
        }

        $tipoNormalizado = mb_strtolower(
            trim($tipo),
        );

        $classeModelo = match ($tipoNormalizado) {
            'metal_thursday' => MetalThursday::class,

            'seccao_metal_thursday' => SeccaoMetalThursday::class,

            default => throw new NotFoundHttpException,
        };

        return $classeModelo::query()
            ->findOrFail(
                $identificador,
            );
    }

    /**
     * Bloqueia a entidade durante a atualização da avaliação.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $avaliavel  Entidade original.
     * @return MetalThursday|SeccaoMetalThursday Entidade bloqueada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function bloquearAvaliavel(
        MetalThursday|SeccaoMetalThursday $avaliavel,
    ): MetalThursday|SeccaoMetalThursday {
        $classeModelo =
            $avaliavel::class;

        /** @var MetalThursday|SeccaoMetalThursday $avaliavelBloqueado */
        $avaliavelBloqueado = $classeModelo::query()
            ->whereKey(
                $avaliavel->getKey(),
            )
            ->lockForUpdate()
            ->firstOrFail();

        return $avaliavelBloqueado;
    }

    /**
     * Obtém a média e o número total de avaliações.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $avaliavel  Entidade consultada.
     * @return array{
     *     media: float,
     *     numero: int
     * } Estatísticas das avaliações.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterEstatisticas(
        MetalThursday|SeccaoMetalThursday $avaliavel,
    ): array {
        $modeloAvaliacao =
            new Avaliacao;

        $estatisticas = DB::table(
            $modeloAvaliacao->getTable(),
        )
            ->where(
                'avaliavel_id',
                $avaliavel->getKey(),
            )
            ->where(
                'tipo_avaliavel',
                $avaliavel->getMorphClass(),
            )
            ->selectRaw(
                'COUNT(*) AS numero_avaliacoes, '
                    .'AVG(pontuacao) AS media_avaliacoes',
            )
            ->first();

        return [
            'media' => (float) (
                $estatisticas?->media_avaliacoes
                ?? 0
            ),

            'numero' => (int) (
                $estatisticas?->numero_avaliacoes
                ?? 0
            ),
        ];
    }

    /**
     * Obtém o conteúdo apresentado no indicador das avaliações.
     *
     * Os nomes são escapados antes de serem incluídos no conteúdo HTML.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $avaliavel  Entidade consultada.
     * @return string Conteúdo HTML seguro.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterConteudoIndicador(
        MetalThursday|SeccaoMetalThursday $avaliavel,
    ): string {
        $linhas = $avaliavel
            ->avaliacoes()
            ->with([
                'utilizador:id,nome',
            ])
            ->orderByDesc(
                'pontuacao',
            )
            ->orderBy(
                'id',
            )
            ->get()
            ->map(
                static function (
                    Avaliacao $avaliacao,
                ): ?string {
                    $nome =
                        $avaliacao
                            ->utilizador
                            ?->nome;

                    if (
                        ! is_string($nome)
                        || trim($nome) === ''
                    ) {
                        return null;
                    }

                    return sprintf(
                        '%s: %s',
                        e($nome),
                        number_format(
                            (float) $avaliacao->pontuacao,
                            1,
                            ',',
                            '',
                        ),
                    );
                },
            )
            ->filter(
                static fn (
                    mixed $linha,
                ): bool => is_string($linha),
            )
            ->values();

        if ($linhas->isEmpty()) {
            return 'Ainda sem avaliações.';
        }

        return $linhas->implode(
            '<br>',
        );
    }

    /**
     * Obtém o identificador do utilizador autenticado.
     *
     * @param  GuardarAvaliacaoRequest  $pedido  Pedido HTTP.
     * @return int Identificador do utilizador.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterIdentificadorUtilizador(
        GuardarAvaliacaoRequest $pedido,
    ): int {
        $utilizador =
            $pedido->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para avaliar.',
            );
        }

        $identificador =
            $utilizador->getKey();

        if (
            ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new AuthenticationException(
                'Não foi possível identificar o utilizador autenticado.',
            );
        }

        return (int) $identificador;
    }
}
