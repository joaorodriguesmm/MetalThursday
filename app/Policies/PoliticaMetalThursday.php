<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;

/**
 * Define as regras de autorização aplicáveis às MetalThursdays.
 *
 * Qualquer utilizador autenticado pode consultar MetalThursdays. A criação é
 * permitida livremente a utilizadores com privilégios administrativos e, para
 * utilizadores comuns, apenas quando existe uma reserva ainda pendente.
 *
 * A alteração é permitida ao autor e ao utilizador que criou o registo.
 * A eliminação e restauração ficam limitadas ao criador, exceto para o
 * superadministrador.
 *
 * @since 1.0.0
 */
final class PoliticaMetalThursday
{
    /**
     * Autoriza antecipadamente todas as ações do superadministrador.
     *
     * O nome permanece em inglês por corresponder ao método especial
     * reconhecido pelo sistema de autorização do Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  string  $capacidade  Capacidade que está a ser verificada.
     * @return bool|null Verdadeiro para o superadministrador ou nulo para
     *                   continuar a avaliação normal.
     *
     * @since 1.0.0
     */
    public function before(
        Utilizador $utilizador,
        string $capacidade,
    ): ?bool {
        return $utilizador->eSuperAdministrador()
            ? true
            : null;
    }

    /**
     * Determina se o utilizador pode consultar a lista de MetalThursdays.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 2.0.0
     */
    public function viewAny(
        Utilizador $utilizador,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode consultar uma MetalThursday.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  MetalThursday  $metalThursday  MetalThursday consultada.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 2.0.0
     */
    public function view(
        Utilizador $utilizador,
        MetalThursday $metalThursday,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode criar uma MetalThursday.
     *
     * Utilizadores com privilégios administrativos podem criar livremente.
     * Um utilizador comum apenas pode criar quando possui uma reserva ainda
     * pendente, isto é, sem MetalThursday associada.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Verdadeiro quando a criação é permitida.
     *
     * @since 2.0.0
     */
    public function create(
        Utilizador $utilizador,
    ): bool {
        if ($utilizador->possuiPrivilegiosAdministrativos()) {
            return true;
        }

        return $utilizador
            ->reservasMetalThursday()
            ->whereNull(
                'metal_thursday_id',
            )
            ->exists();
    }

    /**
     * Determina se o utilizador pode alterar uma MetalThursday.
     *
     * A alteração é permitida ao autor e ao utilizador que criou o registo.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  MetalThursday  $metalThursday  MetalThursday a alterar.
     * @return bool Verdadeiro quando o utilizador é autor ou criador.
     *
     * @since 1.0.0
     */
    public function update(
        Utilizador $utilizador,
        MetalThursday $metalThursday,
    ): bool {
        return $this->utilizadorEAutor(
            $utilizador,
            $metalThursday,
        )
            || $this->utilizadorECriador(
                $utilizador,
                $metalThursday,
            );
    }

    /**
     * Determina se o utilizador pode eliminar uma MetalThursday.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  MetalThursday  $metalThursday  MetalThursday a eliminar.
     * @return bool Verdadeiro quando o utilizador criou o registo.
     *
     * @since 1.0.0
     */
    public function delete(
        Utilizador $utilizador,
        MetalThursday $metalThursday,
    ): bool {
        return $this->utilizadorECriador(
            $utilizador,
            $metalThursday,
        );
    }

    /**
     * Determina se o utilizador pode restaurar uma MetalThursday eliminada.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  MetalThursday  $metalThursday  MetalThursday a restaurar.
     * @return bool Verdadeiro quando o utilizador criou o registo.
     *
     * @since 2.0.0
     */
    public function restore(
        Utilizador $utilizador,
        MetalThursday $metalThursday,
    ): bool {
        return $this->utilizadorECriador(
            $utilizador,
            $metalThursday,
        );
    }

    /**
     * Determina se o utilizador pode eliminar definitivamente uma
     * MetalThursday.
     *
     * O método `before` autoriza antecipadamente o superadministrador. Todos
     * os restantes utilizadores recebem falso.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  MetalThursday  $metalThursday  MetalThursday a eliminar
     *                                        definitivamente.
     * @return bool Falso para utilizadores que não são superadministradores.
     *
     * @since 2.0.0
     */
    public function forceDelete(
        Utilizador $utilizador,
        MetalThursday $metalThursday,
    ): bool {
        return false;
    }

    /**
     * Determina se o utilizador é o autor da MetalThursday.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  MetalThursday  $metalThursday  MetalThursday verificada.
     * @return bool Verdadeiro quando os identificadores coincidem.
     *
     * @since 2.0.0
     */
    private function utilizadorEAutor(
        Utilizador $utilizador,
        MetalThursday $metalThursday,
    ): bool {
        return $this->identificadoresCoincidem(
            $utilizador->getKey(),
            $metalThursday->autor_id,
        );
    }

    /**
     * Determina se o utilizador criou a MetalThursday.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  MetalThursday  $metalThursday  MetalThursday verificada.
     * @return bool Verdadeiro quando os identificadores coincidem.
     *
     * @since 2.0.0
     */
    private function utilizadorECriador(
        Utilizador $utilizador,
        MetalThursday $metalThursday,
    ): bool {
        return $this->identificadoresCoincidem(
            $utilizador->getKey(),
            $metalThursday->criado_por_id,
        );
    }

    /**
     * Compara dois identificadores persistidos.
     *
     * @param  mixed  $primeiroIdentificador  Primeiro identificador.
     * @param  mixed  $segundoIdentificador  Segundo identificador.
     * @return bool Verdadeiro quando ambos são válidos e coincidem.
     *
     * @since 2.0.0
     */
    private function identificadoresCoincidem(
        mixed $primeiroIdentificador,
        mixed $segundoIdentificador,
    ): bool {
        return is_numeric(
            $primeiroIdentificador,
        )
            && is_numeric(
                $segundoIdentificador,
            )
            && (int) $primeiroIdentificador
            === (int) $segundoIdentificador;
    }
}
