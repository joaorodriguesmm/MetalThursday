<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
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
 *
 * @version 1.0.0
 */
final class FactoriesUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que os estados personalizados são aplicados conjuntamente.
     *
     * A fotografia é normalizada pelo contrato definitivo do modelo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     * Confirma que a factory rejeita uma ligação externa como fotografia.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     *
     * @version 1.0.0
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
     *
     * @version 1.0.0
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
