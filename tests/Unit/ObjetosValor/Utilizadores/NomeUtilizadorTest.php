<?php

declare(strict_types=1);

namespace Tests\Unit\ObjetosValor\Utilizadores;

use App\ObjetosValor\Utilizadores\NomeUtilizador;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Testa o objeto de valor do nome do utilizador.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class NomeUtilizadorTest extends TestCase
{
    /**
     * Confirma que espaços exteriores e consecutivos são removidos.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_normaliza_espacos_do_nome(): void
    {
        $nome = NomeUtilizador::deTexto(
            "  João \t Rodrigues \n Silva  ",
        );

        self::assertSame(
            'João Rodrigues Silva',
            $nome->valor(),
        );

        self::assertSame(
            'João Rodrigues Silva',
            (string) $nome,
        );

        self::assertSame(
            'João Rodrigues Silva',
            $nome->jsonSerialize(),
        );
    }

    /**
     * Confirma a obtenção do primeiro nome.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_obtem_o_primeiro_nome(): void
    {
        $nome = NomeUtilizador::deTexto(
            'João Rodrigues Silva',
        );

        self::assertSame(
            'João',
            $nome->primeiroNome(),
        );
    }

    /**
     * Confirma as iniciais de um nome composto.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_obtem_as_iniciais_de_nome_composto(): void
    {
        $nome = NomeUtilizador::deTexto(
            'João Rodrigues Silva',
        );

        self::assertSame(
            'JS',
            $nome->iniciais(),
        );
    }

    /**
     * Confirma as iniciais de um nome simples.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_obtem_duas_iniciais_de_nome_simples(): void
    {
        $nome = NomeUtilizador::deTexto('Álvaro');

        self::assertSame(
            'ÁL',
            $nome->iniciais(),
        );
    }

    /**
     * Confirma que dois nomes com o mesmo valor são iguais.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_compara_nomes_normalizados(): void
    {
        $primeiroNome = NomeUtilizador::deTexto(
            'João Rodrigues',
        );

        $segundoNome = NomeUtilizador::deTexto(
            '  João   Rodrigues ',
        );

        self::assertTrue(
            $primeiroNome->igualA($segundoNome),
        );
    }

    /**
     * Confirma que nomes diferentes não são considerados iguais.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_distingue_nomes_diferentes(): void
    {
        $primeiroNome = NomeUtilizador::deTexto(
            'João Rodrigues',
        );

        $segundoNome = NomeUtilizador::deTexto(
            'João Silva',
        );

        self::assertFalse(
            $primeiroNome->igualA($segundoNome),
        );
    }

    /**
     * Confirma que um nome vazio é rejeitado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_rejeita_nome_vazio(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        NomeUtilizador::deTexto('   ');
    }

    /**
     * Confirma que um nome demasiado curto é rejeitado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_rejeita_nome_demasiado_curto(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        NomeUtilizador::deTexto('Jo');
    }

    /**
     * Confirma que um nome demasiado longo é rejeitado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function test_rejeita_nome_demasiado_longo(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        NomeUtilizador::deTexto(
            str_repeat('a', 256),
        );
    }
}
