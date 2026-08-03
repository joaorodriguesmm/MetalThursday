<?php

declare(strict_types=1);

namespace Tests\Unit\Enumeracoes;

use App\Enumeracoes\PapelUtilizador;
use PHPUnit\Framework\TestCase;

/**
 * Testa a enumeração dos papéis dos utilizadores.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class PapelUtilizadorTest extends TestCase
{
    /**
     * Confirma que os valores públicos portugueses são reconhecidos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_reconhece_valores_publicos_portugueses(): void
    {
        self::assertSame(
            PapelUtilizador::Utilizador,
            PapelUtilizador::tentarCriar(
                'utilizador',
            ),
        );

        self::assertSame(
            PapelUtilizador::Administrador,
            PapelUtilizador::tentarCriar(
                'administrador',
            ),
        );

        self::assertSame(
            PapelUtilizador::SuperAdministrador,
            PapelUtilizador::tentarCriar(
                'super_administrador',
            ),
        );
    }

    /**
     * Confirma que os valores textuais são normalizados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_normaliza_espacos_e_maiusculas(): void
    {
        self::assertSame(
            PapelUtilizador::Utilizador,
            PapelUtilizador::tentarCriar(
                '  UTILIZADOR  ',
            ),
        );

        self::assertSame(
            PapelUtilizador::Administrador,
            PapelUtilizador::tentarCriar(
                'ADMINISTRADOR',
            ),
        );

        self::assertSame(
            PapelUtilizador::SuperAdministrador,
            PapelUtilizador::tentarCriar(
                'SUPER_ADMINISTRADOR',
            ),
        );
    }

    /**
     * Confirma que nomes alternativos e valores inválidos são rejeitados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_rejeita_aliases_e_valores_invalidos(): void
    {
        self::assertNull(
            PapelUtilizador::tentarCriar(
                'superadministrador',
            ),
        );

        self::assertNull(
            PapelUtilizador::tentarCriar(
                'admin',
            ),
        );

        self::assertNull(
            PapelUtilizador::tentarCriar(
                'editor',
            ),
        );

        self::assertNull(
            PapelUtilizador::tentarCriar(
                '',
            ),
        );

        self::assertNull(
            PapelUtilizador::tentarCriar(
                1,
            ),
        );

        self::assertNull(
            PapelUtilizador::tentarCriar(
                null,
            ),
        );
    }

    /**
     * Confirma as etiquetas apresentadas ao utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_devolve_etiquetas_portuguesas(): void
    {
        self::assertSame(
            'Utilizador',
            PapelUtilizador::Utilizador->etiqueta(),
        );

        self::assertSame(
            'Administrador',
            PapelUtilizador::Administrador->etiqueta(),
        );

        self::assertSame(
            'Superadministrador',
            PapelUtilizador::SuperAdministrador->etiqueta(),
        );
    }

    /**
     * Confirma quais papéis possuem privilégios administrativos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_distingue_privilegios_administrativos(): void
    {
        self::assertFalse(
            PapelUtilizador::Utilizador
                ->possuiPrivilegiosAdministrativos(),
        );

        self::assertTrue(
            PapelUtilizador::Administrador
                ->possuiPrivilegiosAdministrativos(),
        );

        self::assertTrue(
            PapelUtilizador::SuperAdministrador
                ->possuiPrivilegiosAdministrativos(),
        );
    }

    /**
     * Confirma que apenas o papel global é um superadministrador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_distingue_o_superadministrador(): void
    {
        self::assertFalse(
            PapelUtilizador::Utilizador
                ->eSuperAdministrador(),
        );

        self::assertFalse(
            PapelUtilizador::Administrador
                ->eSuperAdministrador(),
        );

        self::assertTrue(
            PapelUtilizador::SuperAdministrador
                ->eSuperAdministrador(),
        );
    }
}
