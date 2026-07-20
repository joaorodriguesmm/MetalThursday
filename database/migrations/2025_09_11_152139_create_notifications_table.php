<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retorna a migração responsável pela criação da tabela utilizada pelo
 * sistema de notificações do Laravel.
 *
 * Os nomes da tabela e das colunas permanecem em inglês porque fazem parte
 * do contrato interno do sistema de notificações do Laravel.
 *
 * @return Migration - Migração da tabela de notificações.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela utilizada pelo sistema de notificações do Laravel.
     *
     * A relação polimórfica permite associar notificações a diferentes tipos
     * de entidades notificáveis.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
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
                 * Cria as colunas notifiable_type e notifiable_id, juntamente
                 * com o índice composto necessário para localizar rapidamente
                 * as notificações de uma entidade.
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
     * Elimina a tabela utilizada pelo sistema de notificações do Laravel.
     *
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
