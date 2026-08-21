<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a persistência da disponibilidade para nomeações.
 *
 * @since 2.0.0
 */
final class DisponibilidadeNomeacaoUtilizadoresTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma a existência da coluna de disponibilidade.
     *
     * @since 2.0.0
     */
    #[Test]
    public function possui_disponibilidade_para_nomeacao(): void
    {
        self::assertTrue(
            Schema::hasColumn(
                'utilizadores',
                'disponivel_para_nomeacao',
            ),
        );
    }

    /**
     * Confirma que novos utilizadores estão disponíveis por predefinição.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utiliza_disponibilidade_ativa_por_predefinicao(): void
    {
        $identificador = DB::table(
            'utilizadores',
        )->insertGetId([
            'nome' => 'Utilizador Teste',

            'email' => 'disponibilidade@exemplo.pt',

            'password' => 'hash-de-teste',
        ]);

        $disponibilidade = DB::table(
            'utilizadores',
        )
            ->where(
                'id',
                $identificador,
            )
            ->value(
                'disponivel_para_nomeacao',
            );

        self::assertSame(
            1,
            (int) $disponibilidade,
        );
    }
}
