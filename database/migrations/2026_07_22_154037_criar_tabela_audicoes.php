<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos registos de audição.
 *
 * Os registos de audição podem pertencer a diferentes entidades da aplicação
 * através de uma relação polimórfica.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos registos de audição.
     *
     * Cada utilizador pode marcar apenas uma vez a mesma entidade como
     * ouvida.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'audicoes',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->foreignId(
                    'utilizador_id',
                );

                $tabela
                    ->string(
                        'tipo_audivel',
                        32,
                    )
                    ->charset('ascii')
                    ->collation('ascii_bin');

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
                        'tipo_audivel',
                        'audivel_id',
                    ],
                    'audicoes_utilizador_audivel_unico',
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
            ALTER TABLE `audicoes`
                ADD CONSTRAINT `audicoes_tipo_audivel_valido`
                CHECK (
                    `tipo_audivel` IN (
                        'metal_thursday',
                        'seccao_metal_thursday'
                    )
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `audicoes`
                ADD CONSTRAINT `audicoes_audivel_id_valido`
                CHECK (`audivel_id` >= 1)
            SQL,
        );
    }

    /**
     * Elimina a tabela dos registos de audição.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'audicoes',
        );
    }
};
