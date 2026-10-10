<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a aplicação estrutural da proteção CSRF.
 *
 * @since 2.0.0
 */
final class ProtecaoCsrfTest extends TestCase
{
    /**
     * Métodos HTTP que podem alterar estado.
     *
     * @var list<string>
     */
    private const METODOS_MUTAVEIS = [
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ];

    /**
     * Confirma que todas as rotas mutáveis passam pelo grupo web.
     *
     * O grupo web contém a proteção CSRF fornecida pelo Laravel.
     *
     * @since 2.0.0
     */
    #[Test]
    public function todas_as_rotas_mutaveis_usam_o_grupo_web(): void
    {
        foreach (Route::getRoutes()->getRoutes() as $rota) {
            if (
                array_intersect(
                    $rota->methods(),
                    self::METODOS_MUTAVEIS,
                ) === []
            ) {
                continue;
            }

            self::assertContains(
                'web',
                $rota->gatherMiddleware(),
                sprintf(
                    'A rota mutável [%s] [%s] não utiliza o grupo web.',
                    implode(
                        '|',
                        $rota->methods(),
                    ),
                    $rota->uri(),
                ),
            );
        }
    }

    /**
     * Confirma que não existem caminhos excluídos da proteção CSRF.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_exclui_caminhos_da_protecao_csrf(): void
    {
        $middleware =
            app(
                PreventRequestForgery::class,
            );

        self::assertSame(
            [],
            $middleware->getExcludedPaths(),
        );
    }

    /**
     * Confirma que o disco técnico local não expõe rotas HTTP.
     *
     * Estas rotas do framework não utilizam o grupo web porque são protegidas
     * por ligações assinadas. Como não são necessárias à aplicação, permanecem
     * completamente desativadas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_expoe_rotas_http_do_disco_local(): void
    {
        self::assertSame(
            'privado',
            config(
                'filesystems.default',
            ),
        );

        self::assertFalse(
            config(
                'filesystems.disks.local.serve',
            ),
        );

        self::assertFalse(
            Route::has(
                'storage.local',
            ),
        );

        self::assertFalse(
            Route::has(
                'storage.local.upload',
            ),
        );
    }
}
