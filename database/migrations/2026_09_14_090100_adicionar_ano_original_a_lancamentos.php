<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona o ano original ao catálogo de lançamentos musicais.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Adiciona o ano original, mantendo-o opcional quando não é conhecido de
     * forma fiável.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::table(
            'lancamentos',
            static function (Blueprint $tabela): void {
                $tabela
                    ->unsignedSmallInteger(
                        'ano_original',
                    )
                    ->nullable()
                    ->after(
                        'tipo',
                    );
            },
        );
    }

    /**
     * Remove o ano original dos lançamentos.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::table(
            'lancamentos',
            static function (Blueprint $tabela): void {
                $tabela->dropColumn(
                    'ano_original',
                );
            },
        );
    }
};
