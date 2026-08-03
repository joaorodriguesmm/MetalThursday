<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Autenticacao\Utilizador;

/**
 * Define as regras de autorização aplicáveis à gestão dos utilizadores.
 *
 * A consulta e a gestão dos utilizadores ficam exclusivamente reservadas a
 * superadministradores com acesso ativo.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class PoliticaUtilizador
{
    /**
     * Autoriza antecipadamente todas as ações do superadministrador ativo.
     *
     * O nome permanece em inglês por corresponder ao método especial
     * reconhecido pelo sistema de autorização do Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  string  $capacidade  Capacidade que está a ser verificada.
     * @return bool|null Verdadeiro para um superadministrador ativo ou nulo
     *                   para continuar a avaliação normal.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function before(
        Utilizador $utilizador,
        string $capacidade,
    ): ?bool {
        return $utilizador->eSuperAdministrador()
            && $utilizador->temAcessoAtivo()
            ? true
            : null;
    }

    /**
     * Determina se o utilizador pode consultar a lista de utilizadores.
     *
     * O método `before` autoriza o superadministrador ativo. Todos os
     * restantes utilizadores recebem falso.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Falso para utilizadores que não são superadministradores
     *              ativos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function viewAny(
        Utilizador $utilizador,
    ): bool {
        return false;
    }

    /**
     * Determina se o utilizador pode consultar outro utilizador.
     *
     * O método `before` autoriza o superadministrador ativo. Todos os
     * restantes utilizadores recebem falso.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Utilizador  $utilizadorConsultado  Utilizador consultado.
     * @return bool Falso para utilizadores que não são superadministradores
     *              ativos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function view(
        Utilizador $utilizador,
        Utilizador $utilizadorConsultado,
    ): bool {
        return false;
    }
}
