<?php

declare(strict_types=1);

namespace App\Http\Controllers\Interacoes;

use App\Http\Controllers\Controller;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\Interacoes\DenunciaComentario;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Servicos\Interacoes\ServicoEliminacaoComentarios;
use App\Servicos\Notificacoes\NotificadorDenunciasComentarios;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consulta a fila de denúncias e regista decisões do superadministrador.
 */
final class ControladorModeracaoDenuncias extends Controller
{
    public function __construct(
        private readonly NotificadorDenunciasComentarios $notificadorDenuncias,
        private readonly ServicoEliminacaoComentarios $servicoEliminacaoComentarios,
    ) {}

    public function indice(Request $pedido): JsonResponse
    {
        $this->autorizarSuperadministrador();

        $dados = $pedido->validate([
            'estado' => ['sometimes', Rule::in(['pendente', 'rejeitada', 'aceite'])],
        ]);

        $estado = $dados['estado'] ?? 'pendente';

        $denuncias = DenunciaComentario::query()
            ->where('estado', $estado)
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate(20);

        return response()->json($denuncias);
    }

    /**
     * Apresenta a fila administrativa sem expor denúncias a outros papéis.
     */
    public function painel(Request $pedido): View
    {
        $this->autorizarSuperadministrador();

        $dados = $pedido->validate([
            'estado' => ['sometimes', Rule::in(['pendente', 'rejeitada', 'aceite'])],
        ]);

        $estado = $dados['estado'] ?? 'pendente';

        $denuncias = DenunciaComentario::query()
            ->with(['comentario.utilizador', 'comentario.comentavel', 'denunciante'])
            ->where('estado', $estado)
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $identificadoresPublicacoes = [];

        foreach ($denuncias as $denuncia) {
            $entidade = $denuncia->comentario?->comentavel;

            if ($entidade instanceof MetalThursday) {
                $identificadoresPublicacoes[] = $entidade->id;
            } elseif ($entidade instanceof SeccaoMetalThursday) {
                $identificadoresPublicacoes[] = $entidade->metal_thursday_id;
            }
        }

        $publicacoesDisponiveis = MetalThursday::query()
            ->whereKey(array_unique($identificadoresPublicacoes))
            ->get()
            ->filter(static fn (MetalThursday $publicacao): bool => $publicacao->estaPublicada())
            ->pluck('id')
            ->all();

        return view('moderacao.denuncias-comentarios', [
            'denuncias' => $denuncias,
            'estadoAtual' => $estado,
            'publicacoesDisponiveis' => $publicacoesDisponiveis,
        ]);
    }

    public function decidir(Request $pedido, DenunciaComentario $denuncia): JsonResponse|RedirectResponse
    {
        $utilizador = $this->autorizarSuperadministrador();

        $dados = $pedido->validate([
            'decisao' => ['required', Rule::in(['rejeitar', 'aceitar'])],
        ]);

        $identificadorUtilizador = $utilizador->id;

        DB::transaction(function () use (
            $denuncia,
            $dados,
            $identificadorUtilizador,
        ): void {
            $comentarioBloqueado = Comentario::query()
                ->withTrashed()
                ->whereKey($denuncia->comentario_id)
                ->lockForUpdate()
                ->first();

            $moderadorAtual = Utilizador::query()
                ->whereKey($identificadorUtilizador)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                ! $moderadorAtual->temAcessoAtivo()
                || ! $moderadorAtual->eSuperAdministrador()
            ) {
                abort(Response::HTTP_FORBIDDEN);
            }

            $denunciaAtual = DenunciaComentario::query()
                ->whereKey($denuncia->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($denunciaAtual->estado !== 'pendente') {
                abort(Response::HTTP_CONFLICT);
            }

            if ($denunciaAtual->comentario_id !== $denuncia->comentario_id) {
                abort(Response::HTTP_CONFLICT);
            }

            if (
                $dados['decisao'] === 'aceitar'
                && $comentarioBloqueado instanceof Comentario
                && ! $comentarioBloqueado->trashed()
                && ! $comentarioBloqueado->temConteudoEliminado()
            ) {
                $this->servicoEliminacaoComentarios
                    ->eliminarComentarioBloqueado($comentarioBloqueado);
            }

            $denunciaAtual->estado = $dados['decisao'] === 'rejeitar'
                ? 'rejeitada'
                : 'aceite';
            $denunciaAtual->moderador_id = $moderadorAtual->id;
            $denunciaAtual->decidida_em = now();
            $denunciaAtual->saveOrFail();

            $this->notificadorDenuncias->notificarDecisao($denunciaAtual);
        }, 3);

        $mensagem = 'Decisão de moderação registada com sucesso.';

        if (! $pedido->expectsJson()) {
            return to_route('moderacao.denuncias-comentarios.painel')
                ->with('sucesso', $mensagem);
        }

        return response()->json([
            'mensagem' => $mensagem,
        ]);
    }

    private function autorizarSuperadministrador(): Utilizador
    {
        $utilizador = Auth::guard('sessao')->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException;
        }

        Gate::forUser($utilizador)->authorize('viewAny', Utilizador::class);

        return $utilizador;
    }
}
