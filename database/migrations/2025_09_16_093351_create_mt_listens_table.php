<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável pela criação da tabela dos registos de
 * audição das MetalThursdays.
 *
 * Os registos de audição utilizam uma relação polimórfica para poderem ser
 * associados a diferentes tipos de entidades da aplicação.
 *
 * Os nomes físicos da tabela e das colunas permanecem temporariamente em
 * inglês para garantir compatibilidade com a estrutura atual da base de
 * dados.
 *
 * @return Migration - Migração da tabela dos registos de audição.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos registos de audição.
     *
     * Cada utilizador pode marcar apenas uma vez a mesma entidade como
     * ouvida.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'mt_listens',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                /*
                 * Cria as colunas listenable_type e listenable_id, juntamente
                 * com o índice composto utilizado nas consultas da relação
                 * polimórfica.
                 */
                $tabela->morphs('listenable');

                $tabela->timestamps();

                $tabela->unique(
                    [
                        'user_id',
                        'listenable_id',
                        'listenable_type',
                    ],
                    'mt_listens_user_listenable_unique',
                );
            },
        );
    }

    /**
     * Elimina a tabela dos registos de audição.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('mt_listens');
    }
};
