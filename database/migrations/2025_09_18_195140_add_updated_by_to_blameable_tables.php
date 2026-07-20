<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável por adicionar a coluna de auditoria
 * `updated_by` às tabelas auditáveis.
 *
 * A coluna identifica o utilizador responsável pela última atualização de
 * cada registo.
 *
 * Os nomes físicos das tabelas e colunas permanecem temporariamente em
 * inglês para garantir compatibilidade com a estrutura atual da base de
 * dados.
 *
 * @return Migration - Migração da coluna de auditoria de atualização.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Tabelas que recebem a coluna de auditoria `updated_by`.
     *
     * @var array<int, string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TABELAS_COM_AUDITORIA = [
        'bands',
        'genres',
        'mt_editions',
        'metal_thursdays',
        'mt_sections',
    ];

    /**
     * Adiciona a coluna de auditoria `updated_by` às tabelas configuradas.
     *
     * A coluna é anulável para preservar o registo quando o utilizador
     * responsável pela atualização for eliminado fisicamente.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        foreach (self::TABELAS_COM_AUDITORIA as $nomeTabela) {
            Schema::table(
                $nomeTabela,
                function (Blueprint $tabela): void {
                    $tabela
                        ->foreignId('updated_by')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();
                },
            );
        }
    }

    /**
     * Remove a coluna de auditoria `updated_by` das tabelas configuradas.
     *
     * A chave estrangeira e a respetiva coluna são removidas em conjunto.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        foreach (
            array_reverse(self::TABELAS_COM_AUDITORIA) as $nomeTabela
        ) {
            Schema::table(
                $nomeTabela,
                function (Blueprint $tabela): void {
                    $tabela->dropConstrainedForeignId(
                        'updated_by',
                    );
                },
            );
        }
    }
};
