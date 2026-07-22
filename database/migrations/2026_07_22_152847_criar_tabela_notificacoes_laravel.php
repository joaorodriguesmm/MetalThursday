<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela técnica das notificações do Laravel.
 *
 * Os nomes físicos da tabela e das colunas permanecem de acordo com o
 * contrato do canal de notificações em base de dados do Laravel.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das notificações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'notifications',
            function (Blueprint $tabela): void {
                $tabela
                    ->uuid('id')
                    ->primary();

                $tabela->string('type');

                /*
                 * Cria notifiable_type e notifiable_id, assim como o
                 * respetivo índice composto.
                 */
                $tabela->morphs('notifiable');

                $tabela->text('data');

                $tabela
                    ->timestamp('read_at')
                    ->nullable();

                $tabela->timestamps();
            },
        );
    }

    /**
     * Elimina a tabela das notificações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
