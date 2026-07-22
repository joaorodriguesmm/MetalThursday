<?php

namespace App\Policies;

use App\Models\Genre;
use App\Models\Autenticacao\Utilizador;

class GenrePolicy
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

    public function update(Utilizador $user, Genre $genre): bool
    {
        return $user->id === $genre->created_by;
    }

    public function delete(Utilizador $user, Genre $genre): bool
    {
        return $user->id === $genre->created_by;
    }

    public function view(Utilizador $user, Genre $genre): bool
    {
        return true;
    }
}
