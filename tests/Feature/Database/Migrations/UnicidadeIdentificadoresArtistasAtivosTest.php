<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Confirma a unicidade dos identificadores externos apenas entre artistas ativos.
 *
 * @since 2.0.0
 */
final class UnicidadeIdentificadoresArtistasAtivosTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function permite_reutilizar_identificadores_sem_eliminar_registos_antigos(): void
    {
        $mbid = 'ad7390d4-0606-4f3b-9621-74ee821a5117';
        $discogs = 981245;

        DB::table('artistas')->insert([
            'nome' => 'Registo anterior',
            'musicbrainz_id' => $mbid,
            'discogs_id' => $discogs,
            'deleted_at' => now(),
        ]);

        DB::table('artistas')->insert([
            'nome' => 'Registo novo',
            'musicbrainz_id' => $mbid,
            'discogs_id' => $discogs,
            'deleted_at' => null,
        ]);

        self::assertSame(
            2,
            DB::table('artistas')->where('musicbrainz_id', $mbid)->count(),
        );
        self::assertSame(
            1,
            DB::table('artistas')
                ->where('musicbrainz_id', $mbid)
                ->whereNull('deleted_at')
                ->count(),
        );
    }

    #[Test]
    public function permite_varios_registos_eliminados_com_os_mesmos_identificadores(): void
    {
        $mbid = 'f9960e13-990a-4f4c-b946-67ca41abb2d3';

        foreach (['Primeiro', 'Segundo'] as $nome) {
            DB::table('artistas')->insert([
                'nome' => $nome,
                'musicbrainz_id' => $mbid,
                'discogs_id' => 673921,
                'deleted_at' => now(),
            ]);
        }

        self::assertSame(
            2,
            DB::table('artistas')->where('musicbrainz_id', $mbid)->count(),
        );
    }

    #[Test]
    public function impede_restaurar_artista_quando_ja_existe_outro_ativo(): void
    {
        $mbid = '1c4d7223-c421-4923-98e2-fb01b98e3511';

        $antigo = DB::table('artistas')->insertGetId([
            'nome' => 'Registo eliminado',
            'musicbrainz_id' => $mbid,
            'deleted_at' => now(),
        ]);

        DB::table('artistas')->insert([
            'nome' => 'Registo ativo',
            'musicbrainz_id' => $mbid,
        ]);

        $this->expectException(QueryException::class);

        DB::table('artistas')
            ->where('id', $antigo)
            ->update(['deleted_at' => null]);
    }
}
