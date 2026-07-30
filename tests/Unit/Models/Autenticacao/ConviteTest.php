<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o comportamento de domínio do modelo dos convites.
 *
 * Estes testes não utilizam a base de dados. Validam apenas as regras de
 * estado, normalização e segurança implementadas diretamente pelo modelo.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
final class ConviteTest extends TestCase
{
    /**
     * Confirma que o hash remove os espaços exteriores do código.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function calcula_o_hash_do_codigo_normalizado(): void
    {
        $hashEsperado = hash(
            'sha256',
            'MT-CODIGO-TESTE',
        );

        $hashCalculado = Convite::calcularHashCodigo(
            '  MT-CODIGO-TESTE  ',
        );

        self::assertSame(
            $hashEsperado,
            $hashCalculado,
        );
    }

    /**
     * Confirma que os códigos permanecem sensíveis à capitalização.
     *
     * Ambos os códigos utilizados respeitam o comprimento e o padrão
     * definidos pelo modelo.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function distingue_maiusculas_de_minusculas_no_codigo(): void
    {
        $hashMaiusculas = Convite::calcularHashCodigo(
            'MT-CODIGO1',
        );

        $hashMinusculas = Convite::calcularHashCodigo(
            'mt-codigo1',
        );

        self::assertNotSame(
            $hashMaiusculas,
            $hashMinusculas,
        );
    }

    /**
     * Confirma que um código vazio não pode ser convertido em hash.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function rejeita_um_codigo_vazio(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O código do convite não é válido.',
        );

        Convite::calcularHashCodigo(
            '   ',
        );
    }

    /**
     * Confirma que códigos demasiado curtos são rejeitados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_um_codigo_demasiado_curto(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O código do convite não é válido.',
        );

        Convite::calcularHashCodigo(
            'MT-CODIGO',
        );
    }

    /**
     * Confirma que códigos com caracteres não permitidos são rejeitados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_um_codigo_com_caracteres_invalidos(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O código do convite não é válido.',
        );

        Convite::calcularHashCodigo(
            'MT-CODIGO!TESTE',
        );
    }

    /**
     * Confirma que o modelo guarda apenas o hash do código.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function define_apenas_o_hash_do_codigo_no_modelo(): void
    {
        $convite = new Convite;

        $convite->definirCodigo(
            'MT-CODIGO-SEGURO',
        );

        self::assertSame(
            hash(
                'sha256',
                'MT-CODIGO-SEGURO',
            ),
            $convite->codigo_hash,
        );

        self::assertTrue(
            $convite->correspondeAoCodigo(
                'MT-CODIGO-SEGURO',
            ),
        );

        self::assertFalse(
            $convite->correspondeAoCodigo(
                'MT-CODIGO-INCORRETO',
            ),
        );
    }

    /**
     * Confirma que um convite pendente e sem expiração está disponível.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function considera_disponivel_um_convite_pendente(): void
    {
        $convite = new Convite;

        $momento = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        self::assertFalse(
            $convite->foiUtilizado(),
        );

        self::assertFalse(
            $convite->foiRevogado(),
        );

        self::assertFalse(
            $convite->estaExpirado(
                $momento,
            ),
        );

        self::assertTrue(
            $convite->estaDisponivel(
                $momento,
            ),
        );
    }

    /**
     * Confirma que o convite expira exatamente no momento definido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function considera_expirado_o_convite_no_limite_temporal(): void
    {
        $momentoExpiracao = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        $convite = new Convite([
            'expira_em' => $momentoExpiracao,
        ]);

        self::assertTrue(
            $convite->estaExpirado(
                $momentoExpiracao,
            ),
        );

        self::assertFalse(
            $convite->estaDisponivel(
                $momentoExpiracao,
            ),
        );
    }

    /**
     * Confirma que a utilização associa o utilizador e regista o momento.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function utiliza_um_convite_disponivel(): void
    {
        $momentoUtilizacao = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        $utilizador = new Utilizador;

        $utilizador->forceFill([
            'id' => 42,
        ]);

        $utilizador->exists = true;

        $convite = new Convite;

        $convite->utilizar(
            $utilizador,
            $momentoUtilizacao,
        );

        self::assertSame(
            42,
            $convite->utilizado_por_id,
        );

        self::assertNotNull(
            $convite->utilizado_em,
        );

        self::assertTrue(
            $convite->utilizado_em->equalTo(
                $momentoUtilizacao,
            ),
        );

        self::assertTrue(
            $convite->relationLoaded(
                'utilizador',
            ),
        );

        self::assertSame(
            $utilizador,
            $convite->utilizador,
        );

        self::assertTrue(
            $convite->foiUtilizado(),
        );

        self::assertFalse(
            $convite->estaDisponivel(
                $momentoUtilizacao,
            ),
        );
    }

    /**
     * Confirma que um convite revogado não pode ser utilizado.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function impede_a_utilizacao_de_um_convite_revogado(): void
    {
        $momento = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        $utilizador = new Utilizador;

        $utilizador->forceFill([
            'id' => 42,
        ]);

        $utilizador->exists = true;

        $convite = new Convite;

        $convite->revogar(
            $momento,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->expectExceptionMessage(
            'O convite não está disponível para utilização.',
        );

        $convite->utilizar(
            $utilizador,
            $momento,
        );
    }

    /**
     * Confirma que revogar novamente não altera o momento original.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function mantem_a_primeira_data_ao_revogar_novamente(): void
    {
        $primeiroMomento = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        $segundoMomento = CarbonImmutable::parse(
            '2026-07-22 12:00:00',
        );

        $convite = new Convite;

        $convite->revogar(
            $primeiroMomento,
        );

        $convite->revogar(
            $segundoMomento,
        );

        self::assertNotNull(
            $convite->revogado_em,
        );

        self::assertTrue(
            $convite->revogado_em->equalTo(
                $primeiroMomento,
            ),
        );
    }

    /**
     * Confirma que um convite utilizado não pode ser revogado.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function impede_a_revogacao_de_um_convite_utilizado(): void
    {
        $momento = CarbonImmutable::parse(
            '2026-07-21 12:00:00',
        );

        $utilizador = new Utilizador;

        $utilizador->forceFill([
            'id' => 42,
        ]);

        $utilizador->exists = true;

        $convite = new Convite;

        $convite->utilizar(
            $utilizador,
            $momento,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->expectExceptionMessage(
            'Não é possível revogar um convite já utilizado.',
        );

        $convite->revogar(
            $momento,
        );
    }
}
