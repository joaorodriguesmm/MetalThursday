<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a estrutura persistente do perfil enriquecido dos artistas e das
 * ligações polimórficas.
 *
 * @since 2.0.0
 */
final class PerfilArtistaLigacoesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma as novas colunas do perfil do artista e a tabela genérica de
     * ligações.
     *
     * @since 2.0.0
     */
    #[Test]
    public function possui_estrutura_do_perfil_e_das_ligacoes(): void
    {
        self::assertTrue(
            Schema::hasColumns(
                'artistas',
                [
                    'ano_inicio_atividade',
                    'ano_fim_atividade',
                    'estado_atividade',
                    'biografia',
                    'imagem',
                    'musicbrainz_id',
                    'discogs_id',
                ],
            ),
        );

        self::assertTrue(
            Schema::hasTable(
                'ligacoes',
            ),
        );

        self::assertTrue(
            Schema::hasColumns(
                'ligacoes',
                [
                    'id',
                    'tipo_ligavel',
                    'ligavel_id',
                    'titulo',
                    'url',
                    'ordem',
                    'created_at',
                    'updated_at',
                ],
            ),
        );
    }

    /**
     * Confirma que a base de dados rejeita um ano de fim anterior ao ano de
     * início.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_periodo_de_atividade_invertido(): void
    {
        self::assertTrue(
            Schema::hasColumns(
                'artistas',
                [
                    'ano_inicio_atividade',
                    'ano_fim_atividade',
                ],
            ),
        );

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'artistas',
        )->insert([
            'nome' => 'Artista inválido',

            'ano_inicio_atividade' => 2000,

            'ano_fim_atividade' => 1999,
        ]);
    }

    /**
     * Confirma que apenas os estados de atividade definidos pelo domínio podem
     * ser persistidos.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_estado_de_atividade_desconhecido(): void
    {
        self::assertTrue(
            Schema::hasColumn(
                'artistas',
                'estado_atividade',
            ),
        );

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'artistas',
        )->insert([
            'nome' => 'Artista inválido',

            'estado_atividade' => 'desconhecido',
        ]);
    }

    /**
     * Confirma que um perfil MusicBrainz só pode identificar um artista local.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_musicbrainz_id_repetido(): void
    {
        $mbid =
            '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

        DB::table(
            'artistas',
        )->insert([
            'nome' => 'Primeiro artista',

            'musicbrainz_id' => $mbid,
        ]);

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'artistas',
        )->insert([
            'nome' => 'Segundo artista',

            'musicbrainz_id' => $mbid,
        ]);
    }

    /**
     * Confirma que um perfil do Discogs só pode estar associado a um artista
     * persistido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_discogs_id_repetido(): void
    {
        self::assertTrue(
            Schema::hasColumn(
                'artistas',
                'discogs_id',
            ),
        );

        DB::table(
            'artistas',
        )->insert([
            'nome' => 'Primeiro artista',

            'discogs_id' => 12345,
        ]);

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'artistas',
        )->insert([
            'nome' => 'Segundo artista',

            'discogs_id' => 12345,
        ]);
    }

    /**
     * Confirma que a entidade polimórfica de uma ligação tem de pertencer ao
     * conjunto explicitamente suportado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_tipo_ligavel_desconhecido(): void
    {
        self::assertTrue(
            Schema::hasColumns(
                'ligacoes',
                [
                    'tipo_ligavel',
                    'ligavel_id',
                    'titulo',
                    'url',
                    'ordem',
                ],
            ),
        );

        $this->expectException(
            QueryException::class,
        );

        DB::table(
            'ligacoes',
        )->insert([
            'tipo_ligavel' => 'entidade_desconhecida',

            'ligavel_id' => 1,

            'titulo' => 'Exemplo',

            'url' => 'https://example.com',

            'ordem' => 1,
        ]);
    }
}
