<?php

declare(strict_types=1);

namespace App\Servicos\Interacoes;

use App\Models\Autenticacao\Utilizador;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que uma interação apenas é escrita por um utilizador com acesso ativo.
 *
 * A consulta bloqueia o utilizador na mesma transação da interação para
 * impedir uma suspensão concorrente entre a verificação e a escrita.
 *
 * @since 2.0.0
 */
final class ServicoAcessoInteracoes
{
    /**
     * Revalida o acesso do utilizador na transação de escrita.
     *
     * @param  int  $identificadorUtilizador  Identificador autenticado.
     *
     * @since 2.0.0
     */
    public function garantirAcessoAtivoComBloqueio(
        int $identificadorUtilizador,
    ): void {
        $utilizador = Utilizador::query()
            ->whereKey($identificadorUtilizador)
            ->lockForUpdate()
            ->firstOrFail();

        if (! $utilizador->temAcessoAtivo()) {
            abort(Response::HTTP_FORBIDDEN);
        }
    }
}
