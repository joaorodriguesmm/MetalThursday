<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das origens geográficas disponíveis na aplicação.
 *
 * Uma origem geográfica pode representar um país, uma nação constituinte,
 * um território ou uma origem internacional agregada. O código é,
 * consequentemente, um identificador geográfico da aplicação e não
 * necessariamente um código ISO 3166-1 alfa-2.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das origens geográficas.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'origens_geograficas',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->string(
                    'nome',
                    100,
                );

                $tabela
                    ->string(
                        'codigo',
                        8,
                    )
                    ->charset('ascii')
                    ->collation('ascii_bin');

                $tabela->timestamps();

                $tabela->unique(
                    'nome',
                    'origens_geograficas_nome_unico',
                );

                $tabela->unique(
                    'codigo',
                    'origens_geograficas_codigo_unico',
                );
            },
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE `origens_geograficas`
                ADD CONSTRAINT `origens_geograficas_codigo_formato_valido`
                CHECK (
                    CHAR_LENGTH(`codigo`) BETWEEN 2 AND 8
                    AND BINARY `codigo` REGEXP '^[A-Z0-9]+(-[A-Z0-9]+)*$'
                )
            SQL,
        );
    }

    /**
     * Elimina a tabela das origens geográficas.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'origens_geograficas',
        );
    }
};
