<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela genérica das ligações externas.
 *
 * A entidade proprietária é representada através de uma relação polimórfica.
 * Os aliases persistidos são estáveis e não dependem dos namespaces PHP.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das ligações.
     *
     * @since 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'ligacoes',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela->enum(
                    'tipo_ligavel',
                    [
                        'artista',
                        'utilizador',
                    ],
                );

                $tabela->unsignedBigInteger(
                    'ligavel_id',
                );

                $tabela->string(
                    'titulo',
                    100,
                );

                $tabela->string(
                    'url',
                    2048,
                );

                $tabela->unsignedSmallInteger(
                    'ordem',
                );

                $tabela->timestamps();

                $tabela->index(
                    [
                        'tipo_ligavel',
                        'ligavel_id',
                        'ordem',
                    ],
                    'ligacoes_ligavel_ordem_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela das ligações.
     *
     * @since 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'ligacoes',
        );
    }
};
