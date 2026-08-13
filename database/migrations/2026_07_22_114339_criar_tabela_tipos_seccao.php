<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos tipos de secção de uma MetalThursday.
 *
 * Cada tipo possui um identificador técnico estável, um nome destinado à
 * apresentação, uma descrição, uma ordem e a indicação de que exige
 * informação musical detalhada.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos tipos de secção.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'tipos_seccao',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string(
                        'identificador',
                        32,
                    )
                    ->charset('ascii')
                    ->collation('ascii_bin');

                $tabela->string(
                    'nome',
                    64,
                );

                $tabela->mediumText(
                    'descricao',
                );

                $tabela
                    ->boolean(
                        'exige_detalhes',
                    )
                    ->default(
                        false,
                    );

                $tabela->unsignedTinyInteger(
                    'ordem',
                );

                $tabela->timestamps();

                $tabela->unique(
                    'identificador',
                    'tipos_seccao_identificador_unico',
                );

                $tabela->unique(
                    'nome',
                    'tipos_seccao_nome_unico',
                );

                $tabela->unique(
                    'ordem',
                    'tipos_seccao_ordem_unica',
                );
            },
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `tipos_seccao`
                ADD CONSTRAINT `tipos_seccao_identificador_formato_valido`
                CHECK (
                    BINARY `identificador` REGEXP '^[a-z0-9]+(_[a-z0-9]+)*$'
                )
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `tipos_seccao`
                ADD CONSTRAINT `tipos_seccao_exige_detalhes_valido`
                CHECK (`exige_detalhes` IN (0, 1))
            SQL,
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `tipos_seccao`
                ADD CONSTRAINT `tipos_seccao_ordem_valida`
                CHECK (`ordem` BETWEEN 1 AND 255)
            SQL,
        );
    }

    /**
     * Elimina a tabela dos tipos de secção.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'tipos_seccao',
        );
    }
};
