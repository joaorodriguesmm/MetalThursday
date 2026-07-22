<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela intermédia entre utilizadores e permissões de e-mail.
 *
 * @return Migration Migração das permissões de e-mail dos utilizadores.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela intermédia das permissões de e-mail.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'permissao_email_utilizador',
            function (Blueprint $tabela): void {
                $tabela
                    ->foreignId('utilizador_id')
                    ->constrained('utilizadores')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('permissao_email_id')
                    ->constrained('permissoes_email')
                    ->cascadeOnDelete();

                $tabela->primary(
                    [
                        'utilizador_id',
                        'permissao_email_id',
                    ],
                    'permissao_email_utilizador_pk',
                );
            },
        );
    }

    /**
     * Elimina a tabela intermédia das permissões de e-mail.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'permissao_email_utilizador',
        );
    }
};
