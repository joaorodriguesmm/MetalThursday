<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos comentários.
 *
 * Os comentários podem pertencer a diferentes entidades da aplicação
 * através de uma relação polimórfica e podem responder a outros comentários.
 *
 * A aplicação é responsável por garantir que um comentário não responde a
 * si próprio, que o comentário pai pertence à mesma entidade comentável e
 * que não são criados ciclos na árvore de respostas.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Tipos de entidades que podem receber comentários.
     *
     * Estes valores correspondem aos aliases polimórficos persistidos pela
     * aplicação e não dependem dos namespaces PHP dos modelos.
     *
     * @var list<string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TIPOS_COMENTAVEL = [
        'metal_thursday',
        'seccao_metal_thursday',
    ];

    /**
     * Cria a tabela dos comentários.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'comentarios',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId(
                        'utilizador_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela->text(
                    'conteudo',
                );

                $tabela->enum(
                    'tipo_comentavel',
                    self::TIPOS_COMENTAVEL,
                );

                $tabela->unsignedBigInteger(
                    'comentavel_id',
                );

                $tabela
                    ->foreignId(
                        'comentario_pai_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'comentarios',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela->timestamps();

                $tabela->softDeletes();

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

        DB::statement(
            <<<'SQL'
                ALTER TABLE `comentarios`
                ADD CONSTRAINT `comentarios_conteudo_valido_verificacao`
                CHECK (
                    CHAR_LENGTH(TRIM(`conteudo`))
                    BETWEEN 1 AND 2000
                )
                SQL,
        );
    }

    /**
     * Elimina a tabela dos comentários.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'comentarios',
        );
    }
};
