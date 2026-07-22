<?php

namespace App\Policies;

use App\Models\MetalThursday;
use App\Models\Autenticacao\Utilizador;

/**
 * Define as permissões para executar ações em MetalThursdays.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class MetalThursdayPolicy
{
    /**
     * Permite que um super-administrador execute qualquer ação.
     *
     * @param  Utilizador  $user  - O utilizador autenticado.
     * @param  string  $ability  - A ação a executar.
     * @return bool|null - Verdadeiro se o utilizador for super-administrador.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function before(Utilizador $user, string $ability): ?bool
    {
        if ($user->id === 1) {
            return true;
        }

        return null;
    }

    /**
     * Obtém se o utilizador pode editar a MetalThursday.
     *
     * @param  Utilizador  $user  - O utilizador autenticado.
     * @param  MetalThursday  $metalThursday  - A MetalThursday.
     * @return bool - Verdadeiro se o utilizador for o autor ou criador da MetalThursday.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function update(Utilizador $user, MetalThursday $metalThursday): bool
    {
        return (int) $user->id === (int) $metalThursday->created_by
            || (int) $user->id === (int) $metalThursday->author_id;
    }

    /**
     * Obtém se o utilizador pode apagar a MetalThursday.
     *
     * @param  Utilizador  $user  - O utilizador autenticado.
     * @param  MetalThursday  $metalThursday  - A MetalThursday.
     * @return bool - Verdadeiro se o utilizador for o criador da MetalThursday.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function delete(Utilizador $user, MetalThursday $metalThursday): bool
    {
        return (int) $user->id === (int) $metalThursday->created_by;
    }
}
