<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável pela criação da tabela dos convites.
 *
 * Os convites são criados antes dos respetivos utilizadores. Quando um
 * convite é aceite, o utilizador criado é associado ao convite através da
 * coluna `utilizado_por`.
 *
 * Apenas o hash do código é guardado na base de dados, impedindo a recuperação
 * direta dos códigos de convite em caso de acesso indevido aos dados.
 *
 * A tabela é criada vazia. Os códigos existentes na coluna histórica
 * `users.invite_code` não são migrados.
 *
 * @return Migration - Migração da tabela dos convites.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Nome da restrição única aplicada ao hash do código.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const INDICE_CODIGO_HASH_UNICO =
        'convites_codigo_hash_unico';

    /**
     * Nome do índice utilizado na pesquisa pelo endereço de e-mail.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const INDICE_EMAIL_DESTINO =
        'convites_email_destino_indice';

    /**
     * Nome do índice utilizado na limpeza de convites expirados.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const INDICE_EXPIRACAO =
        'convites_expira_em_indice';

    /**
     * Cria a tabela dos convites.
     *
     * O estado de um convite é determinado através das colunas
     * `utilizado_em`, `revogado_em` e `expira_em`, evitando guardar uma coluna
     * de estado redundante que poderia ficar dessincronizada.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'convites',
            function (Blueprint $tabela): void {
                $tabela->id();

                /*
                 * Nome apresentado no formulário de aceitação e utilizado
                 * inicialmente na criação do novo utilizador.
                 */
                $tabela->string('nome_convidado');

                /*
                 * O e-mail é opcional para permitir que o convidado o indique
                 * durante o registo. Quando preenchido, poderá ser utilizado
                 * para limitar o convite a um endereço específico.
                 */
                $tabela
                    ->string('email_destino')
                    ->nullable();

                /*
                 * Hash hexadecimal SHA-256 do código original.
                 *
                 * O código em texto simples será apresentado apenas no
                 * momento da criação do convite e nunca será persistido.
                 */
                $tabela->char('codigo_hash', 64);

                /*
                 * Administrador responsável pela criação do convite.
                 *
                 * O convite é preservado caso esse utilizador seja eliminado.
                 */
                $tabela
                    ->foreignId('criado_por')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                /*
                 * Utilizador criado ou associado quando o convite é aceite.
                 *
                 * A coluna permanece nula enquanto o convite estiver
                 * disponível.
                 */
                $tabela
                    ->foreignId('utilizado_por')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $tabela->timestamp('expira_em')->nullable();
                $tabela->timestamp('utilizado_em')->nullable();
                $tabela->timestamp('revogado_em')->nullable();

                $tabela->timestamps();

                $tabela->unique(
                    'codigo_hash',
                    self::INDICE_CODIGO_HASH_UNICO,
                );

                $tabela->index(
                    'email_destino',
                    self::INDICE_EMAIL_DESTINO,
                );

                $tabela->index(
                    'expira_em',
                    self::INDICE_EXPIRACAO,
                );
            },
        );
    }

    /**
     * Elimina a tabela dos convites.
     *
     * Durante esta fase da transição, a reversão não afeta a coluna histórica
     * `users.invite_code`.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('convites');
    }
};
