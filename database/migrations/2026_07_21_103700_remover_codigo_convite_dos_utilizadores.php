<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável por remover a coluna histórica dos códigos
 * de convite da tabela dos utilizadores.
 *
 * Os convites passam a ser geridos exclusivamente através da tabela
 * `convites`. Os valores existentes em `users.invite_code` não são migrados,
 * porque pertencem ao fluxo antigo e já não são necessários.
 *
 * Esta alteração elimina definitivamente os valores históricos da coluna.
 *
 * @return Migration - Migração de remoção do código de convite histórico.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Remove a coluna histórica dos códigos de convite.
     *
     * @return void
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::table(
            'users',
            function (Blueprint $tabela): void {
                $tabela->dropColumn('invite_code');
            },
        );
    }

    /**
     * Repõe apenas a estrutura da coluna histórica.
     *
     * Os códigos anteriormente eliminados não podem ser recuperados por esta
     * reversão. A coluna é recriada anulável para não invalidar utilizadores
     * criados pelo novo fluxo de convites.
     *
     * @return void
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::table(
            'users',
            function (Blueprint $tabela): void {
                $tabela
                    ->string('invite_code')
                    ->nullable()
                    ->after('photo');
            },
        );
    }
};
