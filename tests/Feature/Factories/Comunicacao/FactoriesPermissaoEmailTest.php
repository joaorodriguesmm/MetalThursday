<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\Comunicacao;

use App\Models\Comunicacao\PermissaoEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os estados personalizados da factory das permissões de e-mail.
 *
 * @since 2.0.0
 */
final class FactoriesPermissaoEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que os estados personalizados são aplicados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_permissao_com_estados_personalizados(): void
    {
        $permissao = PermissaoEmail::factory()
            ->comIdentificador(
                '  AVISOS_SEMANAIS  ',
            )
            ->comDados(
                '  Avisos   semanais  ',
                '  Recebe   os avisos da semana.  ',
            )
            ->naOrdem(
                7,
            )
            ->create();

        self::assertSame(
            'avisos_semanais',
            $permissao->identificador,
        );

        self::assertSame(
            'Avisos semanais',
            $permissao->nome,
        );

        self::assertSame(
            'Recebe os avisos da semana.',
            $permissao->descricao,
        );

        self::assertSame(
            7,
            $permissao->ordem,
        );
    }

    /**
     * Confirma que a factory utiliza o formato de identificador do modelo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_identificador_com_espacos_interiores(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        PermissaoEmail::factory()
            ->comIdentificador(
                'avisos semanais',
            );
    }

    /**
     * Confirma que a factory rejeita caracteres de controlo nos dados
     * apresentados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_dados_com_caracteres_de_controlo(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        PermissaoEmail::factory()
            ->comDados(
                "Avisos\nsemanais",
                'Descrição válida.',
            );
    }

    /**
     * Confirma que a factory rejeita uma ordem fora dos limites do modelo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_ordem_invalida(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        PermissaoEmail::factory()
            ->naOrdem(
                0,
            );
    }
}
