<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona o marcador durável da notificação de publicação.
 *
 * Os registos existentes são marcados durante a migração para impedir que
 * MetalThursdays históricas ou criadas pelo comportamento anterior originem
 * notificações retroativas.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Nome do índice utilizado pela consulta das publicações por notificar.
     *
     * @since 2.0.0
     */
    private const INDICE_PUBLICACOES_POR_NOTIFICAR =
        'metal_thursdays_publicacao_notificada_data_idx';

    /**
     * Aplica a migração.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::table(
            'metal_thursdays',
            static function (
                Blueprint $tabela,
            ): void {
                $tabela
                    ->timestamp(
                        'publicacao_notificada_em',
                    )
                    ->nullable()
                    ->after(
                        'data',
                    );

                $tabela->index(
                    [
                        'publicacao_notificada_em',
                        'data',
                    ],
                    self::INDICE_PUBLICACOES_POR_NOTIFICAR,
                );
            },
        );

        /*
         * Todos os registos existentes pertencem ao comportamento anterior,
         * no qual a notificação era tratada no momento da criação. Marcam-se
         * como tratados para nunca serem reenviados retroativamente.
         */
        DB::table(
            'metal_thursdays',
        )
            ->whereNull(
                'publicacao_notificada_em',
            )
            ->update([
                'publicacao_notificada_em' => now(),
            ]);
    }

    /**
     * Reverte a migração.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::table(
            'metal_thursdays',
            static function (
                Blueprint $tabela,
            ): void {
                $tabela->dropIndex(
                    self::INDICE_PUBLICACOES_POR_NOTIFICAR,
                );

                $tabela->dropColumn(
                    'publicacao_notificada_em',
                );
            },
        );
    }
};
