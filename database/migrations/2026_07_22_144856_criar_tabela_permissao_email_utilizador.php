<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela intermédia entre utilizadores e permissões de e-mail.
 *
 * Cada associação entre um utilizador e uma permissão pode existir apenas
 * uma vez.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela intermédia das permissões de e-mail.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'permissao_email_utilizador',
            static function (Blueprint $tabela): void {
                $tabela->foreignId(
                    'utilizador_id',
                );

                $tabela->foreignId(
                    'permissao_email_id',
                );

                $tabela->primary(
                    [
                        'utilizador_id',
                        'permissao_email_id',
                    ],
                    'permissao_email_utilizador_primaria',
                );

                /*
                 * A chave primária começa por utilizador_id. Este índice
                 * adicional otimiza a obtenção dos utilizadores associados a
                 * uma permissão.
                 */
                $tabela->index(
                    'permissao_email_id',
                    'permissao_email_utilizador_permissao_indice',
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

                $tabela
                    ->foreign(
                        'permissao_email_id',
                    )
                    ->references(
                        'id',
                    )
                    ->on(
                        'permissoes_email',
                    )
                    ->cascadeOnDelete();
            },
        );
    }

    /**
     * Elimina a tabela intermédia das permissões de e-mail.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'permissao_email_utilizador',
        );
    }
};
