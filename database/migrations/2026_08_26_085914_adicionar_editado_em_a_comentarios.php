<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona o momento da última edição aos comentários.
 *
 * O atributo distingue explicitamente comentários nunca editados de
 * comentários cujo conteúdo já foi alterado, sem depender da diferença entre
 * os timestamps gerais do modelo.
 *
 * Os comentários ativos cuja edição já podia ser determinada pelos timestamps
 * anteriores são migrados para o novo estado explícito.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Adiciona o momento da última edição.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::table(
            'comentarios',
            static function (Blueprint $tabela): void {
                $tabela
                    ->timestamp(
                        'editado_em',
                    )
                    ->nullable()
                    ->after(
                        'conteudo',
                    );
            },
        );

        DB::table(
            'comentarios',
        )
            ->whereNull(
                'conteudo_eliminado_em',
            )
            ->whereColumn(
                'updated_at',
                '<>',
                'created_at',
            )
            ->update([
                'editado_em' => DB::raw(
                    'updated_at',
                ),
            ]);
    }

    /**
     * Remove o momento da última edição.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::table(
            'comentarios',
            static function (Blueprint $tabela): void {
                $tabela->dropColumn(
                    'editado_em',
                );
            },
        );
    }
};
