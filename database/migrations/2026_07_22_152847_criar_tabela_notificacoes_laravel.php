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
     * Cria a tabela das notificações persistidas com os índices das consultas
     * reais da aplicação.
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

                $tabela->string(
                    'notifiable_type',
                    255,
                );

                $tabela->unsignedBigInteger(
                    'notifiable_id',
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

                $tabela->index(
                    [
                        'notifiable_type',
                        'notifiable_id',
                        'read_at',
                    ],
                    'notificacoes_destinatario_leitura_indice',
                );

                $tabela->index(
                    [
                        'notifiable_type',
                        'notifiable_id',
                        'created_at',
                        'id',
                    ],
                    'notificacoes_destinatario_criacao_indice',
                );
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
