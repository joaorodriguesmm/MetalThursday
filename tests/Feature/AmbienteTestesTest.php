<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Confirma que os testes utilizam uma base de dados isolada.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class AmbienteTestesTest extends TestCase
{
    /**
     * Confirma o nome da base de dados utilizada durante os testes.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utiliza_a_base_de_dados_exclusiva_dos_testes(): void
    {
        self::assertSame(
            'metalthursday_testes',
            config('database.connections.mysql.database'),
            'Os testes não estão configurados para a base de dados isolada.',
        );
    }
}
