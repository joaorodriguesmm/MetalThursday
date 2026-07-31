<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os estados personalizados da factory dos convites.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class FactoriesConviteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma o código conhecido e as associações aos utilizadores.
     *
     * O estado utilizado deve também remover qualquer revogação anterior.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_convite_utilizado_com_codigo_e_relacoes_conhecidas(): void
    {
        $codigo =
            'MT-Convite-Factory-Conhecido';

        $criador = Utilizador::factory()
            ->create();

        $utilizador = Utilizador::factory()
            ->create();

        $convite = Convite::factory()
            ->comCodigo(
                $codigo,
            )
            ->criadoPor(
                $criador,
            )
            ->revogado()
            ->utilizadoPor(
                $utilizador,
            )
            ->create();

        self::assertSame(
            $criador->getKey(),
            $convite->criado_por_id,
        );

        self::assertSame(
            $utilizador->getKey(),
            $convite->utilizado_por_id,
        );

        self::assertNotNull(
            $convite->utilizado_em,
        );

        self::assertNull(
            $convite->revogado_em,
        );

        self::assertTrue(
            $convite->correspondeAoCodigo(
                $codigo,
            ),
        );

        self::assertTrue(
            $convite->foiUtilizado(),
        );
    }

    /**
     * Confirma a remoção do destinatário e do prazo de expiração.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_convite_sem_destinatario_nem_expiracao(): void
    {
        $convite = Convite::factory()
            ->semEmailDestino()
            ->semExpiracao()
            ->create();

        self::assertNull(
            $convite->email_destino,
        );

        self::assertNull(
            $convite->expira_em,
        );

        self::assertTrue(
            $convite->estaDisponivel(),
        );
    }

    /**
     * Confirma que o estado expirado produz um convite indisponível.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_convite_expirado_e_pendente(): void
    {
        $convite = Convite::factory()
            ->expirado()
            ->create();

        self::assertTrue(
            $convite->estaExpirado(),
        );

        self::assertFalse(
            $convite->foiUtilizado(),
        );

        self::assertFalse(
            $convite->foiRevogado(),
        );

        self::assertFalse(
            $convite->estaDisponivel(),
        );
    }

    /**
     * Confirma que o estado revogado produz um convite não utilizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_convite_revogado_e_nao_utilizado(): void
    {
        $convite = Convite::factory()
            ->revogado()
            ->create();

        self::assertTrue(
            $convite->foiRevogado(),
        );

        self::assertFalse(
            $convite->foiUtilizado(),
        );

        self::assertFalse(
            $convite->estaDisponivel(),
        );
    }

    /**
     * Confirma que um utilizador não persistido não pode ser associado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_utilizador_nao_persistido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        Convite::factory()
            ->criadoPor(
                new Utilizador,
            );
    }
}
