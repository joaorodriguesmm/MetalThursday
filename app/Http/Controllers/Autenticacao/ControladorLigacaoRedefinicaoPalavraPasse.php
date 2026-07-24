<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\SolicitarRedefinicaoPalavraPasseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Gere o pedido de envio da ligação de redefinição da palavra-passe.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class ControladorLigacaoRedefinicaoPalavraPasse extends Controller
{
    /**
     * Mensagem genérica apresentada depois do pedido.
     *
     * A mesma mensagem é utilizada para endereços existentes e inexistentes,
     * impedindo a enumeração de contas.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const MENSAGEM_PEDIDO_RECEBIDO =
        'Se existir uma conta associada ao endereço indicado, será enviada uma ligação para redefinir a palavra-passe.';

    /**
     * Apresenta o formulário de recuperação da palavra-passe.
     *
     * @return View Formulário de recuperação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function apresentar(): View
    {
        return view(
            'auth.forgot-password',
        );
    }

    /**
     * Solicita o envio da ligação de redefinição da palavra-passe.
     *
     * Apenas um bloqueio temporário por excesso de pedidos é apresentado como
     * erro. Os restantes estados recebem a mesma resposta genérica.
     *
     * @param  SolicitarRedefinicaoPalavraPasseRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento para o formulário.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function enviar(
        SolicitarRedefinicaoPalavraPasseRequest $pedido,
    ): RedirectResponse {
        $dados = $pedido->validated();

        /** @var string $email */
        $email = $dados['email'];

        $estado = Password::sendResetLink([
            'email' => $email,
        ]);

        if ($estado === Password::RESET_THROTTLED) {
            return back()
                ->withInput([
                    'email' => $email,
                ])
                ->withErrors([
                    'email' => __($estado),
                ]);
        }

        return back()->with(
            'estado',
            self::MENSAGEM_PEDIDO_RECEBIDO,
        );
    }
}
