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
     * URI da rota que define um cabeçalho de segurança específico.
     *
     * @since 2.0.0
     */
    private const URI_TESTE_CABECALHO_ESPECIFICO =
        '__testes/cabecalhos-seguranca/especifico';

    /**
     * Caminho da rota que define um cabeçalho de segurança específico.
     *
     * @since 2.0.0
     */
    private const CAMINHO_TESTE_CABECALHO_ESPECIFICO =
        '/__testes/cabecalhos-seguranca/especifico';

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

        Route::get(
            self::URI_TESTE_CABECALHO_ESPECIFICO,
            static fn (): Response => response(
                'Resposta com política específica.',
            )->header(
                'Referrer-Policy',
                'no-referrer',
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
     * Confirma a política CSP aplicada fora do ambiente Vite/HMR.
     *
     * @since 2.0.0
     */
    #[Test]
    public function adiciona_politica_csp_estrita_com_nonce_do_pedido(): void
    {
        $resposta =
            $this
                ->get(
                    self::CAMINHO_TESTE,
                )
                ->assertOk();

        $nonce =
            Vite::cspNonce();

        self::assertIsString(
            $nonce,
        );

        $politicaEsperada =
            implode(
                '; ',
                [
                    "default-src 'self'",
                    "base-uri 'none'",
                    "object-src 'none'",
                    "frame-ancestors 'none'",
                    "form-action 'self'",
                    "script-src 'self' 'nonce-{$nonce}'",
                    "script-src-attr 'none'",
                    "style-src 'self' 'nonce-{$nonce}' 'unsafe-hashes' 'sha256-BFI/9FWiRf1IyGeMhmDVIVtEOFwUe/+oJqGygoJCVh0='",
                    "style-src-attr 'unsafe-hashes' 'sha256-BFI/9FWiRf1IyGeMhmDVIVtEOFwUe/+oJqGygoJCVh0='",
                    "img-src 'self' http: https:",
                    "font-src 'self'",
                    "connect-src 'self'",
                    'frame-src https://www.youtube-nocookie.com https://open.spotify.com https://embed.music.apple.com',
                    "worker-src 'none'",
                ],
            );

        $resposta->assertHeader(
            'Content-Security-Policy',
            $politicaEsperada,
        );

        self::assertStringNotContainsString(
            "'unsafe-inline'",
            $politicaEsperada,
        );

        self::assertStringNotContainsString(
            "'unsafe-eval'",
            $politicaEsperada,
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

        self::assertIsString(
            $conteudo,
        );

        $politica =
            $resposta->headers->get(
                'Content-Security-Policy',
            );

        self::assertIsString(
            $politica,
        );

        self::assertStringContainsString(
            "'nonce-{$nonce}'",
            $politica,
        );

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
     * Confirma as exceções CSP estritamente necessárias ao Vite/HMR local.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_a_origem_vite_quando_hmr_esta_ativo_em_local(): void
    {
        /*
         * A classe TestCase desativa o Vite por omissão. Este teste precisa
         * da implementação real para simular a deteção do servidor HMR.
         */
        $this->withVite();

        Vite::clearResolvedInstance();
        $ambienteOriginal =
            config(
                'app.env',
            );

        $ficheiroHotOriginal =
            Vite::hotFile();

        $ficheiroHot =
            tempnam(
                sys_get_temp_dir(),
                'metal-thursday-vite-',
            );

        self::assertIsString(
            $ficheiroHot,
        );

        self::assertNotFalse(
            file_put_contents(
                $ficheiroHot,
                'http://127.0.0.1:5173',
            ),
        );

        try {
            config()->set(
                'app.env',
                'local',
            );

            Vite::useHotFile(
                $ficheiroHot,
            );

            $resposta =
                $this
                    ->get(
                        self::CAMINHO_TESTE,
                    )
                    ->assertOk();

            $nonce =
                Vite::cspNonce();

            self::assertIsString(
                $nonce,
            );

            $politicaEsperada =
                implode(
                    '; ',
                    [
                        "default-src 'self'",
                        "base-uri 'none'",
                        "object-src 'none'",
                        "frame-ancestors 'none'",
                        "form-action 'self'",
                        "script-src 'self' 'nonce-{$nonce}' http://127.0.0.1:5173",
                        "script-src-attr 'none'",
                        "style-src 'self' 'nonce-{$nonce}' 'unsafe-hashes' 'sha256-BFI/9FWiRf1IyGeMhmDVIVtEOFwUe/+oJqGygoJCVh0=' http://127.0.0.1:5173",
                        "style-src-attr 'unsafe-hashes' 'sha256-BFI/9FWiRf1IyGeMhmDVIVtEOFwUe/+oJqGygoJCVh0='",
                        "img-src 'self' http: https:",
                        "font-src 'self' http://127.0.0.1:5173",
                        "connect-src 'self' http://127.0.0.1:5173 ws://127.0.0.1:5173",
                        'frame-src https://www.youtube-nocookie.com https://open.spotify.com https://embed.music.apple.com',
                        "worker-src 'self' blob:",
                    ],
                );

            $resposta->assertHeader(
                'Content-Security-Policy',
                $politicaEsperada,
            );

            self::assertStringNotContainsString(
                "'unsafe-inline'",
                $politicaEsperada,
            );

            self::assertStringNotContainsString(
                "'unsafe-eval'",
                $politicaEsperada,
            );
        } finally {
            config()->set(
                'app.env',
                $ambienteOriginal,
            );

            Vite::useHotFile(
                $ficheiroHotOriginal,
            );

            if (is_file($ficheiroHot)) {
                unlink(
                    $ficheiroHot,
                );
            }

            $this->withoutVite();
        }
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

    /**
     * Confirma que uma resposta pode definir uma política mais específica.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preserva_cabecalhos_de_seguranca_especificos_da_resposta(): void
    {
        $this
            ->get(
                self::CAMINHO_TESTE_CABECALHO_ESPECIFICO,
            )
            ->assertOk()
            ->assertHeader(
                'Referrer-Policy',
                'no-referrer',
            )
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff',
            )
            ->assertHeader(
                'X-Frame-Options',
                'DENY',
            )
            ->assertHeader(
                'Permissions-Policy',
                'geolocation=(), camera=(), microphone=()',
            );
    }
}
