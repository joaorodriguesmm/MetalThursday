<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos géneros musicais.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos géneros musicais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'generos',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string('nome')
                    ->unique();

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
     * Elimina a tabela dos géneros musicais.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('generos');
    }
};
