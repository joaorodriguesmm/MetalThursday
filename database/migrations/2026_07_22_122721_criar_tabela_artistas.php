<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos artistas musicais.
 *
 * Cada artista pode possuir origem geográfica, período e estado de atividade,
 * biografia, endereço externo da imagem e associações opcionais ao MusicBrainz
 * e ao Discogs. Os géneros e as ligações são representados através de
 * relações.
 *
 * O nome não constitui a identidade do artista e pode, por isso, ser repetido
 * entre registos distintos.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos artistas.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'artistas',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->string(
                    'nome',
                    255,
                );

                $tabela
                    ->foreignId(
                        'origem_geografica_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'origens_geograficas',
                    )
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $tabela
                    ->unsignedSmallInteger(
                        'ano_inicio_atividade',
                    )
                    ->nullable();

                $tabela
                    ->unsignedSmallInteger(
                        'ano_fim_atividade',
                    )
                    ->nullable();

                $tabela
                    ->enum(
                        'estado_atividade',
                        [
                            'ativo',
                            'em_hiato',
                            'terminado',
                        ],
                    )
                    ->nullable();

                $tabela
                    ->text(
                        'biografia',
                    )
                    ->nullable();

                $tabela
                    ->string(
                        'imagem',
                        2048,
                    )
                    ->nullable();

                $tabela
                    ->uuid(
                        'musicbrainz_id',
                    )
                    ->nullable()
                    ->unique(
                        'artistas_musicbrainz_id_unico',
                    );

                $tabela
                    ->unsignedBigInteger(
                        'discogs_id',
                    )
                    ->nullable()
                    ->unique(
                        'artistas_discogs_id_unico',
                    );

                $tabela
                    ->foreignId(
                        'criado_por_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela
                    ->foreignId(
                        'atualizado_por_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela->timestamps();
                $tabela->softDeletes();

                $tabela->index(
                    [
                        'nome',
                        'deleted_at',
                    ],
                    'artistas_nome_estado_indice',
                );

                $tabela->index(
                    [
                        'origem_geografica_id',
                        'deleted_at',
                        'nome',
                    ],
                    'artistas_origem_estado_nome_indice',
                );
            },
        );

        DB::statement(
            <<<'SQL'
                ALTER TABLE artistas
                ADD CONSTRAINT artistas_periodo_atividade_valido
                CHECK (
                    ano_inicio_atividade IS NULL
                    OR ano_fim_atividade IS NULL
                    OR ano_fim_atividade >= ano_inicio_atividade
                )
                SQL,
        );
    }

    /**
     * Elimina a tabela dos artistas.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'artistas',
        );
    }
};
