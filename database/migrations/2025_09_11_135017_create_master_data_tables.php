<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável pela criação das tabelas iniciais de dados
 * mestres da aplicação.
 *
 * Esta migração cria países, géneros, tipos de secção, edições e permissões
 * de correio eletrónico.
 *
 * Os nomes físicos das tabelas e colunas permanecem temporariamente em
 * inglês para garantir compatibilidade com a estrutura atual da base de
 * dados.
 *
 * @return Migration - Migração das tabelas de dados mestres.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria as tabelas iniciais de dados mestres.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        /*
         * Cria a tabela dos países.
         *
         * A designação e o código ISO de duas letras devem ser únicos.
         */
        Schema::create(
            'countries',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string('name')
                    ->unique();

                $tabela
                    ->string('code', 2)
                    ->unique();
            },
        );

        /*
         * Cria a tabela dos géneros musicais.
         *
         * A hierarquia entre géneros é criada posteriormente através de uma
         * tabela intermédia.
         */
        Schema::create(
            'genres',
            function (Blueprint $tabela): void {
                $tabela->id();
                $tabela->string('name');

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
         * Cria a tabela dos tipos de secção das MetalThursdays.
         */
        Schema::create(
            'mt_section_types',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string('name')
                    ->unique();

                $tabela->string('description');

                $tabela
                    ->boolean('has_details')
                    ->default(false);
            },
        );

        /*
         * Cria a tabela das edições das MetalThursdays.
         */
        Schema::create(
            'mt_editions',
            function (Blueprint $tabela): void {
                $tabela->id();
                $tabela->string('name');
                $tabela->date('start_date');

                $tabela
                    ->date('end_date')
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

        /*
         * Cria a tabela das permissões de correio eletrónico.
         */
        Schema::create(
            'email_permissions',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string('name')
                    ->unique();

                $tabela
                    ->string('slug')
                    ->unique();

                $tabela
                    ->text('description')
                    ->nullable();
            },
        );
    }

    /**
     * Elimina as tabelas iniciais de dados mestres.
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
        Schema::dropIfExists('email_permissions');
        Schema::dropIfExists('mt_editions');
        Schema::dropIfExists('mt_section_types');
        Schema::dropIfExists('genres');
        Schema::dropIfExists('countries');
    }
};
