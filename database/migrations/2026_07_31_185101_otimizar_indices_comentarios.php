<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Otimiza os índices utilizados pela apresentação dos comentários.
 *
 * Os índices originais cobrem apenas a entidade comentada ou o comentário
 * pai. As consultas reais filtram também os registos eliminados logicamente
 * e ordenam pela data de criação e pelo identificador.
 *
 * Os índices anteriores são substituídos para não manter estruturas
 * redundantes com os mesmos prefixos.
 *
 * A criação e a remoção são executadas em alterações separadas da tabela.
 * Esta ordem garante que a chave estrangeira de `comentario_pai_id` permanece
 * sempre suportada por um índice válido no MariaDB.
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
return new class extends Migration
{
    /**
     * Substitui os índices genéricos pelos índices das consultas reais.
     *
     * Os índices especializados são criados antes da remoção dos índices
     * anteriores. Desta forma, a chave estrangeira de `comentario_pai_id`
     * continua suportada durante toda a alteração do esquema.
     *
     * O primeiro índice suporta a obtenção dos comentários principais de uma
     * entidade, incluindo o filtro de eliminação lógica e a ordenação
     * cronológica estável.
     *
     * O segundo índice suporta a obtenção das respostas de cada comentário
     * pai com o mesmo filtro e ordenação.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function up(): void
    {
        Schema::table(
            'comentarios',
            static function (Blueprint $tabela): void {
                $tabela->index(
                    [
                        'tipo_comentavel',
                        'comentavel_id',
                        'comentario_pai_id',
                        'deleted_at',
                        'created_at',
                        'id',
                    ],
                    'comentarios_comentavel_principal_ordem_indice',
                );

                $tabela->index(
                    [
                        'comentario_pai_id',
                        'deleted_at',
                        'created_at',
                        'id',
                    ],
                    'comentarios_respostas_ordem_indice',
                );
            },
        );

        Schema::table(
            'comentarios',
            static function (Blueprint $tabela): void {
                $tabela->dropIndex(
                    'comentarios_comentavel_indice',
                );

                $tabela->dropIndex(
                    'comentarios_pai_data_indice',
                );
            },
        );
    }

    /**
     * Remove os índices especializados e restaura os índices originais.
     *
     * Os índices originais são restaurados antes da remoção dos índices
     * especializados. Assim, a chave estrangeira de `comentario_pai_id`
     * permanece suportada durante a reversão da migration.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function down(): void
    {
        Schema::table(
            'comentarios',
            static function (Blueprint $tabela): void {
                $tabela->index(
                    [
                        'tipo_comentavel',
                        'comentavel_id',
                    ],
                    'comentarios_comentavel_indice',
                );

                $tabela->index(
                    [
                        'comentario_pai_id',
                        'created_at',
                    ],
                    'comentarios_pai_data_indice',
                );
            },
        );

        Schema::table(
            'comentarios',
            static function (Blueprint $tabela): void {
                $tabela->dropIndex(
                    'comentarios_comentavel_principal_ordem_indice',
                );

                $tabela->dropIndex(
                    'comentarios_respostas_ordem_indice',
                );
            },
        );
    }
};
