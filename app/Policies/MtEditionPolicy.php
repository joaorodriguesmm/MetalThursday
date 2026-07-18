<?php

namespace App\Policies;

use App\Models\MtEdition;
use App\Models\User;

class MtEditionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->id === 1 ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MtEdition $mtEdition): bool
    {
        return true;
    }

    public function delete(User $user, MtEdition $mtEdition): bool
    {
        return $user->id === $mtEdition->created_by;
    }

    public function view(User $user, MtEdition $mtEdition): bool
    {
        return true;
    }
}
