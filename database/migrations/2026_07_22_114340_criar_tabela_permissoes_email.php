<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das permissões de comunicação por correio eletrónico.
 *
 * Cada permissão possui um identificador técnico estável, um nome
 * apresentado ao utilizador, uma descrição e uma ordem de apresentação.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das permissões de correio eletrónico.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'permissoes_email',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string(
                        'identificador',
                        64,
                    )
                    ->charset('ascii')
                    ->collation('ascii_bin');

                $tabela->string(
                    'nome',
                    100,
                );

                $tabela->mediumText(
                    'descricao',
                );

                $tabela->unsignedTinyInteger(
                    'ordem',
                );

                $tabela->timestamps();

                $tabela->unique(
                    'identificador',
                    'permissoes_email_identificador_unico',
                );

                $tabela->unique(
                    'nome',
                    'permissoes_email_nome_unico',
                );

                $tabela->unique(
                    'ordem',
                    'permissoes_email_ordem_unica',
                );
            },
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `permissoes_email`
                ADD CONSTRAINT `permissoes_email_identificador_formato_valido`
                CHECK (
                    BINARY `identificador` REGEXP '^[a-z0-9]+(_[a-z0-9]+)*$'
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `permissoes_email`
                ADD CONSTRAINT `permissoes_email_ordem_valida`
                CHECK (`ordem` BETWEEN 1 AND 255)
            SQL,
        );
    }

    /**
     * Elimina a tabela das permissões de correio eletrónico.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'permissoes_email',
        );
    }
};
