<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a política CORS fechada por omissão.
 *
 * @since 2.0.0
 */
final class PoliticaCorsTest extends TestCase
{
    /**
     * Confirma que a configuração CORS permanece desativada por omissão.
     *
     * @since 2.0.0
     */
    #[Test]
    public function mantem_cors_fechado_por_omissao(): void
    {
        self::assertSame(
            [],
            config(
                'cors.paths',
            ),
        );

        self::assertSame(
            [],
            config(
                'cors.allowed_methods',
            ),
        );

        self::assertSame(
            [],
            config(
                'cors.allowed_origins',
            ),
        );

        self::assertSame(
            [],
            config(
                'cors.allowed_headers',
            ),
        );

        self::assertFalse(
            config(
                'cors.supports_credentials',
            ),
        );
    }

    /**
     * Confirma que pedidos externos não recebem autorização CORS.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_autoriza_origens_externas_em_caminhos_api(): void
    {
        $this
            ->withHeader(
                'Origin',
                'https://atacante.example',
            )
            ->get(
                '/api/auditoria-cors-inexistente',
            )
            ->assertNotFound()
            ->assertHeaderMissing(
                'Access-Control-Allow-Origin',
            );
    }

    /**
     * Confirma que preflights externos não são processados pelo CORS.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_aceita_preflight_externo_em_caminhos_api(): void
    {
        $this
            ->withHeaders([
                'Origin' => 'https://atacante.example',

                'Access-Control-Request-Method' => 'POST',

                'Access-Control-Request-Headers' => 'Content-Type,Authorization',
            ])
            ->call(
                'OPTIONS',
                '/api/auditoria-cors-inexistente',
            )
            ->assertNotFound()
            ->assertHeaderMissing(
                'Access-Control-Allow-Origin',
            )
            ->assertHeaderMissing(
                'Access-Control-Allow-Methods',
            )
            ->assertHeaderMissing(
                'Access-Control-Allow-Headers',
            );
    }
}
