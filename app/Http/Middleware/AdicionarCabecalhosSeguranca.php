<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acrescenta cabeçalhos HTTP defensivos às respostas da aplicação.
 *
 * Inclui uma política de segurança de conteúdos estrita, baseada num nonce
 * único por pedido e limitada aos recursos efetivamente utilizados pela
 * aplicação.
 *
 * @since 2.0.0
 */
final class AdicionarCabecalhosSeguranca
{
    /**
     * Cabeçalhos aplicáveis a todas as respostas HTTP.
     *
     * @var array<string, string>
     *
     * @since 2.0.0
     */
    private const CABECALHOS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), camera=(), microphone=()',
    ];

    /**
     * Duração da política HSTS, em segundos.
     *
     * @since 2.0.0
     */
    private const DURACAO_HSTS =
        31_536_000;

    /**
     * Hash do único atributo style utilizado internamente pelo SweetAlert2.
     *
     * Corresponde exatamente a "display:none !important", utilizado apenas
     * pelo workaround de encerramento em Safari/iOS.
     *
     * @since 2.0.0
     */
    private const HASH_ESTILO_SWEETALERT =
        'sha256-BFI/9FWiRf1IyGeMhmDVIVtEOFwUe/+oJqGygoJCVh0=';

    /**
     * Origens autorizadas para leitores incorporados.
     *
     * @var list<string>
     *
     * @since 2.0.0
     */
    private const ORIGENS_INCORPORACOES = [
        'https://www.youtube-nocookie.com',
        'https://open.spotify.com',
        'https://embed.music.apple.com',
    ];

    /**
     * Processa o pedido e acrescenta os cabeçalhos de segurança à resposta.
     *
     * HSTS só é enviado quando o pedido é efetivamente HTTPS. Desta forma, o
     * ambiente local HTTP não anuncia uma política de transporte que não pode
     * cumprir.
     *
     * @param  Request  $request  Pedido HTTP atual.
     * @param  Closure(Request): Response  $next  Próximo middleware.
     * @return Response Resposta com os cabeçalhos defensivos.
     *
     * @since 2.0.0
     */
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        /*
         * O nonce é preparado antes da renderização da resposta para que o
         * Vite e os blocos inline explicitamente autorizados partilhem o
         * mesmo valor.
         */
        $nonce =
            Vite::useCspNonce();

        $response = $next(
            $request,
        );

        foreach (
            self::CABECALHOS as $nome => $valor
        ) {
            if (
                $response->headers->has(
                    $nome,
                )
            ) {
                continue;
            }

            $response->headers->set(
                $nome,
                $valor,
            );
        }

        $response->headers->set(
            'Content-Security-Policy',
            $this->criarPoliticaSegurancaConteudos(
                $nonce,
            ),
        );

        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age='.self::DURACAO_HSTS,
            );
        }

        return $response;
    }

    /**
     * Constrói a política de segurança de conteúdos do pedido atual.
     *
     * O ambiente local permite apenas a origem concreta do servidor Vite
     * quando o HMR está ativo. Em produção e testes não são acrescentadas
     * exceções de desenvolvimento.
     *
     * @param  string  $nonce  Nonce único do pedido.
     * @return string Política CSP serializada.
     *
     * @since 2.0.0
     */
    private function criarPoliticaSegurancaConteudos(
        string $nonce,
    ): string {
        $fontesScript = [
            "'self'",
            "'nonce-{$nonce}'",
        ];

        $fontesEstilo = [
            "'self'",
            "'nonce-{$nonce}'",
            "'unsafe-hashes'",
            "'".self::HASH_ESTILO_SWEETALERT."'",
        ];

        $fontesFontes = [
            "'self'",
        ];

        $fontesLigacoes = [
            "'self'",
        ];

        $fontesWorkers = [
            "'none'",
        ];

        $fontesVite =
            $this->obterFontesViteDesenvolvimento();

        if ($fontesVite !== null) {
            $fontesScript[] =
                $fontesVite['origem'];

            $fontesEstilo[] =
                $fontesVite['origem'];

            $fontesFontes[] =
                $fontesVite['origem'];

            $fontesLigacoes[] =
                $fontesVite['origem'];

            $fontesLigacoes[] =
                $fontesVite['websocket'];

            /*
             * O cliente HMR do Vite pode recorrer a um SharedWorker criado
             * através de blob quando tenta restabelecer a ligação.
             */
            $fontesWorkers = [
                "'self'",
                'blob:',
            ];
        }

        return implode(
            '; ',
            [
                "default-src 'self'",
                "base-uri 'none'",
                "object-src 'none'",
                "frame-ancestors 'none'",
                "form-action 'self'",

                'script-src '
                    .implode(
                        ' ',
                        $fontesScript,
                    ),

                "script-src-attr 'none'",

                'style-src '
                    .implode(
                        ' ',
                        $fontesEstilo,
                    ),

                "style-src-attr 'unsafe-hashes' '"
                    .self::HASH_ESTILO_SWEETALERT
                    ."'",

                "img-src 'self' http: https: blob:",

                'font-src '
                    .implode(
                        ' ',
                        $fontesFontes,
                    ),

                'connect-src '
                    .implode(
                        ' ',
                        $fontesLigacoes,
                    ),

                'frame-src '
                    .implode(
                        ' ',
                        self::ORIGENS_INCORPORACOES,
                    ),

                'worker-src '
                    .implode(
                        ' ',
                        $fontesWorkers,
                    ),
            ],
        );
    }

    /**
     * Obtém as origens necessárias ao Vite/HMR no ambiente local.
     *
     * @return array{
     *     origem: string,
     *     websocket: string
     * }|null Origens do servidor Vite ou nulo quando não são necessárias.
     *
     * @since 2.0.0
     */
    private function obterFontesViteDesenvolvimento(): ?array
    {
        if (config('app.env') !== 'local') {
            return null;
        }

        $enderecoServidor =
            Vite::devServerUrl();

        if ($enderecoServidor === null) {
            return null;
        }

        $componentes =
            parse_url(
                $enderecoServidor,
            );

        if (! is_array($componentes)) {
            return null;
        }

        $esquema =
            $componentes['scheme']
            ?? null;

        $anfitriao =
            $componentes['host']
            ?? null;

        $porta =
            $componentes['port']
            ?? null;

        if (
            ! is_string($esquema)
            || ! is_string($anfitriao)
            || $anfitriao === ''
            || (
                $porta !== null
                && ! is_int($porta)
            )
        ) {
            return null;
        }

        $esquema =
            mb_strtolower(
                $esquema,
            );

        if (
            ! in_array(
                $esquema,
                [
                    'http',
                    'https',
                ],
                true,
            )
        ) {
            return null;
        }

        $sufixoPorta =
            $porta !== null
            ? ':'.$porta
            : '';

        return [
            'origem' => $esquema
                .'://'
                .$anfitriao
                .$sufixoPorta,

            'websocket' => (
                $esquema === 'https'
                ? 'wss'
                : 'ws'
            )
                .'://'
                .$anfitriao
                .$sufixoPorta,
        ];
    }
}
