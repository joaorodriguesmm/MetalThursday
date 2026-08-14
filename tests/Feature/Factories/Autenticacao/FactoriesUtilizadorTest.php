<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonImmutable;
use Database\Factories\Autenticacao\UtilizadorFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os estados personalizados da factory dos utilizadores.
 *
 * @since 2.0.0
 */
final class FactoriesUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma o estado ativo predefinido dos utilizadores.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_utilizador_com_acesso_ativo_por_predefinicao(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        self::assertNull(
            $utilizador->suspenso_em,
        );

        self::assertNull(
            $utilizador->motivo_suspensao,
        );

        self::assertNull(
            $utilizador->suspenso_por_id,
        );

        self::assertTrue(
            $utilizador->temAcessoAtivo(),
        );

        self::assertFalse(
            $utilizador->estaSuspenso(),
        );
    }

    /**
     * Confirma que os estados personalizados são aplicados conjuntamente.
     *
     * A fotografia é normalizada pelo contrato definitivo do modelo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_utilizador_com_estados_personalizados(): void
    {
        $utilizador = Utilizador::factory()
            ->naoVerificado()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->comFotografia(
                '  utilizadores\fotografia.jpg  ',
            )
            ->create();

        self::assertNull(
            $utilizador->email_verified_at,
        );

        self::assertSame(
            PapelUtilizador::Administrador,
            $utilizador->papel,
        );

        self::assertSame(
            'utilizadores/fotografia.jpg',
            $utilizador->fotografia,
        );

        self::assertTrue(
            Hash::check(
                UtilizadorFactory::PALAVRA_PASSE_PREDEFINIDA,
                $utilizador->password,
            ),
        );
    }

    /**
     * Confirma a criação de um utilizador suspenso.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_utilizador_com_acesso_suspenso(): void
    {
        $responsavel = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $utilizador = Utilizador::factory()
            ->suspensoPor(
                $responsavel,
                "  Suspensão \n criada para testes. ",
            )
            ->create()
            ->fresh();

        self::assertNotNull(
            $utilizador,
        );

        self::assertInstanceOf(
            CarbonImmutable::class,
            $utilizador->suspenso_em,
        );

        self::assertSame(
            'Suspensão criada para testes.',
            $utilizador->motivo_suspensao,
        );

        self::assertSame(
            (int) $responsavel->getKey(),
            $utilizador->suspenso_por_id,
        );

        self::assertTrue(
            $utilizador->estaSuspenso(),
        );

        self::assertFalse(
            $utilizador->temAcessoAtivo(),
        );

        self::assertTrue(
            $utilizador
                ->responsavelSuspensao
                ->is(
                    $responsavel,
                ),
        );
    }

    /**
     * Confirma que um responsável não persistido é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_responsavel_de_suspensao_nao_persistido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        Utilizador::factory()
            ->suspensoPor(
                new Utilizador,
            );
    }

    /**
     * Confirma que um motivo de suspensão vazio é rejeitado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_motivo_de_suspensao_vazio(): void
    {
        $responsavel = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $this->expectException(
            InvalidArgumentException::class,
        );

        Utilizador::factory()
            ->suspensoPor(
                $responsavel,
                " \t\n ",
            );
    }

    /**
     * Confirma que a factory rejeita uma ligação externa como fotografia.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_fotografia_com_ligacao_externa(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        Utilizador::factory()
            ->comFotografia(
                'https://example.com/fotografia.jpg',
            );
    }

    /**
     * Confirma que a factory rejeita travessia de diretórios.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_fotografia_com_travessia_de_diretorios(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        Utilizador::factory()
            ->comFotografia(
                'utilizadores/../fotografia.jpg',
            );
    }

    /**
     * Confirma que um caminho vazio não representa uma fotografia.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_fotografia_vazia(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        Utilizador::factory()
            ->comFotografia(
                '   ',
            );
    }
}
