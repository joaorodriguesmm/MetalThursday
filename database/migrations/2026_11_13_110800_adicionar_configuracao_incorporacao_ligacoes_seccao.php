<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permite escolher uma incorporação sem alterar a ligação pública original.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Acrescenta opções de incorporação retrocompatíveis.
     */
    public function up(): void
    {
        Schema::table(
            'ligacoes_seccao_metal_thursday',
            static function (Blueprint $tabela): void {
                $tabela
                    ->string('tipo_incorporacao', 16)
                    ->charset('ascii')
                    ->collation('ascii_bin')
                    ->nullable()
                    ->after('incorporar');

                $tabela
                    ->string('url_incorporacao_alternativa', 2048)
                    ->nullable()
                    ->after('tipo_incorporacao');
            },
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `ligacoes_seccao_metal_thursday`
                ADD CONSTRAINT `ligacoes_seccao_tipo_incorporacao_valido`
                CHECK (`tipo_incorporacao` IS NULL OR `tipo_incorporacao` IN ('video', 'playlist'))
            SQL,
        );
    }

    /**
     * Remove apenas as opções acrescentadas nesta migração.
     */
    public function down(): void
    {
        DB::statement(
            <<<'SQL'
            ALTER TABLE `ligacoes_seccao_metal_thursday`
                DROP CONSTRAINT `ligacoes_seccao_tipo_incorporacao_valido`
            SQL,
        );

        Schema::table(
            'ligacoes_seccao_metal_thursday',
            static function (Blueprint $tabela): void {
                $tabela->dropColumn([
                    'tipo_incorporacao',
                    'url_incorporacao_alternativa',
                ]);
            },
        );
    }
};
