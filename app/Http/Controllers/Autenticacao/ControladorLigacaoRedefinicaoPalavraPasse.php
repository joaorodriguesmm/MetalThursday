<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Gere os pedidos de ligação para redefinição da palavra-passe.
 *
 * A resposta não revela se existe uma conta associada ao endereço de e-mail
 * recebido.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class ControladorLigacaoRedefinicaoPalavraPasse extends Controller
{
    /**
     * Mensagem apresentada depois de um pedido válido.
     *
     * A mesma mensagem é utilizada quando o endereço não pertence a qualquer
     * utilizador, impedindo a enumeração de contas.
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
     * Envia uma ligação de redefinição da palavra-passe.
     *
     * @param  ForgotPasswordRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento para o formulário.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function enviar(
        ForgotPasswordRequest $pedido,
    ): RedirectResponse {
        $dados = $pedido->validated();

        $estado = Password::sendResetLink([
            'email' => $dados['email'],
        ]);

        if ($estado === Password::RESET_THROTTLED) {
            return back()
                ->withInput(
                    $pedido->only('email'),
                )
                ->withErrors([
                    'email' => 'Aguarda antes de efetuares outro pedido de redefinição.',
                ]);
        }

        return back()->with(
            'estado',
            self::MENSAGEM_PEDIDO_RECEBIDO,
        );
    }
}
