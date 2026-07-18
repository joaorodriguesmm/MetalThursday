<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\PasswordUpdateRequest;
use App\Http\Requests\User\ProfileUpdateRequest;
use App\Http\Requests\User\UpdateEmailPermissionsRequest;
use App\Models\EmailPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Gere a edição do perfil de utilizador autenticado.
 *
 * @since 1.0
 * @version 1.0
 */
class ProfileController extends Controller
{
    /**
     * Exibe o formulário de edição de perfil do utilizador autenticado.
     *
     * @param Request $request - Pedido HTTP.
     * @return View - View do formulário de edição de perfil.
     *
     * @since 1.0
     * @version 1.0
     */
    public function edit(Request $request): View
    {
        $user                 = $request->user();
        $allEmailPermissions  = EmailPermission::all();
        $userEmailPermissions = $user->emailPermissions->pluck('id')->toArray();

        return view('profile.edit', [
            'user'                 => $user,
            'allEmailPermissions'  => $allEmailPermissions,
            'userEmailPermissions' => $userEmailPermissions,
        ]);
    }

    /**
     * Atualiza a informação do perfil do utilizador autenticado.
     *
     * @param ProfileUpdateRequest $request - Pedido de atualização de perfil.
     * @return RedirectResponse - Redirecionamento para a página de edição de perfil.
     *
     * @since 1.0
     * @version 1.0
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        $emailChanged = $user->isDirty('email');
        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $user->photo = $request->file('photo')->store('photos', 'public');
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'O teu perfil foi atualizado! Como alteraste o e-mail, a tua sessão foi terminada. Por favor, verifica o teu novo e-mail, clicando no link que foi enviado.');
        }

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Atualiza as permissões de e-mail do utilizador.
     *
     * @param UpdateEmailPermissionsRequest $request - Pedido de atualização de permissões de e-mail.
     * @return RedirectResponse - Redirecionamento para a página de edição de perfil com uma mensagem de sucesso.
     *
     * @since 1.0
     * @version 1.0
     */
    public function updateEmailPermissions(UpdateEmailPermissionsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $request->user()->emailPermissions()->sync($validated['email_permissions'] ?? []);
        return redirect()->route('profile.edit')->with('status', 'email-permissions-updated');
    }


    /**
     * Atualiza a password do utilizador.
     *
     * @param PasswordUpdateRequest $request - Pedido de atualização de password.
     * @return RedirectResponse - Redirecionamento para a página de edição de perfil com uma mensagem de sucesso.
     *
     * @since 1.0
     * @version 1.0
     */
    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        return redirect()->route('profile.edit')->with('status', 'password-updated');
    }
}
