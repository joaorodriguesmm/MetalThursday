<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa os papéis atribuíveis aos utilizadores.
 *
 * Os valores correspondem aos valores permitidos pela coluna `users.papel`.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
enum PapelUtilizador: string
{
    /**
     * Utilizador comum da aplicação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case Utilizador = 'utilizador';

    /**
     * Utilizador com permissões administrativas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case Administrador = 'administrador';

    /**
     * Utilizador com acesso administrativo global.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case SuperAdministrador = 'super_administrador';

    /**
     * Determina se o papel possui privilégios administrativos.
     *
     * @return bool - Verdadeiro para administradores e
     *              superadministradores.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function possuiPrivilegiosAdministrativos(): bool
    {
        return match ($this) {
            self::Administrador,
            self::SuperAdministrador => true,

            self::Utilizador => false,
        };
    }

    /**
     * Determina se o papel corresponde ao superadministrador.
     *
     * @return bool - Verdadeiro apenas para o superadministrador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function eSuperAdministrador(): bool
    {
        return $this === self::SuperAdministrador;
    }
}
