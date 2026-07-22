<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos registos de audição.
 *
 * Os registos de audição podem pertencer a diferentes entidades da
 * aplicação através de uma relação polimórfica.
 *
 * @return Migration Migração da tabela dos registos de audição.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
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
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'audicoes',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('utilizador_id')
                    ->constrained('utilizadores')
                    ->cascadeOnDelete();

                /*
                 * Relação polimórfica em português.
                 *
                 * A configuração explícita será posteriormente efetuada
                 * no modelo Audicao.
                 */
                $tabela->string(
                    'tipo_audivel',
                );

                $tabela->unsignedBigInteger(
                    'audivel_id',
                );

                $tabela->timestamps();

                $tabela->index(
                    [
                        'tipo_audivel',
                        'audivel_id',
                    ],
                    'audicoes_audivel_indice',
                );

                $tabela->unique(
                    [
                        'utilizador_id',
                        'audivel_id',
                        'tipo_audivel',
                    ],
                    'audicoes_utilizador_audivel_unico',
                );
            },
        );
    }

    /**
     * Elimina a tabela dos registos de audição.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('audicoes');
    }
};
