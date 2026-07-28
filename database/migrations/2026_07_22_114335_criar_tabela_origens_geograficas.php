<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das origens geográficas.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'origens_geograficas',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->string(
                        'nome',
                        100,
                    )
                    ->unique();

                $tabela
                    ->string(
                        'codigo',
                        8,
                    )
                    ->unique();

                $tabela->timestamps();
            },
        );
    }

    /**
     * Elimina a tabela das origens geográficas.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'origens_geograficas',
        );
    }
};
