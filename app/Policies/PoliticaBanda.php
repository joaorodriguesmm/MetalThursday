<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Banda;

/**
 * Define as regras de autorização aplicáveis às bandas.
 *
 * Qualquer utilizador autenticado pode consultar e criar bandas. A alteração
 * e eliminação ficam limitadas ao utilizador que criou o registo, exceto para
 * o superadministrador.
 *
 * @since 1.0.0
 */
final class PoliticaBanda
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
     * Determina se o utilizador pode consultar a lista de bandas.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 1.0.0
     */
    public function viewAny(
        Utilizador $utilizador,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode consultar uma banda.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Banda  $banda  Banda consultada.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 1.0.0
     */
    public function view(
        Utilizador $utilizador,
        Banda $banda,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode criar uma banda.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 1.0.0
     */
    public function create(
        Utilizador $utilizador,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode alterar uma banda.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Banda  $banda  Banda a alterar.
     * @return bool Verdadeiro quando o utilizador criou a banda.
     *
     * @since 1.0.0
     */
    public function update(
        Utilizador $utilizador,
        Banda $banda,
    ): bool {
        return $this->utilizadorCriouBanda(
            $utilizador,
            $banda,
        );
    }

    /**
     * Determina se o utilizador pode eliminar uma banda.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Banda  $banda  Banda a eliminar.
     * @return bool Verdadeiro quando o utilizador criou a banda.
     *
     * @since 1.0.0
     */
    public function delete(
        Utilizador $utilizador,
        Banda $banda,
    ): bool {
        return $this->utilizadorCriouBanda(
            $utilizador,
            $banda,
        );
    }

    /**
     * Determina se o utilizador pode restaurar uma banda eliminada.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Banda  $banda  Banda a restaurar.
     * @return bool Verdadeiro quando o utilizador criou a banda.
     *
     * @since 2.0.0
     */
    public function restore(
        Utilizador $utilizador,
        Banda $banda,
    ): bool {
        return $this->utilizadorCriouBanda(
            $utilizador,
            $banda,
        );
    }

    /**
     * Determina se o utilizador pode eliminar definitivamente uma banda.
     *
     * A eliminação definitiva fica reservada ao superadministrador. Como o
     * método `before` já o autoriza, os restantes utilizadores recebem falso.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Banda  $banda  Banda a eliminar definitivamente.
     * @return bool Falso para utilizadores comuns.
     *
     * @since 2.0.0
     */
    public function forceDelete(
        Utilizador $utilizador,
        Banda $banda,
    ): bool {
        return false;
    }

    /**
     * Determina se uma banda foi criada pelo utilizador indicado.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Banda  $banda  Banda verificada.
     * @return bool Verdadeiro quando os identificadores coincidem.
     *
     * @since 2.0.0
     */
    private function utilizadorCriouBanda(
        Utilizador $utilizador,
        Banda $banda,
    ): bool {
        $identificadorUtilizador =
            $utilizador->getKey();

        $identificadorCriador =
            $banda->criado_por_id;

        return is_numeric($identificadorUtilizador)
            && is_numeric($identificadorCriador)
            && (int) $identificadorUtilizador
            === (int) $identificadorCriador;
    }
}
