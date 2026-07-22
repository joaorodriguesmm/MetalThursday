<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria as tabelas técnicas de cache do Laravel.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria as tabelas de cache e bloqueios de cache.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'cache',
            function (Blueprint $tabela): void {
                $tabela
                    ->string('key')
                    ->primary();

                $tabela->mediumText('value');

                $tabela
                    ->integer('expiration')
                    ->index();
            },
        );

        Schema::create(
            'cache_locks',
            function (Blueprint $tabela): void {
                $tabela
                    ->string('key')
                    ->primary();

                $tabela->string('owner');

                $tabela
                    ->integer('expiration')
                    ->index();
            },
        );
    }

    /**
     * Elimina as tabelas de cache.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};
