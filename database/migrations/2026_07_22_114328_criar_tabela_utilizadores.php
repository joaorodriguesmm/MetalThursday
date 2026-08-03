<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos utilizadores da aplicação.
 *
 * Os campos `email`, `email_verified_at`, `password` e `remember_token`
 * permanecem em inglês por integrarem os contratos de autenticação,
 * verificação de e-mail e recuperação da palavra-passe do Laravel.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela dos utilizadores.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'utilizadores',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->string(
                    'nome',
                    255,
                );

                $tabela
                    ->string(
                        'email',
                        255,
                    )
                    ->unique();

                $tabela
                    ->timestamp(
                        'email_verified_at',
                    )
                    ->nullable();

                $tabela->string(
                    'password',
                    255,
                );

                $tabela
                    ->string(
                        'fotografia',
                        255,
                    )
                    ->nullable();

                $tabela
                    ->string(
                        'papel',
                        32,
                    )
                    ->default(
                        'utilizador',
                    )
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
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'utilizadores',
        );
    }
};
