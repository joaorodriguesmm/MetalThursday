<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa os papéis atribuíveis aos utilizadores.
 *
 * Os valores correspondem diretamente aos valores permitidos pela coluna
 * `utilizadores.papel`.
 *
 * @since 2.0.0
 */
enum PapelUtilizador: string
{
    /**
     * Utilizador comum da aplicação.
     *
     * @since 2.0.0
     */
    case Utilizador = 'utilizador';

    /**
     * Utilizador com permissões administrativas.
     *
     * @since 2.0.0
     */
    case Administrador = 'administrador';

    /**
     * Utilizador com acesso administrativo global.
     *
     * @since 2.0.0
     */
    case SuperAdministrador = 'super_administrador';

    /**
     * Tenta criar um papel a partir de um valor textual.
     *
     * A normalização limita-se à remoção de espaços exteriores e à conversão
     * para minúsculas. Apenas os valores públicos definidos pela própria
     * enumeração são aceites.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return self|null Papel correspondente ou nulo.
     *
     * @since 2.0.0
     */
    public static function tentarCriar(
        mixed $valor,
    ): ?self {
        if (! is_string($valor)) {
            return null;
        }

        return self::tryFrom(
            mb_strtolower(
                trim(
                    $valor,
                ),
            ),
        );
    }

    /**
     * Obtém a etiqueta apresentada ao utilizador.
     *
     * @return string Etiqueta do papel.
     *
     * @since 2.0.0
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Utilizador => 'Utilizador',
            self::Administrador => 'Administrador',
            self::SuperAdministrador => 'Superadministrador',
        };
    }

    /**
     * Determina se o papel possui privilégios administrativos.
     *
     * @return bool Verdadeiro para administradores e superadministradores.
     *
     * @since 2.0.0
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
     * @return bool Verdadeiro apenas para o superadministrador.
     *
     * @since 2.0.0
     */
    public function eSuperAdministrador(): bool
    {
        return $this === self::SuperAdministrador;
    }
}
