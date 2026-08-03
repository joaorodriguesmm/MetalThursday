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
 *
 * @version 2.1.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das permissões de correio eletrónico.
     *
     * @since 2.0.0
     *
     * @version 2.1.0
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
                    ->unique();

                $tabela
                    ->string(
                        'nome',
                        100,
                    )
                    ->unique();

                $tabela->text(
                    'descricao',
                );

                $tabela
                    ->unsignedTinyInteger(
                        'ordem',
                    )
                    ->unique();

                $tabela->timestamps();
            },
        );

        DB::statement(
            <<<'SQL'
                ALTER TABLE `permissoes_email`
                ADD CONSTRAINT `permissoes_email_ordem_positiva_verificacao`
                CHECK (`ordem` >= 1)
                SQL,
        );
    }

    /**
     * Elimina a tabela das permissões de correio eletrónico.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'permissoes_email',
        );
    }
};
