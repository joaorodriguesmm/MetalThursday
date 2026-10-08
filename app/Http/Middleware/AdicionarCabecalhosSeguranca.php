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
 * A política de segurança de conteúdos é tratada separadamente, por exigir
 * uma configuração ajustada aos recursos e incorporações utilizados pela
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
        Vite::useCspNonce();

        $response = $next(
            $request,
        );

        foreach (
            self::CABECALHOS as $nome => $valor
        ) {
            $response->headers->set(
                $nome,
                $valor,
            );
        }

        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age='.self::DURACAO_HSTS,
            );
        }

        return $response;
    }
}
