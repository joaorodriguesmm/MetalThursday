<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o estado básico da aplicação.
 *
 * @since 2.0.0
 */
final class EstadoAplicacaoTest extends TestCase
{
    /**
     * Confirma que o endpoint de estado da aplicação está disponível.
     *
     * @since 2.0.0
     */
    #[Test]
    public function endpoint_de_estado_esta_disponivel(): void
    {
        $resposta = $this->get('/up');

        $resposta->assertOk();
    }
}
