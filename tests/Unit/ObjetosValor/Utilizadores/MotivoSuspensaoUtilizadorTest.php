<?php

declare(strict_types=1);

namespace Tests\Unit\ObjetosValor\Utilizadores;

use App\ObjetosValor\Utilizadores\MotivoSuspensaoUtilizador;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Testa o objeto de valor dos motivos de suspensão.
 *
 * @since 2.0.0
 */
final class MotivoSuspensaoUtilizadorTest extends TestCase
{
    /**
     * Confirma a normalização dos espaços do motivo.
     *
     * @since 2.0.0
     */
    public function test_normaliza_os_espacos_do_motivo(): void
    {
        $motivo = MotivoSuspensaoUtilizador::deTexto(
            "\t Suspensão \n temporária   por abuso. \r\n",
        );

        self::assertSame(
            'Suspensão temporária por abuso.',
            $motivo->valor(),
        );
    }

    /**
     * Confirma que a grafia e a pontuação são preservadas.
     *
     * @since 2.0.0
     */
    public function test_preserva_a_grafia_do_motivo(): void
    {
        $motivo = MotivoSuspensaoUtilizador::deTexto(
            'Incumprimento reiterado: situação n.º 3.',
        );

        self::assertSame(
            'Incumprimento reiterado: situação n.º 3.',
            $motivo->valor(),
        );
    }

    /**
     * Confirma a comparação entre motivos normalizados.
     *
     * @since 2.0.0
     */
    public function test_compara_motivos_normalizados(): void
    {
        $primeiro = MotivoSuspensaoUtilizador::deTexto(
            '  Motivo   válido. ',
        );

        $segundo = MotivoSuspensaoUtilizador::deTexto(
            'Motivo válido.',
        );

        $diferente = MotivoSuspensaoUtilizador::deTexto(
            'Outro motivo.',
        );

        self::assertTrue(
            $primeiro->igualA(
                $segundo,
            ),
        );

        self::assertFalse(
            $primeiro->igualA(
                $diferente,
            ),
        );
    }

    /**
     * Confirma as conversões textual e JSON do motivo.
     *
     * @since 2.0.0
     */
    public function test_converte_o_motivo_para_texto_e_json(): void
    {
        $motivo = MotivoSuspensaoUtilizador::deTexto(
            'Motivo válido.',
        );

        self::assertSame(
            'Motivo válido.',
            (string) $motivo,
        );

        self::assertSame(
            '"Motivo válido."',
            json_encode(
                $motivo,
                JSON_THROW_ON_ERROR
                    | JSON_UNESCAPED_UNICODE,
            ),
        );
    }

    /**
     * Confirma que o comprimento máximo é aceite.
     *
     * @since 2.0.0
     */
    public function test_aceita_o_comprimento_maximo(): void
    {
        $texto = str_repeat(
            'a',
            MotivoSuspensaoUtilizador::COMPRIMENTO_MAXIMO,
        );

        $motivo = MotivoSuspensaoUtilizador::deTexto(
            $texto,
        );

        self::assertSame(
            MotivoSuspensaoUtilizador::COMPRIMENTO_MAXIMO,
            mb_strlen(
                $motivo->valor(),
            ),
        );
    }

    /**
     * Confirma que um motivo vazio é rejeitado.
     *
     * @since 2.0.0
     */
    public function test_rejeita_um_motivo_vazio(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        MotivoSuspensaoUtilizador::deTexto(
            " \t\n ",
        );
    }

    /**
     * Confirma que um motivo demasiado longo é rejeitado.
     *
     * @since 2.0.0
     */
    public function test_rejeita_um_motivo_demasiado_longo(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        MotivoSuspensaoUtilizador::deTexto(
            str_repeat(
                'a',
                MotivoSuspensaoUtilizador::COMPRIMENTO_MAXIMO + 1,
            ),
        );
    }

    /**
     * Confirma que caracteres de controlo inválidos são rejeitados.
     *
     * @since 2.0.0
     */
    public function test_rejeita_caracteres_de_controlo_invalidos(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        MotivoSuspensaoUtilizador::deTexto(
            "Motivo\x07inválido.",
        );
    }

    /**
     * Confirma que texto com codificação inválida é rejeitado.
     *
     * @since 2.0.0
     */
    public function test_rejeita_texto_com_codificacao_invalida(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        MotivoSuspensaoUtilizador::deTexto(
            "\xC3\x28",
        );
    }
}
