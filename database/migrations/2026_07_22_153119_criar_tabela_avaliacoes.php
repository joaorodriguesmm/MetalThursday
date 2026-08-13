<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das avaliações atribuídas pelos utilizadores.
 *
 * As avaliações podem pertencer a diferentes entidades da aplicação através
 * de uma relação polimórfica.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das avaliações.
     *
     * Cada utilizador pode atribuir apenas uma avaliação a cada entidade
     * avaliável.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'avaliacoes',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->foreignId(
                    'utilizador_id',
                );

                $tabela
                    ->decimal(
                        'pontuacao',
                        3,
                        1,
                    )
                    ->unsigned();

                $tabela
                    ->string(
                        'tipo_avaliavel',
                        32,
                    )
                    ->charset('ascii')
                    ->collation('ascii_bin');

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
                        'tipo_avaliavel',
                        'avaliavel_id',
                    ],
                    'avaliacoes_utilizador_avaliavel_unico',
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
                    ->cascadeOnDelete();
            },
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `avaliacoes`
                ADD CONSTRAINT `avaliacoes_pontuacao_valida`
                CHECK (
                    `pontuacao` BETWEEN 0.5 AND 10.0
                    AND MOD(`pontuacao` * 10, 5) = 0
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `avaliacoes`
                ADD CONSTRAINT `avaliacoes_tipo_avaliavel_valido`
                CHECK (
                    `tipo_avaliavel` IN (
                        'metal_thursday',
                        'seccao_metal_thursday'
                    )
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `avaliacoes`
                ADD CONSTRAINT `avaliacoes_avaliavel_id_valido`
                CHECK (`avaliavel_id` >= 1)
            SQL,
        );
    }

    /**
     * Elimina a tabela das avaliações.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'avaliacoes',
        );
    }
};
