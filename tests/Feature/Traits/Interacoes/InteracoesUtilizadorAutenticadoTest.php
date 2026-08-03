<?php

declare(strict_types=1);

namespace Tests\Feature\Traits\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os atributos de interação dependentes do utilizador autenticado.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class InteracoesUtilizadorAutenticadoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a pontuação exige a relação explicitamente carregada.
     *
     * O acesso ao atributo sem a relação deve falhar sem executar qualquer
     * consulta oculta. Depois do carregamento explícito, o atributo é obtido
     * inteiramente da relação em memória.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function pontuacao_exige_relacao_carregada_sem_consulta_oculta(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->create();

        $metalThursday
            ->avaliacoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),

                'pontuacao' => 8.5,
            ]);

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] = $consulta->sql;
            },
        );

        try {
            $metalThursday->pontuacao_utilizador_autenticado;

            self::fail(
                'Era esperado que o atributo exigisse a relação carregada.',
            );
        } catch (LogicException) {
            self::assertSame(
                [],
                $consultas,
            );
        }

        $metalThursday->load(
            'avaliacaoUtilizadorAutenticado',
        );

        $consultas = [];

        self::assertSame(
            8.5,
            $metalThursday->pontuacao_utilizador_autenticado,
        );

        self::assertSame(
            [],
            $consultas,
        );
    }

    /**
     * Confirma que o estado de audição exige a relação explicitamente
     * carregada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function estado_audicao_exige_relacao_carregada_sem_consulta_oculta(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->create();

        $metalThursday
            ->audicoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),
            ]);

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] = $consulta->sql;
            },
        );

        try {
            $metalThursday->ouvido_pelo_utilizador_autenticado;

            self::fail(
                'Era esperado que o atributo exigisse a relação carregada.',
            );
        } catch (LogicException) {
            self::assertSame(
                [],
                $consultas,
            );
        }

        $metalThursday->load(
            'audicaoUtilizadorAutenticado',
        );

        $consultas = [];

        self::assertTrue(
            $metalThursday->ouvido_pelo_utilizador_autenticado,
        );

        self::assertSame(
            [],
            $consultas,
        );
    }

    /**
     * Confirma que um visitante recebe os valores neutros sem relações nem
     * consultas adicionais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function visitante_recebe_valores_neutros_sem_consultas(): void
    {
        $metalThursday = MetalThursday::factory()
            ->create();

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] = $consulta->sql;
            },
        );

        self::assertSame(
            0.0,
            $metalThursday->pontuacao_utilizador_autenticado,
        );

        self::assertFalse(
            $metalThursday->ouvido_pelo_utilizador_autenticado,
        );

        self::assertSame(
            [],
            $consultas,
        );
    }
}
