<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mantém identificadores externos únicos apenas entre artistas ativos.
 *
 * Uma coluna gerada vale 1 para artistas ativos e NULL para eliminados.
 * Os índices compostos UNIQUE impedem duplicações apenas entre ativos.
 * Nenhum identificador ou registo histórico é alterado.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /** Aplica os índices condicionais numa única alteração da tabela. */
    public function up(): void
    {
        DB::statement(
            <<<'SQL'
                ALTER TABLE `artistas`
                    ADD COLUMN `chave_unicidade_ativo` TINYINT UNSIGNED
                        GENERATED ALWAYS AS (
                            IF(`deleted_at` IS NULL, 1, NULL)
                        ) PERSISTENT,
                    ADD UNIQUE INDEX `artistas_musicbrainz_ativo_unico`
                        (`musicbrainz_id`, `chave_unicidade_ativo`),
                    ADD UNIQUE INDEX `artistas_discogs_ativo_unico`
                        (`discogs_id`, `chave_unicidade_ativo`),
                    DROP INDEX `artistas_musicbrainz_id_unico`,
                    DROP INDEX `artistas_discogs_id_unico`
                SQL,
        );
    }

    /**
     * Reverte apenas se ainda for possível repor a unicidade histórica.
     *
     * Um rollback depois da reutilização legítima de um identificador não
     * deve apagar dados nem deixar a base de dados sem proteção.
     */
    public function down(): void
    {
        foreach (['musicbrainz_id', 'discogs_id'] as $coluna) {
            $duplicado = DB::table('artistas')
                ->select($coluna)
                ->whereNotNull($coluna)
                ->groupBy($coluna)
                ->havingRaw('COUNT(*) > 1')
                ->first();

            if ($duplicado !== null) {
                throw new RuntimeException(
                    'Não é possível reverter a migração: existem identificadores '
                    .$coluna.' repetidos em artistas eliminados logicamente.',
                );
            }
        }

        DB::statement(
            <<<'SQL'
                ALTER TABLE `artistas`
                    ADD UNIQUE INDEX `artistas_musicbrainz_id_unico` (`musicbrainz_id`),
                    ADD UNIQUE INDEX `artistas_discogs_id_unico` (`discogs_id`),
                    DROP INDEX `artistas_musicbrainz_ativo_unico`,
                    DROP INDEX `artistas_discogs_ativo_unico`,
                    DROP COLUMN `chave_unicidade_ativo`
                SQL,
        );
    }
};
