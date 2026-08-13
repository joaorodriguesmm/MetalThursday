<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria as tabelas técnicas de cache do Laravel.
 *
 * O nome `cache` é mantido por ser também o termo técnico utilizado em
 * português. Os nomes das colunas permanecem de acordo com o contrato do
 * armazenamento de cache do Laravel.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria as tabelas de cache e de bloqueios atómicos.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'cache',
            static function (Blueprint $tabela): void {
                $tabela
                    ->string(
                        'key',
                        255,
                    )
                    ->collation(
                        'utf8mb4_bin',
                    )
                    ->primary();

                $tabela->mediumText(
                    'value',
                );

                $tabela
                    ->unsignedBigInteger(
                        'expiration',
                    )
                    ->index();
            },
        );

        Schema::create(
            'bloqueios_cache',
            static function (Blueprint $tabela): void {
                $tabela
                    ->string(
                        'key',
                        255,
                    )
                    ->collation(
                        'utf8mb4_bin',
                    )
                    ->primary();

                $tabela
                    ->string(
                        'owner',
                        255,
                    )
                    ->collation(
                        'utf8mb4_bin',
                    );

                $tabela
                    ->unsignedBigInteger(
                        'expiration',
                    )
                    ->index();
            },
        );
    }

    /**
     * Elimina as tabelas de cache.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'bloqueios_cache',
        );

        Schema::dropIfExists(
            'cache',
        );
    }
};
