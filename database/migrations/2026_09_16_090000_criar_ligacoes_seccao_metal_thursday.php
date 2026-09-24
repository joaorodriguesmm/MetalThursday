<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a estrutura de múltiplas ligações das secções MetalThursday.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela de ligações das secções.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'ligacoes_seccao_metal_thursday',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId(
                        'seccao_metal_thursday_id',
                    )
                    ->constrained(
                        table: 'seccoes_metal_thursday',
                    )
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $tabela
                    ->string(
                        'plataforma',
                        16,
                    )
                    ->charset('ascii')
                    ->collation('ascii_bin');

                $tabela
                    ->string(
                        'etiqueta',
                        255,
                    )
                    ->nullable();

                $tabela->string(
                    'url',
                    2048,
                );

                $tabela
                    ->boolean(
                        'incorporar',
                    )
                    ->default(false);

                $tabela->unsignedSmallInteger(
                    'ordem',
                );

                $tabela->timestamps();

                $tabela->unique(
                    [
                        'seccao_metal_thursday_id',
                        'ordem',
                    ],
                    'ligacoes_seccao_ordem_unica',
                );
            },
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `ligacoes_seccao_metal_thursday`
                ADD CONSTRAINT `ligacoes_seccao_metal_thursday_plataforma_valida`
                CHECK (`plataforma` IN ('spotify', 'apple_music', 'youtube', 'outro'))
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `ligacoes_seccao_metal_thursday`
                ADD CONSTRAINT `ligacoes_seccao_metal_thursday_ordem_valida`
                CHECK (`ordem` BETWEEN 1 AND 65535)
            SQL,
        );
    }

    /**
     * Elimina a estrutura de ligações das secções.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'ligacoes_seccao_metal_thursday',
        );
    }
};
