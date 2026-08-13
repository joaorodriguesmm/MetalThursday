<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos comentários.
 *
 * Os comentários podem pertencer a diferentes entidades da aplicação
 * através de uma relação polimórfica e podem responder a outros comentários.
 *
 * A aplicação é responsável por garantir que um comentário não responde a
 * si próprio, que o comentário pai pertence à mesma entidade comentável e
 * que não são criados ciclos na árvore de respostas.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos comentários.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'comentarios',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId(
                        'utilizador_id',
                    )
                    ->nullable();

                $tabela->text(
                    'conteudo',
                );

                $tabela
                    ->string(
                        'tipo_comentavel',
                        32,
                    )
                    ->charset('ascii')
                    ->collation('ascii_bin');

                $tabela->unsignedBigInteger(
                    'comentavel_id',
                );

                $tabela
                    ->foreignId(
                        'comentario_pai_id',
                    )
                    ->nullable();

                $tabela->timestamps();

                $tabela->softDeletes();

                $tabela->index(
                    'utilizador_id',
                    'comentarios_utilizador_indice',
                );

                $tabela->index(
                    [
                        'tipo_comentavel',
                        'comentavel_id',
                    ],
                    'comentarios_comentavel_indice',
                );

                $tabela->index(
                    [
                        'comentario_pai_id',
                        'created_at',
                    ],
                    'comentarios_pai_data_indice',
                );

                $tabela
                    ->foreign(
                        'utilizador_id',
                    )
                    ->references(
                        'id',
                    )
                    ->on(
                        'utilizadores',
                    )
                    ->nullOnDelete();

                $tabela
                    ->foreign(
                        'comentario_pai_id',
                    )
                    ->references(
                        'id',
                    )
                    ->on(
                        'comentarios',
                    )
                    ->nullOnDelete();
            },
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `comentarios`
                ADD CONSTRAINT `comentarios_conteudo_valido`
                CHECK (
                    CHAR_LENGTH(`conteudo`) BETWEEN 1 AND 2000
                    AND `conteudo` REGEXP '[^[:space:]]'
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `comentarios`
                ADD CONSTRAINT `comentarios_tipo_comentavel_valido`
                CHECK (
                    `tipo_comentavel` IN (
                        'metal_thursday',
                        'seccao_metal_thursday'
                    )
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `comentarios`
                ADD CONSTRAINT `comentarios_comentavel_id_valido`
                CHECK (`comentavel_id` >= 1)
            SQL,
        );
    }

    /**
     * Elimina a tabela dos comentários.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'comentarios',
        );
    }
};
