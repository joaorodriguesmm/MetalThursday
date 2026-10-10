<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa a ausência de informações internas nas respostas de erro.
 *
 * @since 2.0.0
 */
final class ExposicaoErrosTest extends TestCase
{
    /**
     * Confirma que uma exceção interna não revela detalhes quando
     * a depuração está desativada.
     *
     * A rota de auditoria existe apenas durante a execução do teste.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_expoe_detalhes_de_excecoes_com_debug_desativado(): void
    {
        config()->set(
            'app.debug',
            false,
        );

        $caminho =
            '/auditoria-b19-13-erro';

        $marcador =
            'MARCADOR_INTERNO_B19_13';

        Route::get(
            $caminho,
            static function () use ($marcador): never {
                throw new RuntimeException(
                    $marcador,
                );
            },
        );

        $this
            ->get(
                $caminho,
            )
            ->assertStatus(
                500,
            )
            ->assertDontSee(
                $marcador,
            )
            ->assertDontSee(
                'RuntimeException',
            )
            ->assertDontSee(
                'Stack trace',
            );

        $this
            ->getJson(
                $caminho,
            )
            ->assertStatus(
                500,
            )
            ->assertDontSee(
                $marcador,
            )
            ->assertDontSee(
                'RuntimeException',
            )
            ->assertDontSee(
                '"trace"',
            );
    }
}
