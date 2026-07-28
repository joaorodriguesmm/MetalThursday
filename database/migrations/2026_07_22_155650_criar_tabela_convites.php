<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos convites de registo.
 *
 * Os convites existem antes dos respetivos utilizadores. Apenas o hash do
 * código é persistido, impedindo a recuperação do código original através
 * da base de dados.
 *
 * O estado de um convite é determinado pelas datas de utilização, revogação
 * e expiração, sem recorrer a uma coluna de estado redundante.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos convites.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'convites',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->string(
                    'nome_convidado',
                    255,
                );

                $tabela
                    ->string(
                        'email_destino',
                        255,
                    )
                    ->nullable();

                /*
                 * Hash hexadecimal SHA-256 do código original.
                 *
                 * O conjunto de caracteres ASCII e a comparação binária
                 * evitam qualquer normalização linguística deste valor.
                 */
                $tabela
                    ->char(
                        'codigo_hash',
                        64,
                    )
                    ->charset(
                        'ascii',
                    )
                    ->collation(
                        'ascii_bin',
                    );

                /*
                 * Utilizador responsável pela criação do convite.
                 *
                 * O convite é preservado caso o criador seja posteriormente
                 * eliminado.
                 */
                $tabela
                    ->foreignId(
                        'criado_por_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                /*
                 * Utilizador criado através do convite.
                 *
                 * Permanece nulo enquanto o convite não tiver sido utilizado.
                 * Caso o utilizador seja eliminado fisicamente, a data de
                 * utilização continua a preservar o estado consumido.
                 */
                $tabela
                    ->foreignId(
                        'utilizado_por_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela
                    ->timestamp(
                        'expira_em',
                    )
                    ->nullable();

                $tabela
                    ->timestamp(
                        'utilizado_em',
                    )
                    ->nullable();

                $tabela
                    ->timestamp(
                        'revogado_em',
                    )
                    ->nullable();

                $tabela->timestamps();

                $tabela->unique(
                    'codigo_hash',
                    'convites_codigo_hash_unico',
                );

                /*
                 * Um utilizador pode ter sido criado através de apenas um
                 * convite.
                 *
                 * O MySQL permite vários valores nulos nesta restrição.
                 */
                $tabela->unique(
                    'utilizado_por_id',
                    'convites_utilizado_por_unico',
                );

                $tabela->index(
                    'email_destino',
                    'convites_email_destino_indice',
                );

                /*
                 * Suporta a localização de convites pendentes, não revogados
                 * e ainda não expirados.
                 */
                $tabela->index(
                    [
                        'utilizado_por_id',
                        'utilizado_em',
                        'revogado_em',
                        'expira_em',
                    ],
                    'convites_disponibilidade_indice',
                );

                $tabela->index(
                    'criado_por_id',
                    'convites_criado_por_indice',
                );
            },
        );

        DB::statement(
            <<<'SQL'
                ALTER TABLE `convites`
                ADD CONSTRAINT `convites_nome_convidado_valido_verificacao`
                CHECK (
                    CHAR_LENGTH(TRIM(`nome_convidado`))
                    BETWEEN 1 AND 255
                )
                SQL,
        );

        DB::statement(
            <<<'SQL'
                ALTER TABLE `convites`
                ADD CONSTRAINT `convites_email_destino_valido_verificacao`
                CHECK (
                    `email_destino` IS NULL
                    OR CHAR_LENGTH(TRIM(`email_destino`))
                        BETWEEN 1 AND 255
                )
                SQL,
        );

        DB::statement(
            <<<'SQL'
                ALTER TABLE `convites`
                ADD CONSTRAINT `convites_codigo_hash_valido_verificacao`
                CHECK (CHAR_LENGTH(TRIM(`codigo_hash`)) = 64)
                SQL,
        );

        /*
         * Um convite associado a um utilizador tem obrigatoriamente uma data
         * de utilização.
         *
         * O inverso não é exigido porque a eliminação física do utilizador
         * coloca utilizado_por_id a nulo, preservando utilizado_em.
         */
        DB::statement(
            <<<'SQL'
                ALTER TABLE `convites`
                ADD CONSTRAINT `convites_utilizacao_coerente_verificacao`
                CHECK (
                    `utilizado_por_id` IS NULL
                    OR `utilizado_em` IS NOT NULL
                )
                SQL,
        );

        /*
         * Um convite utilizado já produziu efeitos e não pode também estar
         * marcado como revogado.
         */
        DB::statement(
            <<<'SQL'
                ALTER TABLE `convites`
                ADD CONSTRAINT `convites_estado_exclusivo_verificacao`
                CHECK (
                    `utilizado_em` IS NULL
                    OR `revogado_em` IS NULL
                )
                SQL,
        );
    }

    /**
     * Elimina a tabela dos convites.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'convites',
        );
    }
};
