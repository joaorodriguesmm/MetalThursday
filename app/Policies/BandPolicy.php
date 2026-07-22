<?php

namespace App\Policies;

use App\Models\Band;
use App\Models\Autenticacao\Utilizador;

class BandPolicy
{
    /**
     * Permite que um super-administrador (ID 1) execute qualquer ação.
     */
    public function before(Utilizador $user, string $ability): ?bool
    {
        return $user->id === 1 ? true : null;
    }

    /**
     * Determina se o utilizador pode ver a lista de bandas.
     */
    public function viewAny(Utilizador $user): bool
    {
        return true;
    }

    /**
     * Determina se o utilizador pode criar bandas.
     */
    public function create(Utilizador $user): bool
    {
        // Qualquer utilizador autenticado pode criar.
        return true;
    }

    /**
     * Determina se o utilizador pode atualizar a banda.
     */
    public function update(Utilizador $user, Band $band): bool
    {
        // Apenas o utilizador que criou a banda pode atualizá-la.
        return $user->id === $band->created_by;
    }

    /**
     * Determina se o utilizador pode apagar a banda.
     */
    public function delete(Utilizador $user, Band $band): bool
    {
        // Apenas o utilizador que criou a banda pode apagá-la.
        return $user->id === $band->created_by;
    }

    public function view(Utilizador $user, Band $band): bool
    {
        return true;
    }
}
