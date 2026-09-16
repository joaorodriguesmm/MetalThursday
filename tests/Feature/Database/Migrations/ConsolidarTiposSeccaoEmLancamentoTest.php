<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Models\MetalThursday\MetalThursday;
use Database\Seeders\TipoSeccaoSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a consolidação dos tipos de secção legados num lançamento.
 *
 * @since 2.0.0
 */
final class ConsolidarTiposSeccaoEmLancamentoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a reversão não falha quando a migração não teve dados para
     * consolidar numa instalação ainda não povoada pelos seeders.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reversao_sem_tipos_relacionados_nao_tem_efeitos(): void
    {
        $migracao = require database_path(
            'migrations/2026_09_14_090000_consolidar_tipos_seccao_em_lancamento.php',
        );

        self::assertInstanceOf(
            Migration::class,
            $migracao,
        );

        self::assertFalse(
            DB::table(
                'tipos_seccao',
            )
                ->whereIn(
                    'identificador',
                    [
                        'lancamento',
                        'album',
                        'ep',
                    ],
                )
                ->exists(),
        );

        $migracao->down();

        self::assertFalse(
            DB::table(
                'tipos_seccao',
            )
                ->whereIn(
                    'identificador',
                    [
                        'lancamento',
                        'album',
                        'ep',
                    ],
                )
                ->exists(),
        );
    }

    /**
     * Confirma que álbum e EP são consolidados sem perder secções existentes.
     *
     * Valida também que o seeder pode ser executado depois da migração sem
     * colisões nas restrições únicas dos tipos de secção.
     *
     * @since 2.0.0
     */
    #[Test]
    public function consolida_album_e_ep_preservando_seccoes_existentes(): void
    {
        $agora = now();

        $identificadorTexto = DB::table(
            'tipos_seccao',
        )->insertGetId([
            'identificador' => 'texto',
            'nome' => 'Texto',
            'descricao' => 'Secção textual.',
            'exige_detalhes' => false,
            'ordem' => 1,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        $identificadorAlbum = DB::table(
            'tipos_seccao',
        )->insertGetId([
            'identificador' => 'album',
            'nome' => 'LP',
            'descricao' => 'Secção de álbum.',
            'exige_detalhes' => true,
            'ordem' => 2,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        $identificadorEp = DB::table(
            'tipos_seccao',
        )->insertGetId([
            'identificador' => 'ep',
            'nome' => 'EP',
            'descricao' => 'Secção de EP.',
            'exige_detalhes' => true,
            'ordem' => 3,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        $identificadorMusica = DB::table(
            'tipos_seccao',
        )->insertGetId([
            'identificador' => 'musica',
            'nome' => 'Música',
            'descricao' => 'Secção de música.',
            'exige_detalhes' => true,
            'ordem' => 4,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        $metalThursday = MetalThursday::factory()
            ->create();

        $identificadorSeccaoAlbum = DB::table(
            'seccoes_metal_thursday',
        )->insertGetId([
            'metal_thursday_id' => $metalThursday->getKey(),
            'tipo_seccao_id' => $identificadorAlbum,
            'ordem' => 1,
            'titulo' => 'Álbum histórico',
            'descricao' => 'Descrição do álbum histórico.',
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        $identificadorSeccaoEp = DB::table(
            'seccoes_metal_thursday',
        )->insertGetId([
            'metal_thursday_id' => $metalThursday->getKey(),
            'tipo_seccao_id' => $identificadorEp,
            'ordem' => 2,
            'titulo' => 'EP histórico',
            'descricao' => 'Descrição do EP histórico.',
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        $migracao = require database_path(
            'migrations/2026_09_14_090000_consolidar_tipos_seccao_em_lancamento.php',
        );

        self::assertInstanceOf(
            Migration::class,
            $migracao,
        );

        $migracao->up();

        $tipoLancamento = DB::table(
            'tipos_seccao',
        )
            ->where(
                'identificador',
                'lancamento',
            )
            ->first();

        self::assertNotNull(
            $tipoLancamento,
        );
        self::assertSame(
            $identificadorAlbum,
            (int) $tipoLancamento->id,
        );
        self::assertSame(
            'Lançamento',
            $tipoLancamento->nome,
        );
        self::assertSame(
            1,
            (int) $tipoLancamento->exige_detalhes,
        );
        self::assertSame(
            2,
            (int) $tipoLancamento->ordem,
        );

        self::assertFalse(
            DB::table(
                'tipos_seccao',
            )
                ->whereIn(
                    'identificador',
                    [
                        'album',
                        'ep',
                    ],
                )
                ->exists(),
        );

        self::assertSame(
            3,
            (int) DB::table(
                'tipos_seccao',
            )
                ->where(
                    'id',
                    $identificadorMusica,
                )
                ->value(
                    'ordem',
                ),
        );

        self::assertSame(
            1,
            (int) DB::table(
                'tipos_seccao',
            )
                ->where(
                    'id',
                    $identificadorTexto,
                )
                ->value(
                    'ordem',
                ),
        );

        $seccaoAlbum = DB::table(
            'seccoes_metal_thursday',
        )
            ->where(
                'id',
                $identificadorSeccaoAlbum,
            )
            ->first();

        $seccaoEp = DB::table(
            'seccoes_metal_thursday',
        )
            ->where(
                'id',
                $identificadorSeccaoEp,
            )
            ->first();

        self::assertNotNull(
            $seccaoAlbum,
        );
        self::assertNotNull(
            $seccaoEp,
        );
        self::assertSame(
            $identificadorAlbum,
            (int) $seccaoAlbum->tipo_seccao_id,
        );
        self::assertSame(
            $identificadorAlbum,
            (int) $seccaoEp->tipo_seccao_id,
        );
        self::assertSame(
            'Álbum histórico',
            $seccaoAlbum->titulo,
        );
        self::assertSame(
            'Descrição do álbum histórico.',
            $seccaoAlbum->descricao,
        );
        self::assertSame(
            'EP histórico',
            $seccaoEp->titulo,
        );
        self::assertSame(
            'Descrição do EP histórico.',
            $seccaoEp->descricao,
        );

        $this->seed(
            TipoSeccaoSeeder::class,
        );

        self::assertSame(
            3,
            DB::table(
                'tipos_seccao',
            )->count(),
        );
        self::assertSame(
            1,
            DB::table(
                'tipos_seccao',
            )
                ->where(
                    'identificador',
                    'lancamento',
                )
                ->count(),
        );
        self::assertSame(
            3,
            (int) DB::table(
                'tipos_seccao',
            )
                ->where(
                    'identificador',
                    'musica',
                )
                ->value(
                    'ordem',
                ),
        );
    }
}
