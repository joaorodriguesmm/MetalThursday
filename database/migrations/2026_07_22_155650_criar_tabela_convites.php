<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos convites de registo.
 *
 * Os convites são criados antes dos respetivos utilizadores. Apenas o hash
 * do código é persistido, impedindo a recuperação do código original através
 * da base de dados.
 *
 * @return Migration Migração da tabela dos convites.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Nome da restrição única do hash do código.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const CODIGO_HASH_UNICO =
        'convites_codigo_hash_unico';

    /**
     * Nome do índice do endereço de e-mail de destino.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const EMAIL_DESTINO_INDICE =
        'convites_email_destino_indice';

    /**
     * Nome do índice da data de expiração.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const EXPIRA_EM_INDICE =
        'convites_expira_em_indice';

    /**
     * Cria a tabela dos convites.
     *
     * O estado do convite é determinado pelas datas de utilização,
     * revogação e expiração, evitando uma coluna de estado redundante.
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

                $tabela->string(
                    'nome_convidado',
                );

                $tabela
                    ->string('email_destino')
                    ->nullable();

                /*
                 * Hash hexadecimal SHA-256 do código original.
                 */
                $tabela->char(
                    'codigo_hash',
                    64,
                );

                /*
                 * Utilizador responsável pela criação do convite.
                 *
                 * O convite é preservado caso esse utilizador seja
                 * posteriormente eliminado.
                 */
                $tabela
                    ->foreignId('criado_por_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                /*
                 * Utilizador criado através do convite.
                 *
                 * Permanece nulo enquanto o convite ainda não tiver sido
                 * utilizado.
                 */
                $tabela
                    ->foreignId('utilizado_por_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela
                    ->timestamp('expira_em')
                    ->nullable();

                $tabela
                    ->timestamp('utilizado_em')
                    ->nullable();

                $tabela
                    ->timestamp('revogado_em')
                    ->nullable();

                $tabela->timestamps();

                $tabela->unique(
                    'codigo_hash',
                    self::CODIGO_HASH_UNICO,
                );

                $tabela->index(
                    'email_destino',
                    self::EMAIL_DESTINO_INDICE,
                );

                $tabela->index(
                    'expira_em',
                    self::EXPIRA_EM_INDICE,
                );
            },
        );
    }

    /**
     * Elimina a tabela dos convites.
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
