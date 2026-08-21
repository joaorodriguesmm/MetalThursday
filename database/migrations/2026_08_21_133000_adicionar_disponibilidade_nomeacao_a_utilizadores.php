<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona a disponibilidade dos utilizadores para novas nomeações.
 *
 * A indisponibilidade é voluntária e afeta apenas futuras nomeações. Não
 * altera reservas anteriormente atribuídas nem o estado de acesso.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Adiciona a disponibilidade para nomeação.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::table(
            'utilizadores',
            static function (Blueprint $tabela): void {
                $tabela
                    ->boolean(
                        'disponivel_para_nomeacao',
                    )
                    ->default(
                        true,
                    );
            },
        );
    }

    /**
     * Remove a disponibilidade para nomeação.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::table(
            'utilizadores',
            static function (Blueprint $tabela): void {
                $tabela->dropColumn(
                    'disponivel_para_nomeacao',
                );
            },
        );
    }
};
