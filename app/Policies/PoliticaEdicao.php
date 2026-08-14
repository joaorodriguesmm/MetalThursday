<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;

/**
 * Define as regras de autorização aplicáveis às edições.
 *
 * Qualquer utilizador autenticado pode consultar edições. A criação,
 * alteração, eliminação e restauração ficam reservadas aos utilizadores com
 * privilégios administrativos. A eliminação definitiva fica exclusivamente
 * reservada ao superadministrador.
 *
 * @since 1.0.0
 */
final class PoliticaEdicao
{
    /**
     * Autoriza antecipadamente todas as ações do superadministrador.
     *
     * O nome permanece em inglês por corresponder ao método especial
     * reconhecido pelo sistema de autorização do Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  string  $capacidade  Capacidade verificada.
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
     * Determina se o utilizador pode consultar a lista de edições.
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
     * Determina se o utilizador pode consultar uma edição.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Edicao  $edicao  Edição consultada.
     * @return bool Verdadeiro para qualquer utilizador autenticado.
     *
     * @since 1.0.0
     */
    public function view(
        Utilizador $utilizador,
        Edicao $edicao,
    ): bool {
        return true;
    }

    /**
     * Determina se o utilizador pode criar uma edição.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Verdadeiro para utilizadores com privilégios
     *              administrativos.
     *
     * @since 1.0.0
     */
    public function create(
        Utilizador $utilizador,
    ): bool {
        return $utilizador->possuiPrivilegiosAdministrativos();
    }

    /**
     * Determina se o utilizador pode alterar uma edição.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Edicao  $edicao  Edição a alterar.
     * @return bool Verdadeiro para utilizadores com privilégios
     *              administrativos.
     *
     * @since 1.0.0
     */
    public function update(
        Utilizador $utilizador,
        Edicao $edicao,
    ): bool {
        return $utilizador->possuiPrivilegiosAdministrativos();
    }

    /**
     * Determina se o utilizador pode eliminar uma edição.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Edicao  $edicao  Edição a eliminar.
     * @return bool Verdadeiro para utilizadores com privilégios
     *              administrativos.
     *
     * @since 1.0.0
     */
    public function delete(
        Utilizador $utilizador,
        Edicao $edicao,
    ): bool {
        return $utilizador->possuiPrivilegiosAdministrativos();
    }

    /**
     * Determina se o utilizador pode restaurar uma edição eliminada.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Edicao  $edicao  Edição a restaurar.
     * @return bool Verdadeiro para utilizadores com privilégios
     *              administrativos.
     *
     * @since 2.0.0
     */
    public function restore(
        Utilizador $utilizador,
        Edicao $edicao,
    ): bool {
        return $utilizador->possuiPrivilegiosAdministrativos();
    }

    /**
     * Determina se o utilizador pode eliminar definitivamente uma edição.
     *
     * O método `before` autoriza antecipadamente o superadministrador. Todos
     * os restantes utilizadores recebem falso.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Edicao  $edicao  Edição a eliminar definitivamente.
     * @return bool Falso para utilizadores que não são superadministradores.
     *
     * @since 2.0.0
     */
    public function forceDelete(
        Utilizador $utilizador,
        Edicao $edicao,
    ): bool {
        return false;
    }
}
