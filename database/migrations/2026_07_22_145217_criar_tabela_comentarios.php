<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos comentários.
 *
 * Os comentários podem pertencer a diferentes entidades da aplicação
 * através de uma relação polimórfica e podem responder a outros
 * comentários.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos comentários.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'comentarios',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('utilizador_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela->text('conteudo');

                /*
                 * Relação polimórfica em português.
                 *
                 * A configuração explícita será feita posteriormente
                 * no modelo Comentario.
                 */
                $tabela->string(
                    'tipo_comentavel',
                );

                $tabela->unsignedBigInteger(
                    'comentavel_id',
                );

                $tabela->index(
                    [
                        'tipo_comentavel',
                        'comentavel_id',
                    ],
                    'comentarios_comentavel_indice',
                );

                $tabela
                    ->foreignId('comentario_pai_id')
                    ->nullable()
                    ->constrained('comentarios')
                    ->nullOnDelete();

                $tabela->timestamps();
                $tabela->softDeletes();
            },
        );
    }

    /**
     * Elimina a tabela dos comentários.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('comentarios');
    }
};
