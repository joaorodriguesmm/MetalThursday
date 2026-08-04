<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
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
 * @version 2.0.0
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
     * @version 2.0.0
     */
    #[Test]
    public function cria_convite_utilizado_com_codigo_e_relacoes_conhecidas(): void
    {
        $codigo =
            'MT-Convite-Factory-Conhecido';

        $criador =
            Utilizador::factory()
                ->create();

        $responsavelRevogacao =
            $this->criarSuperAdministrador();

        $utilizador =
            Utilizador::factory()
                ->create();

        $convite =
            Convite::factory()
                ->comCodigo(
                    $codigo,
                )
                ->criadoPor(
                    $criador,
                )
                ->revogadoPor(
                    $responsavelRevogacao,
                )
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

        self::assertNull(
            $convite->revogado_por_id,
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
        $convite =
            Convite::factory()
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
        $convite =
            Convite::factory()
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

        self::assertNull(
            $convite->revogado_por_id,
        );

        self::assertFalse(
            $convite->estaDisponivel(),
        );
    }

    /**
     * Confirma que o estado revogado conserva o responsável.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function cria_convite_revogado_e_nao_utilizado(): void
    {
        $responsavel =
            $this->criarSuperAdministrador();

        $convite =
            Convite::factory()
                ->revogadoPor(
                    $responsavel,
                )
                ->create()
                ->fresh([
                    'responsavelRevogacao',
                ]);

        self::assertNotNull(
            $convite,
        );

        self::assertTrue(
            $convite->foiRevogado(),
        );

        self::assertFalse(
            $convite->foiUtilizado(),
        );

        self::assertFalse(
            $convite->estaDisponivel(),
        );

        self::assertSame(
            (int) $responsavel->getKey(),
            $convite->revogado_por_id,
        );

        self::assertTrue(
            $convite->responsavelRevogacao->is(
                $responsavel,
            ),
        );
    }

    /**
     * Confirma que um criador não persistido não pode ser associado.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    #[Test]
    public function rejeita_criador_nao_persistido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        Convite::factory()
            ->criadoPor(
                new Utilizador,
            );
    }

    /**
     * Confirma que um responsável não persistido não pode ser associado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_responsavel_revogacao_nao_persistido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        Convite::factory()
            ->revogadoPor(
                new Utilizador,
            );
    }

    /**
     * Cria um superadministrador ativo.
     *
     * @return Utilizador Superadministrador criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarSuperAdministrador(): Utilizador
    {
        return Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();
    }
}
