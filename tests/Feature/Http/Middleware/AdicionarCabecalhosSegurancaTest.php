<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os cabeçalhos HTTP defensivos aplicados globalmente.
 *
 * @since 2.0.0
 */
final class AdicionarCabecalhosSegurancaTest extends TestCase
{
    /**
     * URI da rota isolada utilizada pelos testes.
     *
     * @since 2.0.0
     */
    private const URI_TESTE =
        '__testes/cabecalhos-seguranca';

    /**
     * Caminho HTTP da rota isolada utilizada pelos testes.
     *
     * @since 2.0.0
     */
    private const CAMINHO_TESTE =
        '/__testes/cabecalhos-seguranca';

    /**
     * Regista uma rota sem middleware de rota para confirmar que a proteção
     * pertence à pilha global.
     *
     * O nome permanece em inglês por corresponder ao ciclo de vida do
     * PHPUnit.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        Route::get(
            self::URI_TESTE,
            static fn (): Response => response(
                'Resposta protegida.',
            ),
        );
    }

    /**
     * Confirma os cabeçalhos independentes do protocolo em HTTP.
     *
     * @since 2.0.0
     */
    #[Test]
    public function adiciona_os_cabecalhos_defensivos_em_http(): void
    {
        $this
            ->get(
                self::CAMINHO_TESTE,
            )
            ->assertOk()
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff',
            )
            ->assertHeader(
                'X-Frame-Options',
                'DENY',
            )
            ->assertHeader(
                'Referrer-Policy',
                'strict-origin-when-cross-origin',
            )
            ->assertHeader(
                'Permissions-Policy',
                'geolocation=(), camera=(), microphone=()',
            )
            ->assertHeaderMissing(
                'Strict-Transport-Security',
            );
    }

    /**
     * Confirma que HSTS só é anunciado através de HTTPS.
     *
     * @since 2.0.0
     */
    #[Test]
    public function adiciona_hsts_em_https(): void
    {
        URL::forceScheme(
            'https',
        );

        $this
            ->get(
                self::CAMINHO_TESTE,
            )
            ->assertOk()
            ->assertHeader(
                'Strict-Transport-Security',
                'max-age=31536000',
            );
    }
}
