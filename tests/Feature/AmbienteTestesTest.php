<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Confirma que os testes utilizam um ambiente e uma base de dados isolados.
 *
 * @since 2.0.0
 */
final class AmbienteTestesTest extends TestCase
{
    /**
     * Confirma o ambiente, a ligação e a base de dados utilizados nos testes.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utiliza_o_ambiente_e_a_base_de_dados_exclusivos_dos_testes(): void
    {
        self::assertSame(
            'testing',
            app()->environment(),
            'A aplicação não está a executar no ambiente de testes.',
        );

        self::assertSame(
            'mariadb',
            config('database.default'),
            'Os testes não estão a utilizar a ligação MariaDB configurada.',
        );

        self::assertSame(
            'metalthursday_testes',
            config('database.connections.mariadb.database'),
            'A ligação MariaDB não está configurada para a base de dados de testes.',
        );

        self::assertSame(
            'metalthursday_testes',
            DB::connection()->getDatabaseName(),
            'A ligação ativa não utiliza a base de dados isolada dos testes.',
        );
    }
}
