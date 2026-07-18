<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ClaimInvitationRequest;
use App\Models\EmailPermission;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Gere o registo de utilizadores por convite.
 *
 * @since 1.0
 * @version 1.0
 */
class RegisteredUserController extends Controller
{
    /**
     * Apresenta o formulário de registo por convite.
     *
     * @param string $codigoConvite - Código de convite.
     * @return View|RedirectResponse - Página de registo por convite ou redirecionamento para a página de login.
     *
     * @since 1.0
     * @version 1.0
     */
    public function create(string $codigoConvite): View|RedirectResponse
    {
        $user = User::where('invite_code', $codigoConvite)
                    ->whereNull('email')
                    ->first();

        if (!$user) {
            return redirect()
                   ->route('login')
                   ->with('error', 'Este convite é inválido ou já foi utilizado.');
        }

        return view('auth.claim-invitation', [
            'user'        => $user,
            'permissions' => EmailPermission::all(),
        ]);
    }

    /**
     * Processa o formulário de registo por convite.
     *
     * @param ClaimInvitationRequest $request - Pedido de registo por convite.
     * @return RedirectResponse - Redirecionamento para a página de login.
     *
     * @since 1.0
     * @version 1.0
     */
    public function store(ClaimInvitationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::where('invite_code', $validated['invite_code'])->first();

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos', 'public');
        }

        $user->update([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'photo'    => $photoPath,
        ]);

        $user->emailPermissions()->sync($validated['email_permissions'] ?? []);

        event(new Registered($user));

        return redirect()
               ->route('login')
               ->with('status', 'Registo concluído! Foi enviado um link de verificação para o teu e-mail.');
    }
}
