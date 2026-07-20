<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável pela criação das tabelas intermédias das
 * relações de muitos para muitos.
 *
 * Esta migração cria a hierarquia entre géneros, a associação entre bandas e
 * géneros e as permissões de correio eletrónico atribuídas aos utilizadores.
 *
 * Os nomes físicos das tabelas e colunas permanecem temporariamente em
 * inglês para garantir compatibilidade com a estrutura atual da base de
 * dados.
 *
 * @return Migration - Migração das tabelas intermédias.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria as tabelas intermédias das relações de muitos para muitos.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        /*
         * Cria a tabela intermédia da hierarquia dos géneros.
         *
         * A chave primária composta impede a repetição da mesma relação
         * entre um género e o respetivo género pai.
         */
        Schema::create(
            'genre_parent_genre',
            function (Blueprint $tabela): void {
                $tabela
                    ->foreignId('genre_id')
                    ->constrained('genres')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('parent_genre_id')
                    ->constrained('genres')
                    ->cascadeOnDelete();

                $tabela->primary([
                    'genre_id',
                    'parent_genre_id',
                ]);
            },
        );

        /*
         * Cria a tabela intermédia entre bandas e géneros.
         *
         * A chave primária composta impede que o mesmo género seja associado
         * mais de uma vez à mesma banda.
         */
        Schema::create(
            'band_genre',
            function (Blueprint $tabela): void {
                $tabela
                    ->foreignId('band_id')
                    ->constrained('bands')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('genre_id')
                    ->constrained('genres')
                    ->cascadeOnDelete();

                $tabela->primary([
                    'band_id',
                    'genre_id',
                ]);
            },
        );

        /*
         * Cria a tabela intermédia entre utilizadores e permissões de correio
         * eletrónico.
         *
         * A chave primária composta impede que a mesma permissão seja
         * atribuída mais de uma vez ao mesmo utilizador.
         */
        Schema::create(
            'email_permission_user',
            function (Blueprint $tabela): void {
                $tabela
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('email_permission_id')
                    ->constrained('email_permissions')
                    ->cascadeOnDelete();

                $tabela->primary([
                    'user_id',
                    'email_permission_id',
                ]);
            },
        );
    }

    /**
     * Elimina as tabelas intermédias das relações de muitos para muitos.
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
        Schema::dropIfExists('email_permission_user');
        Schema::dropIfExists('band_genre');
        Schema::dropIfExists('genre_parent_genre');
    }
};
