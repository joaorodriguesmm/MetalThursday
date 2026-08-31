<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das secções das MetalThursdays.
 *
 * Cada secção pertence a uma MetalThursday e a um tipo de secção. Pode ainda
 * conter um artista, informação editorial e uma ligação externa incorporável.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das secções das MetalThursdays.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'seccoes_metal_thursday',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId(
                        'metal_thursday_id',
                    )
                    ->constrained(
                        table: 'metal_thursdays',
                    )
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId(
                        'tipo_seccao_id',
                    )
                    ->constrained(
                        table: 'tipos_seccao',
                    )
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $tabela->unsignedSmallInteger(
                    'ordem',
                );

                $tabela
                    ->string(
                        'titulo',
                        255,
                    )
                    ->nullable();

                $tabela->mediumText(
                    'descricao',
                );

                $tabela
                    ->foreignId(
                        'artista_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'artistas',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela
                    ->string(
                        'ligacao',
                        2048,
                    )
                    ->nullable();

                $tabela
                    ->string(
                        'tipo_incorporacao',
                        24,
                    )
                    ->charset('ascii')
                    ->collation('ascii_bin')
                    ->nullable();

                $tabela
                    ->unsignedSmallInteger(
                        'ano',
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
                 * Mantém a ordem única entre secções ativas, sem impedir a
                 * preservação de posições em secções eliminadas logicamente.
                 */
                $tabela
                    ->unsignedSmallInteger(
                        'ordem_ativa',
                    )
                    ->nullable()
                    ->virtualAs(
                        'if(`deleted_at` is null, `ordem`, null)',
                    );

                $tabela->unique(
                    [
                        'metal_thursday_id',
                        'ordem_ativa',
                    ],
                    'seccoes_metal_thursday_ordem_ativa_unica',
                );

                $tabela->index(
                    [
                        'metal_thursday_id',
                        'deleted_at',
                        'ordem',
                    ],
                    'seccoes_metal_thursday_estado_ordem_indice',
                );

                $tabela->index(
                    [
                        'artista_id',
                        'deleted_at',
                        'metal_thursday_id',
                    ],
                    'seccoes_metal_thursday_artista_estado_metal_indice',
                );
            },
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `seccoes_metal_thursday`
                ADD CONSTRAINT `seccoes_metal_thursday_ordem_valida`
                CHECK (`ordem` BETWEEN 1 AND 65535)
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `seccoes_metal_thursday`
                ADD CONSTRAINT `seccoes_metal_thursday_ano_valido`
                CHECK (`ano` IS NULL OR `ano` BETWEEN 1900 AND 2155)
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `seccoes_metal_thursday`
                ADD CONSTRAINT `seccoes_metal_thursday_tipo_incorporacao_valido`
                CHECK (
                    `tipo_incorporacao` IS NULL
                    OR `tipo_incorporacao` IN (
                        'ligacao',
                        'video_youtube',
                        'lista_reproducao_youtube'
                    )
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `seccoes_metal_thursday`
                ADD CONSTRAINT `seccoes_metal_thursday_incorporacao_coerente`
                CHECK (
                    (`ligacao` IS NULL AND `tipo_incorporacao` IS NULL)
                    OR
                    (`ligacao` IS NOT NULL AND `tipo_incorporacao` IS NOT NULL)
                )
            SQL,
        );
    }

    /**
     * Elimina a tabela das secções das MetalThursdays.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'seccoes_metal_thursday',
        );
    }
};
