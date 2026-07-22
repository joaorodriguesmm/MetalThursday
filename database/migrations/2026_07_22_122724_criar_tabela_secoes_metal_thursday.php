<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das secções das MetalThursdays.
 *
 * @return Migration Migração da tabela das secções.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das secções das MetalThursdays.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'secoes_metal_thursday',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('metal_thursday_id')
                    ->constrained('metal_thursdays')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('tipo_secao_id')
                    ->constrained('tipos_secao')
                    ->restrictOnDelete();

                $tabela
                    ->string('titulo')
                    ->nullable();

                $tabela
                    ->text('descricao')
                    ->nullable();

                $tabela
                    ->foreignId('banda_id')
                    ->nullable()
                    ->constrained('bandas')
                    ->nullOnDelete();

                $tabela
                    ->string('ligacao', 2048)
                    ->nullable();

                $tabela
                    ->string('tipo_incorporacao', 32)
                    ->nullable();

                $tabela
                    ->year('ano')
                    ->nullable();

                $tabela
                    ->foreignId('criado_por_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela
                    ->foreignId('atualizado_por_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela->timestamps();
                $tabela->softDeletes();

                $tabela->index(
                    [
                        'metal_thursday_id',
                        'tipo_secao_id',
                    ],
                    'secoes_metal_thursday_tipo_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela das secções das MetalThursdays.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'secoes_metal_thursday',
        );
    }
};
