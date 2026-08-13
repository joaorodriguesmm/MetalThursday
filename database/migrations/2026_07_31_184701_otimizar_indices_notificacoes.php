<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Otimiza os índices utilizados pelas consultas das notificações.
 *
 * O índice original cobre apenas o tipo e o identificador do destinatário.
 * As consultas da aplicação filtram adicionalmente pela data de leitura ou
 * ordenam pela data de criação e pelo identificador.
 *
 * O índice original é substituído por dois índices compostos, evitando manter
 * um terceiro índice redundante com o mesmo prefixo.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Substitui o índice genérico pelos índices das consultas reais.
     *
     * O primeiro índice suporta:
     *
     * - a verificação da existência de notificações por ler;
     * - a contagem de notificações por ler;
     * - a marcação em massa das notificações por ler.
     *
     * O segundo índice suporta a paginação cronológica das notificações de
     * cada utilizador, incluindo o identificador utilizado para desempatar
     * registos com a mesma data de criação.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::table(
            'notificacoes',
            static function (Blueprint $tabela): void {
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

                $tabela->dropIndex(
                    'notificacoes_notificavel_indice',
                );
            },
        );
    }

    /**
     * Remove os índices especializados e restaura o índice original.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::table(
            'notificacoes',
            static function (Blueprint $tabela): void {
                $tabela->index(
                    [
                        'notifiable_type',
                        'notifiable_id',
                    ],
                    'notificacoes_notificavel_indice',
                );

                $tabela->dropIndex(
                    'notificacoes_destinatario_leitura_indice',
                );

                $tabela->dropIndex(
                    'notificacoes_destinatario_criacao_indice',
                );
            },
        );
    }
};
