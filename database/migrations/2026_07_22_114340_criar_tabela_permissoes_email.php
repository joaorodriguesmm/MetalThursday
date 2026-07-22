<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das permissões de e-mail.
 *
 * @return Migration Migração da tabela das permissões de e-mail.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das permissões de e-mail.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'permissoes_email',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string('nome')
                    ->unique();

                $tabela
                    ->string('identificador')
                    ->unique();

                $tabela
                    ->text('descricao')
                    ->nullable();

                $tabela->timestamps();
            },
        );
    }

    /**
     * Elimina a tabela das permissões de e-mail.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('permissoes_email');
    }
};
