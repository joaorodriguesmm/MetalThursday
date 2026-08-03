<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela dos registos de audição.
 *
 * Os registos de audição podem pertencer a diferentes entidades da aplicação
 * através de uma relação polimórfica.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Tipos de entidades que podem ser marcadas como ouvidas.
     *
     * Estes valores correspondem aos aliases polimórficos persistidos pela
     * aplicação e não devem depender dos namespaces PHP dos modelos.
     *
     * @var list<string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TIPOS_AUDIVEL = [
        'metal_thursday',
        'seccao_metal_thursday',
    ];

    /**
     * Cria a tabela dos registos de audição.
     *
     * Cada utilizador pode marcar apenas uma vez a mesma entidade como
     * ouvida.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'audicoes',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId(
                        'utilizador_id',
                    )
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                $tabela->enum(
                    'tipo_audivel',
                    self::TIPOS_AUDIVEL,
                );

                $tabela->unsignedBigInteger(
                    'audivel_id',
                );

                $tabela->timestamps();

                $tabela->index(
                    [
                        'tipo_audivel',
                        'audivel_id',
                    ],
                    'audicoes_audivel_indice',
                );

                $tabela->unique(
                    [
                        'utilizador_id',
                        'tipo_audivel',
                        'audivel_id',
                    ],
                    'audicoes_utilizador_audivel_unico',
                );
            },
        );
    }

    /**
     * Elimina a tabela dos registos de audição.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'audicoes',
        );
    }
};
