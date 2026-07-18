<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Gere a recuperação de password.
 *
 * @since 1.0
 * @version 1.0
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Apresenta a página de recuperação de password.
     *
     * @return View - Página de recuperação de password.
     *
     * @since 1.0
     * @version 1.0
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Processa o formulário de recuperação de password.
     *
     * @param ForgotPasswordRequest $request - Pedido de recuperação de password.
     * @return RedirectResponse - Redirecionamento para a página de recuperação de password.
     * @throws ValidationException - Exceção de validação.
     *
     * @since 1.0
     * @version 1.0
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $customPasswordMessages = [
            Password::RESET_LINK_SENT => 'Foi enviado um link de redefinição de palavra-passe para o teu e-mail.',
            Password::INVALID_USER    => 'Não existe nenhum utilizador com o e-mail inserido.',
            Password::RESET_THROTTLED => 'Por favor, aguarda antes de fazer outro pedido de recuperação de palavra-passe.',
        ];

        $status = Password::sendResetLink($validated);

        return
            $status == Password::RESET_LINK_SENT
            ? back()
              ->with('status', $customPasswordMessages[$status] ?? 'Erro desconhecido ao enviar o link de recuperação de palavra-passe.')
            : back()
              ->withInput($request->only('email'))
              ->withErrors(['email' => $customPasswordMessages[$status] ?? 'Erro desconhecido ao enviar o link de recuperação de palavra-passe.']);
    }
}
