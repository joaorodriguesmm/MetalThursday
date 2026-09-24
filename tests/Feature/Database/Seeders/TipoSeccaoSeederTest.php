<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use Database\Seeders\TipoSeccaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a materialização do catálogo dos tipos de secção.
 *
 * @since 2.0.0
 */
final class TipoSeccaoSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o seeder materializa integralmente o catálogo final.
     *
     * @since 2.0.0
     */
    #[Test]
    public function materializa_catalogo_dos_tipos_de_seccao(): void
    {
        app(
            TipoSeccaoSeeder::class,
        )->run();

        $persistidos = DB::table(
            'tipos_seccao',
        )
            ->orderBy(
                'ordem',
            )
            ->get([
                'identificador',
                'nome',
                'descricao',
                'exige_detalhes',
                'ordem',
            ])
            ->map(
                static fn (object $tipo): array => [
                    'identificador' => (string) $tipo->identificador,
                    'nome' => (string) $tipo->nome,
                    'descricao' => (string) $tipo->descricao,
                    'exige_detalhes' => (bool) $tipo->exige_detalhes,
                    'ordem' => (int) $tipo->ordem,
                ],
            )
            ->all();

        self::assertSame(
            [
                [
                    'identificador' => 'texto',
                    'nome' => 'Texto',
                    'descricao' => 'Secção destinada à apresentação de conteúdo textual.',
                    'exige_detalhes' => false,
                    'ordem' => 1,
                ],
                [
                    'identificador' => 'lancamento',
                    'nome' => 'Lançamento',
                    'descricao' => 'Secção destinada à apresentação de um lançamento musical.',
                    'exige_detalhes' => true,
                    'ordem' => 2,
                ],
                [
                    'identificador' => 'musica',
                    'nome' => 'Música',
                    'descricao' => 'Secção destinada à apresentação de uma música individual.',
                    'exige_detalhes' => true,
                    'ordem' => 3,
                ],
            ],
            $persistidos,
        );
    }

    /**
     * Confirma que execuções repetidas atualizam os metadados sem duplicar
     * tipos de secção existentes.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualiza_catalogo_sem_duplicar_tipos(): void
    {
        $seeder = app(
            TipoSeccaoSeeder::class,
        );

        $seeder->run();

        $identificadorPersistido = DB::table(
            'tipos_seccao',
        )
            ->where(
                'identificador',
                'lancamento',
            )
            ->value(
                'id',
            );

        self::assertNotNull(
            $identificadorPersistido,
        );

        DB::table(
            'tipos_seccao',
        )
            ->where(
                'identificador',
                'lancamento',
            )
            ->update([
                'nome' => 'Nome temporário',
                'descricao' => 'Descrição temporária.',
                'exige_detalhes' => false,
            ]);

        $seeder->run();

        self::assertSame(
            3,
            DB::table(
                'tipos_seccao',
            )->count(),
        );

        $lancamento = DB::table(
            'tipos_seccao',
        )
            ->where(
                'identificador',
                'lancamento',
            )
            ->first([
                'id',
                'nome',
                'descricao',
                'exige_detalhes',
                'ordem',
            ]);

        self::assertNotNull(
            $lancamento,
        );

        self::assertSame(
            (int) $identificadorPersistido,
            (int) $lancamento->id,
        );

        self::assertSame(
            'Lançamento',
            $lancamento->nome,
        );

        self::assertSame(
            'Secção destinada à apresentação de um lançamento musical.',
            $lancamento->descricao,
        );

        self::assertSame(
            1,
            (int) $lancamento->exige_detalhes,
        );

        self::assertSame(
            2,
            (int) $lancamento->ordem,
        );
    }
}
