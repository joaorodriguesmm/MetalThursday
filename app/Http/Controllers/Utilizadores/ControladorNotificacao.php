<?php

declare(strict_types=1);

namespace App\Http\Controllers\Utilizadores;

use App\Http\Controllers\Controller;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Gere as notificações do utilizador autenticado.
 *
 * Todas as consultas são executadas através das relações do utilizador,
 * impedindo o acesso ou a alteração de notificações pertencentes a outras
 * contas.
 *
 * As marcações de leitura utilizam atualizações condicionais atómicas,
 * preservando a primeira data de leitura perante pedidos concorrentes.
 *
 * @since 1.0.0
 *
 * @version 4.1.0
 */
final class ControladorNotificacao extends Controller
{
    /**
     * Número de notificações apresentadas por página.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private const NOTIFICACOES_POR_PAGINA =
        15;

    /**
     * Apresenta as notificações do utilizador autenticado.
     *
     * A existência de notificações por ler é verificada diretamente na base
     * de dados, sem carregar todos os registos para memória.
     *
     * @return View Página das notificações.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function indice(): View
    {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        $existemNotificacoesNaoLidas =
            $utilizador
                ->notificacoesPorLer()
                ->exists();

        $notificacoes =
            $utilizador
                ->notificacoes()
                ->select([
                    'id',
                    'type',
                    'notifiable_type',
                    'notifiable_id',
                    'data',
                    'read_at',
                    'created_at',
                    'updated_at',
                ])
                ->orderByDesc(
                    'created_at',
                )
                ->orderByDesc(
                    'id',
                )
                ->paginate(
                    self::NOTIFICACOES_POR_PAGINA,
                )
                ->withQueryString();

        return view(
            'notificacoes.indice',
            [
                'notificacoes' => $notificacoes,

                'existemNotificacoesNaoLidas' => $existemNotificacoesNaoLidas,
            ],
        );
    }

    /**
     * Marca uma notificação do utilizador autenticado como lida.
     *
     * A atualização permanece limitada à relação do utilizador autenticado e
     * apenas modifica notificações cuja data de leitura ainda esteja vazia.
     *
     * A condição é aplicada diretamente no `UPDATE`, garantindo que pedidos
     * concorrentes não substituem a primeira data de leitura. Uma marcação
     * bem-sucedida necessita apenas de uma consulta à tabela.
     *
     * Quando nenhuma linha é atualizada, é confirmada a existência da
     * notificação dentro da mesma relação. Uma notificação pertencente a outra
     * conta continua a produzir uma resposta de recurso inexistente.
     *
     * @param  string  $identificadorNotificacao  Identificador da notificação.
     * @return RedirectResponse Redirecionamento para as notificações.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 4.1.0
     */
    public function marcarComoLida(
        string $identificadorNotificacao,
    ): RedirectResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        $consultaNotificacao =
            $utilizador
                ->notificacoes()
                ->reorder()
                ->whereKey(
                    $identificadorNotificacao,
                );

        $quantidadeAtualizada =
            (clone $consultaNotificacao)
                ->whereNull(
                    'read_at',
                )
                ->update([
                    'read_at' => now(),
                ]);

        if ($quantidadeAtualizada === 0) {
            (clone $consultaNotificacao)
                ->firstOrFail();

            return to_route(
                'notificacoes.indice',
            )->with(
                'informacao',
                'A notificação já estava marcada como lida.',
            );
        }

        return to_route(
            'notificacoes.indice',
        )->with(
            'sucesso',
            'Notificação marcada como lida.',
        );
    }

    /**
     * Marca todas as notificações por ler como lidas.
     *
     * A atualização é executada diretamente na base de dados, evitando
     * carregar todos os modelos para memória.
     *
     * A consulta permanece limitada às notificações pertencentes ao
     * utilizador autenticado.
     *
     * @return RedirectResponse Redirecionamento para as notificações.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function marcarTodasComoLidas(): RedirectResponse
    {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        $quantidadeAtualizada =
            $utilizador
                ->notificacoesPorLer()
                ->update([
                    'read_at' => now(),
                ]);

        $mensagem =
            match ($quantidadeAtualizada) {
                0 => 'Não existiam notificações por ler.',

                1 => 'A notificação por ler foi marcada como lida.',

                default => sprintf(
                    '%d notificações por ler foram marcadas como lidas.',
                    $quantidadeAtualizada,
                ),
            };

        return to_route(
            'notificacoes.indice',
        )->with(
            $quantidadeAtualizada === 0
                ? 'informacao'
                : 'sucesso',
            $mensagem,
        );
    }

    /**
     * Obtém o utilizador autenticado através do guard da aplicação.
     *
     * @return Utilizador Utilizador autenticado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador =
            Auth::guard(
                'sessao',
            )->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para consultar notificações.',
            );
        }

        return $utilizador;
    }
}
