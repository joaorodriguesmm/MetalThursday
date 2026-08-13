<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria o histórico imutável das alterações dos papéis dos utilizadores.
 *
 * O papel atual permanece na tabela `utilizadores`. Cada alteração conserva
 * o papel anterior, o novo papel, o superadministrador responsável e o
 * momento em que a operação foi concluída.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela e as respetivas restrições de integridade.
     *
     * Os valores permanecem autónomos em relação à enumeração PHP para que a
     * execução futura da migration não dependa de alterações posteriores ao
     * código da aplicação.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'registos_papel_utilizadores',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->foreignId(
                    'utilizador_id',
                );

                $tabela
                    ->string(
                        'papel_anterior',
                        19,
                    )
                    ->charset('ascii')
                    ->collation('ascii_bin');

                $tabela
                    ->string(
                        'papel_novo',
                        19,
                    )
                    ->charset('ascii')
                    ->collation('ascii_bin');

                $tabela->foreignId(
                    'responsavel_id',
                );

                $tabela->timestamp(
                    'registado_em',
                );

                $tabela->index(
                    [
                        'utilizador_id',
                        'registado_em',
                        'id',
                    ],
                    'registos_papel_utilizador_data_indice',
                );

                $tabela->index(
                    [
                        'responsavel_id',
                        'registado_em',
                        'id',
                    ],
                    'registos_papel_responsavel_data_indice',
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
                    ->restrictOnDelete();

                $tabela
                    ->foreign(
                        'responsavel_id',
                    )
                    ->references(
                        'id',
                    )
                    ->on(
                        'utilizadores',
                    )
                    ->restrictOnDelete();
            },
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `registos_papel_utilizadores`
                ADD CONSTRAINT `registos_papel_papeis_validos_verificacao`
                CHECK (
                    BINARY `papel_anterior` IN (
                        BINARY 'utilizador',
                        BINARY 'administrador',
                        BINARY 'super_administrador'
                    )
                    AND
                    BINARY `papel_novo` IN (
                        BINARY 'utilizador',
                        BINARY 'administrador',
                        BINARY 'super_administrador'
                    )
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `registos_papel_utilizadores`
                ADD CONSTRAINT `registos_papel_papeis_distintos_verificacao`
                CHECK (
                    BINARY `papel_anterior`
                    <> BINARY `papel_novo`
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `registos_papel_utilizadores`
                ADD CONSTRAINT `registos_papel_responsavel_distinto_verificacao`
                CHECK (`responsavel_id` <> `utilizador_id`)
            SQL,
        );
    }

    /**
     * Remove o histórico das alterações dos papéis.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'registos_papel_utilizadores',
        );
    }
};
