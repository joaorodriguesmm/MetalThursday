<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
     * URI da rota que renderiza o carregador de página.
     *
     * @since 2.0.0
     */
    private const URI_TESTE_CARREGADOR =
        '__testes/cabecalhos-seguranca/carregador';

    /**
     * Caminho HTTP da rota que renderiza o carregador de página.
     *
     * @since 2.0.0
     */
    private const CAMINHO_TESTE_CARREGADOR =
        '/__testes/cabecalhos-seguranca/carregador';

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

        Route::get(
            self::URI_TESTE_CARREGADOR,
            static fn (): Response => response(
                view(
                    'components.carregamento-pagina',
                ),
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
     * Confirma que cada pedido recebe um nonce CSP próprio.
     *
     * @since 2.0.0
     */
    #[Test]
    public function gera_um_nonce_csp_novo_por_pedido(): void
    {
        $this
            ->get(
                self::CAMINHO_TESTE,
            )
            ->assertOk();

        $primeiroNonce =
            Vite::cspNonce();

        self::assertIsString(
            $primeiroNonce,
        );

        self::assertNotSame(
            '',
            $primeiroNonce,
        );

        $this
            ->get(
                self::CAMINHO_TESTE,
            )
            ->assertOk();

        $segundoNonce =
            Vite::cspNonce();

        self::assertIsString(
            $segundoNonce,
        );

        self::assertNotSame(
            '',
            $segundoNonce,
        );

        self::assertNotSame(
            $primeiroNonce,
            $segundoNonce,
        );
    }

    /**
     * Confirma que os blocos inline autorizados utilizam o nonce do pedido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function aplica_o_nonce_csp_ao_carregador_de_pagina(): void
    {
        $resposta =
            $this
                ->get(
                    self::CAMINHO_TESTE_CARREGADOR,
                )
                ->assertOk();

        $nonce =
            Vite::cspNonce();

        self::assertIsString(
            $nonce,
        );

        self::assertNotSame(
            '',
            $nonce,
        );

        $conteudo =
            $resposta->getContent();

        self::assertSame(
            1,
            substr_count(
                $conteudo,
                '<style nonce="'.$nonce.'">',
            ),
        );

        self::assertSame(
            1,
            substr_count(
                $conteudo,
                '<script nonce="'.$nonce.'">',
            ),
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
