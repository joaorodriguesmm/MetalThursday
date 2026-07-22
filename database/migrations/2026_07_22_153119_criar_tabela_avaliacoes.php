<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das avaliações atribuídas pelos utilizadores.
 *
 * As avaliações podem pertencer a diferentes entidades da aplicação
 * através de uma relação polimórfica.
 *
 * @return Migration Migração da tabela das avaliações.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das avaliações.
     *
     * Cada utilizador pode atribuir apenas uma avaliação a cada entidade
     * avaliável.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'avaliacoes',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('utilizador_id')
                    ->constrained('utilizadores')
                    ->cascadeOnDelete();

                $tabela->decimal(
                    'pontuacao',
                    3,
                    1,
                );

                /*
                 * Relação polimórfica em português.
                 *
                 * A configuração explícita será posteriormente efetuada
                 * no modelo Avaliacao.
                 */
                $tabela->string(
                    'tipo_avaliavel',
                );

                $tabela->unsignedBigInteger(
                    'avaliavel_id',
                );

                $tabela->timestamps();

                $tabela->index(
                    [
                        'tipo_avaliavel',
                        'avaliavel_id',
                    ],
                    'avaliacoes_avaliavel_indice',
                );

                $tabela->unique(
                    [
                        'utilizador_id',
                        'avaliavel_id',
                        'tipo_avaliavel',
                    ],
                    'avaliacoes_utilizador_avaliavel_unico',
                );
            },
        );
    }

    /**
     * Elimina a tabela das avaliações.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};
