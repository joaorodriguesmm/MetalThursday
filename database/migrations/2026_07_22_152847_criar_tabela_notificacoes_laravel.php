<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela técnica das notificações persistidas pelo Laravel.
 *
 * O nome da tabela é definido pelo MetalThursday e utiliza português. Os
 * nomes das colunas permanecem de acordo com o contrato do canal de
 * notificações em base de dados do Laravel.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das notificações persistidas.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'notificacoes',
            static function (Blueprint $tabela): void {
                $tabela
                    ->char(
                        'id',
                        36,
                    )
                    ->charset('ascii')
                    ->collation('ascii_bin')
                    ->primary();

                $tabela->string(
                    'type',
                    255,
                );

                $tabela->morphs(
                    'notifiable',
                    'notificacoes_notificavel_indice',
                );

                $tabela->text(
                    'data',
                );

                $tabela
                    ->timestamp(
                        'read_at',
                    )
                    ->nullable();

                $tabela->timestamps();
            },
        );
    }

    /**
     * Elimina a tabela das notificações persistidas.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'notificacoes',
        );
    }
};
