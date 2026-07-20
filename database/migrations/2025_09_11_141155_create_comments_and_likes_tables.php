<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável pela criação das tabelas de comentários e
 * gostos das MetalThursdays.
 *
 * Os comentários podem pertencer a diferentes tipos de entidades através de
 * uma relação polimórfica e podem responder a outros comentários.
 *
 * Os nomes físicos das tabelas e colunas permanecem temporariamente em
 * inglês para garantir compatibilidade com a estrutura atual da base de
 * dados.
 *
 * @return Migration - Migração das tabelas de comentários e gostos.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria as tabelas de comentários e gostos.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        /*
         * Cria a tabela dos comentários.
         *
         * A relação polimórfica permite associar comentários a diferentes
         * modelos da aplicação.
         *
         * A relação parent_id permite criar respostas hierárquicas.
         */
        Schema::create(
            'mt_comments',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $tabela->text('content');

                $tabela->morphs('commentable');

                $tabela
                    ->foreignId('parent_id')
                    ->nullable()
                    ->constrained('mt_comments')
                    ->cascadeOnDelete();

                $tabela->timestamps();
            },
        );

        /*
         * Cria a tabela dos gostos atribuídos aos comentários.
         *
         * A restrição única impede que o mesmo utilizador atribua mais de um
         * gosto ao mesmo comentário.
         */
        Schema::create(
            'mt_likes',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('comment_id')
                    ->constrained('mt_comments')
                    ->cascadeOnDelete();

                $tabela->timestamps();

                $tabela->unique(
                    [
                        'user_id',
                        'comment_id',
                    ],
                    'mt_likes_user_comment_unique',
                );
            },
        );
    }

    /**
     * Elimina as tabelas de comentários e gostos.
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
        Schema::dropIfExists('mt_likes');
        Schema::dropIfExists('mt_comments');
    }
};
