<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Gere a redefinição de password.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class NewPasswordController extends Controller
{
    /**
     * Apresenta a página de redefinição de password.
     *
     * @param  Request  $request  - Pedido HTTP.
     * @return View - Página de redefinição de password.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function create(Request $request): View
    {
        $passwordResetRecord = DB::table('password_reset_tokens')
            ->get()
            ->first(function ($record) use ($request) {
                return Hash::check($request->token, $record->token);
            });

        if (! $passwordResetRecord) {
            return view('auth.reset-password', [
                'request' => $request,
                'error' => 'O link de redefinição de palavra-passe é inválido ou expirou.',
            ]);
        }

        return view('auth.reset-password', [
            'request' => $request,
            'email' => $passwordResetRecord->email,
        ]);
    }

    /**
     * Processa o formulário de redefinição de password.
     *
     * @param  ResetPasswordRequest  $request  - Pedido de redefinição de password.
     * @return RedirectResponse - Redireciona para a página de login ou para a página de reposição de password.
     *
     * @throws ValidationException - Exceção de validação.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $customPasswordResetMessages = [
            Password::PASSWORD_RESET => 'A tua palavra-passe foi redefinida com sucesso! Já podes iniciar sessão.',
            Password::INVALID_TOKEN => 'O token de redefinição de palavra-passe é inválido ou expirou.',
            Password::INVALID_USER => 'Ocorreu um erro ao validar a integridade do link. Recarrega a página e tenta novamente.',
        ];

        $status = Password::reset(
            $validated,
            function (Utilizador $user) use ($validated) {
                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? redirect()
            ->route('login')
            ->with('status', $customPasswordResetMessages[$status] ?? 'Erro desconhecido ao redefinir a palavra-passe.')
            : back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $customPasswordResetMessages[$status] ?? 'Erro desconhecido ao redefinir a palavra-passe.']);
    }
}
