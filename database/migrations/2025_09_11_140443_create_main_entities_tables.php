<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável pela criação das tabelas das entidades
 * principais do MetalThursday.
 *
 * Esta migração cria as bandas, as MetalThursdays e as respetivas secções.
 *
 * Os nomes físicos das tabelas e colunas permanecem temporariamente em
 * inglês para garantir compatibilidade com a estrutura atual da base de
 * dados.
 *
 * @return Migration - Migração das entidades principais.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria as tabelas das entidades principais do MetalThursday.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        /*
         * Cria a tabela das bandas.
         */
        Schema::create(
            'bands',
            function (Blueprint $tabela): void {
                $tabela->id();
                $tabela->string('name');

                $tabela
                    ->foreignId('country_id')
                    ->constrained('countries')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $tabela->timestamps();
                $tabela->softDeletes();
            },
        );

        /*
         * Cria a tabela das MetalThursdays.
         */
        Schema::create(
            'metal_thursdays',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string('name')
                    ->nullable();

                $tabela->date('date');

                $tabela
                    ->foreignId('edition_id')
                    ->constrained('mt_editions')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('author_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('next_nominee_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('created_by')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $tabela->timestamps();
                $tabela->softDeletes();
            },
        );

        /*
         * Cria a tabela das secções das MetalThursdays.
         */
        Schema::create(
            'mt_sections',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('metal_thursday_id')
                    ->constrained('metal_thursdays')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('section_type_id')
                    ->constrained('mt_section_types')
                    ->cascadeOnDelete();

                $tabela
                    ->string('title')
                    ->nullable();

                $tabela
                    ->text('description')
                    ->nullable();

                $tabela
                    ->foreignId('band_id')
                    ->nullable()
                    ->constrained('bands')
                    ->nullOnDelete();

                $tabela
                    ->string('link')
                    ->nullable();

                $tabela
                    ->year('year')
                    ->nullable();

                $tabela
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $tabela->timestamps();
                $tabela->softDeletes();
            },
        );
    }

    /**
     * Elimina as tabelas das entidades principais do MetalThursday.
     *
     * As tabelas são eliminadas pela ordem inversa à respetiva criação.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('mt_sections');
        Schema::dropIfExists('metal_thursdays');
        Schema::dropIfExists('bands');
    }
};
