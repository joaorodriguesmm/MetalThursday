<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos lançamentos musicais.
 *
 * O título não constitui a identidade do lançamento e pode ser repetido. O
 * tipo, o ano original e a identificação Discogs são opcionais quando essa
 * informação não é conhecida ou não se aplica.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos lançamentos no estado funcional completo.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'lancamentos',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->string(
                    'titulo',
                    255,
                );

                $tabela
                    ->enum(
                        'tipo',
                        [
                            'album_estudio',
                            'ep',
                            'single',
                            'album_ao_vivo',
                            'compilacao',
                            'demo',
                            'split',
                            'banda_sonora',
                            'outro',
                        ],
                    )
                    ->nullable();

                $tabela
                    ->unsignedSmallInteger(
                        'ano_original',
                    )
                    ->nullable();

                $tabela
                    ->unsignedBigInteger(
                        'discogs_release_id',
                    )
                    ->nullable()
                    ->unique(
                        'lancamentos_discogs_release_id_unico',
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
                        'titulo',
                        'deleted_at',
                    ],
                    'lancamentos_titulo_estado_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela dos lançamentos.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'lancamentos',
        );
    }
};
