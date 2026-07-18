<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração consolidada para criar as tabelas de entidades principais.
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
        // Cria a tabela bands.
        Schema::create('bands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        // Cria a tabela metal_thursdays.
        Schema::create('metal_thursdays', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->date('date');
            $table->foreignId('edition_id')->constrained('mt_editions')->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('next_nominee_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // Cria a tabela mt_sections.
        Schema::create('mt_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metal_thursday_id')->constrained('metal_thursdays')->onDelete('cascade');
            $table->foreignId('section_type_id')->constrained('mt_section_types')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('band_id')->nullable()->constrained('bands')->onDelete('set null');
            $table->string('link')->nullable();
            $table->year('year')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
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
        Schema::dropIfExists('mt_sections');
        Schema::dropIfExists('metal_thursdays');
        Schema::dropIfExists('bands');
    }
};
