<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos tipos de secção.
 *
 * Os tipos de secção definem a natureza e o comportamento das secções
 * existentes numa MetalThursday.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos tipos de secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'tipos_seccao',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string('nome')
                    ->unique();

                $tabela->text('descricao');

                $tabela
                    ->boolean('tem_detalhes')
                    ->default(false);

                $tabela->timestamps();
            },
        );
    }

    /**
     * Elimina a tabela dos tipos de secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'tipos_seccao',
        );
    }
};
