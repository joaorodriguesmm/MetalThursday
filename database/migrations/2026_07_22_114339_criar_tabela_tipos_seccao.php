<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos tipos de secção de uma MetalThursday.
 *
 * Cada tipo possui um identificador técnico estável, um nome destinado à
 * apresentação, uma descrição, uma ordem e a indicação de que exige
 * informação musical detalhada.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos tipos de secção.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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
                    ->unique();

                $tabela
                    ->string(
                        'nome',
                        64,
                    )
                    ->unique();

                $tabela->text(
                    'descricao',
                );

                $tabela
                    ->boolean(
                        'exige_detalhes',
                    )
                    ->default(
                        false,
                    );

                $tabela
                    ->unsignedTinyInteger(
                        'ordem',
                    )
                    ->unique();

                $tabela->timestamps();
            },
        );
    }

    /**
     * Elimina a tabela dos tipos de secção.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'tipos_seccao',
        );
    }
};
