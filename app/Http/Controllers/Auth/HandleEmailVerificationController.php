<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gere a verificação de e-mail.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class HandleEmailVerificationController extends Controller
{
    /**
     * Verifica o e-mail do utilizador.
     *
     * @param  Request  $request  - Pedido HTTP.
     * @param  string  $id  - Id do utilizador.
     * @param  string  $hash  - Hash do utilizador.
     * @return RedirectResponse Redirecionamento para a página de login.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = Utilizador::find($id);

        if (! $user || ! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Link de verificação inválido ou expirado.']);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()
                ->route('login')
                ->with('status', 'O teu email já foi verificado! Podes iniciar sessão.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()
            ->route('login')
            ->with('status', 'E-mail verificado com sucesso! Já podes iniciar sessão.');
    }
}
