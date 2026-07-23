<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa os papéis atribuíveis aos utilizadores.
 *
 * Os valores correspondem aos valores permitidos pela coluna
 * `utilizadores.papel`.
 *
 * @since 2.0.0
 *
 * @version 1.1.0
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
     * Tenta criar um papel a partir de um valor recebido.
     *
     * São também aceites aliases utilizados anteriormente pela aplicação.
     * A comparação ignora espaços adicionais e diferenças entre letras
     * maiúsculas e minúsculas.
     *
     * @param  mixed  $valor  Valor a converter.
     * @return self|null Papel correspondente ou nulo quando o valor não é
     *                   reconhecido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function tentarCriar(
        mixed $valor,
    ): ?self {
        if (! is_string($valor)) {
            return null;
        }

        $valorNormalizado = mb_strtolower(
            trim($valor),
        );

        return match ($valorNormalizado) {
            self::Utilizador->value,
            'user' => self::Utilizador,

            self::Administrador->value,
            'admin' => self::Administrador,

            self::SuperAdministrador->value,
            'superadmin',
            'super_admin' => self::SuperAdministrador,

            default => null,
        };
    }

    /**
     * Determina se o papel possui privilégios administrativos.
     *
     * @return bool Verdadeiro para administradores e superadministradores.
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
     * Determina se o papel corresponde a um administrador.
     *
     * Este método não considera o superadministrador como administrador
     * comum.
     *
     * @return bool Verdadeiro apenas para o administrador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function eAdministrador(): bool
    {
        return $this === self::Administrador;
    }

    /**
     * Determina se o papel corresponde ao superadministrador.
     *
     * @return bool Verdadeiro apenas para o superadministrador.
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
