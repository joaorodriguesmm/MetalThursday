<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das edições do MetalThursday.
 *
 * Cada edição delimita um período temporal, agrega MetalThursdays e pode
 * possuir uma ligação para a respetiva compilação.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das edições.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'edicoes',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->string(
                    'nome',
                    255,
                );

                $tabela->date(
                    'data_inicio',
                );

                $tabela
                    ->date(
                        'data_fim',
                    )
                    ->nullable();

                $tabela
                    ->string(
                        'ligacao_compilacao',
                        2048,
                    )
                    ->nullable();

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

                /*
                 * Permite reutilizar o nome de uma edição eliminada
                 * logicamente, mantendo a unicidade entre edições ativas.
                 */
                $tabela
                    ->string(
                        'nome_ativo',
                        255,
                    )
                    ->nullable()
                    ->virtualAs(
                        'if(`deleted_at` is null, `nome`, null)',
                    );

                $tabela->unique(
                    'nome_ativo',
                    'edicoes_nome_ativo_unico',
                );

                $tabela->index(
                    [
                        'data_inicio',
                        'data_fim',
                    ],
                    'edicoes_periodo_indice',
                );
            },
        );

        DB::statement(
            <<<'SQL'
                ALTER TABLE `edicoes`
                ADD CONSTRAINT `edicoes_datas_validas_verificacao`
                CHECK (`data_fim` IS NULL OR `data_fim` >= `data_inicio`)
                SQL,
        );
    }

    /**
     * Elimina a tabela das edições.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'edicoes',
        );
    }
};
