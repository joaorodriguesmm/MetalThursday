<?php

namespace App\Policies;

use App\Models\MtEdition;
use App\Models\Autenticacao\Utilizador;

class MtEditionPolicy
{
    public function before(Utilizador $user, string $ability): ?bool
    {
        return $user->id === 1 ? true : null;
    }

    public function viewAny(Utilizador $user): bool
    {
        return true;
    }

    public function create(Utilizador $user): bool
    {
        return true;
    }

    public function update(Utilizador $user, MtEdition $mtEdition): bool
    {
        return true;
    }

    public function delete(Utilizador $user, MtEdition $mtEdition): bool
    {
        return $user->id === $mtEdition->created_by;
    }

    public function view(Utilizador $user, MtEdition $mtEdition): bool
    {
        return true;
    }
}
