<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das secções das MetalThursdays.
 *
 * Cada secção pertence a uma MetalThursday e a um tipo de secção. Pode ainda
 * estar associada a uma banda e conter diferentes elementos de apresentação.
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das secções das MetalThursdays.
     *
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function up(): void
    {
        Schema::create(
            'seccoes_metal_thursday',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('metal_thursday_id')
                    ->constrained('metal_thursdays')
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId('tipo_seccao_id')
                    ->constrained('tipos_seccao')
                    ->restrictOnDelete();

                /*
                 * Posição da secção dentro da MetalThursday.
                 *
                 * A ordenação começa em 1 e a unicidade entre secções ativas
                 * será garantida pela aplicação, devido ao uso de eliminação
                 * lógica.
                 */
                $tabela->unsignedSmallInteger(
                    'ordem',
                );

                $tabela
                    ->string('titulo')
                    ->nullable();

                $tabela
                    ->text('descricao')
                    ->nullable();

                $tabela
                    ->foreignId('banda_id')
                    ->nullable()
                    ->constrained('bandas')
                    ->nullOnDelete();

                $tabela
                    ->string(
                        'ligacao',
                        2048,
                    )
                    ->nullable();

                $tabela
                    ->string(
                        'tipo_incorporacao',
                        32,
                    )
                    ->nullable();

                $tabela
                    ->year('ano')
                    ->nullable();

                $tabela
                    ->foreignId('criado_por_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela
                    ->foreignId('atualizado_por_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela->timestamps();
                $tabela->softDeletes();

                $tabela->index(
                    [
                        'metal_thursday_id',
                        'tipo_seccao_id',
                    ],
                    'seccoes_metal_thursday_tipo_indice',
                );

                /*
                 * Otimiza a consulta das secções ativas de uma MetalThursday
                 * pela ordem definida.
                 */
                $tabela->index(
                    [
                        'metal_thursday_id',
                        'deleted_at',
                        'ordem',
                    ],
                    'seccoes_metal_thursday_ordem_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela das secções das MetalThursdays.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'seccoes_metal_thursday',
        );
    }
};
