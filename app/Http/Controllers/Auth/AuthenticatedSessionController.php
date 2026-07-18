<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Gere a autenticação de utilizadores.
 *
 * @since 1.0
 * @version 1.0
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Apresenta a página de login.
     *
     * @return View - Página de login.
     *
     * @since 1.0
     * @version 1.0
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Processa o formulário de login.
     *
     * @param LoginRequest $request - Pedido de autenticação.
     * @return RedirectResponse - Redirecionamento para a página de login ou para a página inicial.
     *
     * @since 1.0
     * @version 1.0
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'A tua conta ainda não foi ativada. Por favor, verifica o link que foi enviado para o teu email.']);
        }

        $request->session()->regenerate();
        return redirect()->intended(route('home', [], false));
    }

    /**
     * Termina a sessão do utilizador.
     *
     * @param Request $request - Pedido HTTP.
     * @return RedirectResponse - Redirecionamento para a página de login.
     *
     * @since 1.0
     * @version 1.0
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
