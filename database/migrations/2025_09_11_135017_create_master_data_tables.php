<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração consolidada para criar as tabelas de dados mestres iniciais.
 *
 * @return Migration - Migração consolidada.
 *
 * @since 1.0
 * @version 1.0
 */
return new class extends Migration
{
    /**
     * Executa a migração.
     *
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function up(): void
    {
        // Cria a tabela countries.
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 2)->unique();
        });

        // Cria a tabela genres.
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        // Cria a tabela mt_section_types.
        Schema::create('mt_section_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description');
            $table->boolean('has_details')->default(false);
        });

        // Cria a tabela mt_editions.
        Schema::create('mt_editions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        // Cria a tabela email_permissions.
        Schema::create('email_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverte a migração.
     *
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function down(): void
    {
        // Elimina as tabelas por ordem inversa à da criação.
        Schema::dropIfExists('email_permissions');
        Schema::dropIfExists('mt_editions');
        Schema::dropIfExists('mt_section_types');
        Schema::dropIfExists('genres');
        Schema::dropIfExists('countries');
    }
};
