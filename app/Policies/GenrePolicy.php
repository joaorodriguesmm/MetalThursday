<?php

namespace App\Policies;

use App\Models\Genre;
use App\Models\User;

class GenrePolicy
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

    public function update(User $user, Genre $genre): bool
    {
        return $user->id === $genre->created_by;
    }

    public function delete(User $user, Genre $genre): bool
    {
        return $user->id === $genre->created_by;
    }

    public function view(User $user, Genre $genre): bool
    {
        return true;
    }
}
