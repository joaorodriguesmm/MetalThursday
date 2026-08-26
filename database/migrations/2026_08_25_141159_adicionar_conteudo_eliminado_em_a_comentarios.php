<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona o estado de conteúdo eliminado aos comentários.
 *
 * Um comentário que possua respostas pode manter-se estruturalmente na
 * conversa mesmo depois de o respetivo autor eliminar o conteúdo. O
 * `deleted_at` continua reservado para comentários que podem desaparecer
 * completamente da árvore.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Adiciona o momento de eliminação do conteúdo.
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
                        'conteudo_eliminado_em',
                    )
                    ->nullable()
                    ->after(
                        'conteudo',
                    );
            },
        );
    }

    /**
     * Remove o estado de conteúdo eliminado.
     *
     * Antes de remover a coluna, os marcadores estruturais ainda ativos são
     * eliminados logicamente. Desta forma, uma reversão da migração não volta
     * a expor o conteúdo que o utilizador tinha eliminado.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        $agora = now();

        DB::table(
            'comentarios',
        )
            ->whereNotNull(
                'conteudo_eliminado_em',
            )
            ->whereNull(
                'deleted_at',
            )
            ->update([
                'deleted_at' => $agora,
                'updated_at' => $agora,
            ]);

        Schema::table(
            'comentarios',
            static function (Blueprint $tabela): void {
                $tabela->dropColumn(
                    'conteudo_eliminado_em',
                );
            },
        );
    }
};
