<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interacoes;

use App\Http\Controllers\Controller;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\Interacoes\DenunciaComentario;
use App\Servicos\Interacoes\ServicoDisponibilidadeInteracoes;
use App\Servicos\Notificacoes\NotificadorDenunciasComentarios;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Recebe denúncias de comentários publicados sem duplicar submissões.
 */
final class ControladorDenunciaComentario extends Controller
{
    public function __construct(
        private readonly ServicoDisponibilidadeInteracoes $disponibilidade,
        private readonly NotificadorDenunciasComentarios $notificadorDenuncias,
    ) {}

    public function guardar(Request $pedido, Comentario $comentario): JsonResponse
    {
        $utilizador = Auth::guard('sessao')->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException;
        }

        $dados = $pedido->validate([
            'motivo' => ['required', Rule::in(['spam', 'ofensivo', 'assedio', 'outro'])],
            'detalhes' => ['nullable', 'string', 'max:1000', 'required_if:motivo,outro'],
        ]);

        $identificadorUtilizador = $utilizador->id;

        $denuncia = DB::transaction(function () use (
            $comentario,
            $dados,
            $identificadorUtilizador,
        ): DenunciaComentario {
            $comentarioAtual = Comentario::query()
                ->whereKey($comentario->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->disponibilidade->obterMetalThursdayPublicadaComBloqueio(
                $comentarioAtual,
            );

            $utilizadorAtual = Utilizador::query()
                ->whereKey($identificadorUtilizador)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $utilizadorAtual->temAcessoAtivo()) {
                abort(Response::HTTP_FORBIDDEN);
            }

            if ($comentarioAtual->temConteudoEliminado()) {
                abort(Response::HTTP_GONE);
            }

            if ($comentarioAtual->utilizador_id === $identificadorUtilizador) {
                abort(Response::HTTP_FORBIDDEN);
            }

            /** @var string $motivo */
            $motivo = $dados['motivo'];
            /** @var string|null $detalhes */
            $detalhes = $dados['detalhes'] ?? null;

            $denunciaAtual = DenunciaComentario::query()->firstOrCreate(
                [
                    'comentario_id' => $comentarioAtual->id,
                    'denunciante_id' => $identificadorUtilizador,
                ],
                [
                    'motivo' => $motivo,
                    'detalhes' => $detalhes,
                ],
            );

            if ($denunciaAtual->wasRecentlyCreated) {
                $this->notificadorDenuncias->notificarNovaDenuncia($denunciaAtual);
            }

            return $denunciaAtual;
        }, 3);

        $nova = $denuncia->wasRecentlyCreated;

        return response()->json([
            'mensagem' => $nova
                ? 'Denúncia registada com sucesso.'
                : 'Este comentário já foi denunciado por ti.',
            'estado' => $denuncia->estado,
            'nova' => $nova,
        ], $nova ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
