<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos utilizadores da aplicação.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos utilizadores.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'utilizadores',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->string('nome');

                $tabela
                    ->string('email')
                    ->unique();

                /*
                 * Estes campos mantêm os nomes convencionais do Laravel.
                 */
                $tabela
                    ->timestamp('email_verified_at')
                    ->nullable();

                $tabela->string('password');

                $tabela
                    ->string('fotografia')
                    ->nullable();

                $tabela
                    ->string('papel', 32)
                    ->default('utilizador')
                    ->index();

                $tabela->rememberToken();
                $tabela->timestamps();
            },
        );
    }

    /**
     * Elimina a tabela dos utilizadores.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('utilizadores');
    }
};
