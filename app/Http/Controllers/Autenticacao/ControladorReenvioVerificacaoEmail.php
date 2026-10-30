<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\ReenviarVerificacaoEmailRequest;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Recupera a verificação sem exigir uma sessão de uma conta não verificada.
 *
 * A resposta é igual para contas inexistentes, verificadas ou suspensas e
 * para pedidos repetidos. O limite por endereço é partilhado entre IPs.
 *
 * @since 2.0.0
 */
final class ControladorReenvioVerificacaoEmail extends Controller
{
    public function apresentar(): View
    {
        return view('autenticacao.reenviar-verificacao-email');
    }

    public function enviar(ReenviarVerificacaoEmailRequest $pedido): RedirectResponse
    {
        $email = $pedido->email();

        // A reserva atómica impede envios simultâneos para o mesmo endereço.
        if (Cache::add('verificacao-email:reenvio:'.hash('sha256', $email), true, 60)) {
            $utilizador = Utilizador::query()
                ->comAcessoAtivo()
                ->where('email', $email)
                ->whereNull('email_verified_at')
                ->first();

            if ($utilizador instanceof Utilizador) {
                try {
                    $utilizador->sendEmailVerificationNotification();
                } catch (Throwable $excecao) {
                    Log::error(
                        'Ocorreu um erro ao reenviar a verificação do endereço de e-mail.',
                        ['excecao' => $excecao::class],
                    );
                }
            }
        }

        return to_route('autenticacao.reenviar-verificacao-email')
            ->withInput(['email' => $email])
            ->with(
                'informacao',
                'Se existir uma conta ativa por verificar associada ao endereço indicado, será enviada uma nova ligação de verificação. Aguarda um minuto antes de repetires o pedido.',
            );
    }
}
