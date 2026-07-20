<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável pela criação da tabela das classificações
 * atribuídas pelos utilizadores.
 *
 * As classificações utilizam uma relação polimórfica para poderem ser
 * associadas a diferentes tipos de entidades da aplicação.
 *
 * Os nomes físicos da tabela e das colunas permanecem temporariamente em
 * inglês para garantir compatibilidade com a estrutura atual da base de
 * dados.
 *
 * @return Migration - Migração da tabela das classificações.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das classificações.
     *
     * Cada utilizador pode atribuir apenas uma classificação a cada entidade
     * classificável.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'mt_ratings',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $tabela->decimal(
                    'rating',
                    3,
                    1,
                );

                /*
                 * Cria as colunas rateable_type e rateable_id, juntamente com
                 * o índice composto necessário para consultar as
                 * classificações de uma entidade.
                 */
                $tabela->morphs('rateable');

                $tabela->timestamps();

                $tabela->unique(
                    [
                        'user_id',
                        'rateable_id',
                        'rateable_type',
                    ],
                    'mt_ratings_user_rateable_unique',
                );
            },
        );
    }

    /**
     * Elimina a tabela das classificações.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('mt_ratings');
    }
};
