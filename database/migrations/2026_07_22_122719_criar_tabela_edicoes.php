<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das edições do MetalThursday.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das edições.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'edicoes',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string('nome')
                    ->index();

                $tabela
                    ->date('data_inicio')
                    ->index();

                $tabela
                    ->date('data_fim')
                    ->nullable()
                    ->index();

                $tabela
                    ->string('ligacao_compilacao', 2048)
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
            },
        );
    }

    /**
     * Elimina a tabela das edições.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('edicoes');
    }
};
