<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela intermédia da hierarquia dos géneros musicais.
 *
 * Um género pode possuir vários géneros pais e cada género pai pode estar
 * relacionado com vários géneros filhos.
 *
 * A base de dados impede relações duplicadas e relações de um género consigo
 * próprio. A prevenção de ciclos com vários níveis pertence à camada da
 * aplicação.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela intermédia da hierarquia dos géneros.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'hierarquia_generos',
            static function (Blueprint $tabela): void {
                $tabela
                    ->foreignId(
                        'genero_id',
                    )
                    ->constrained(
                        table: 'generos',
                    )
                    ->cascadeOnDelete();

                $tabela
                    ->foreignId(
                        'genero_pai_id',
                    )
                    ->constrained(
                        table: 'generos',
                        indexName: 'hierarquia_generos_genero_pai_fk',
                    )
                    ->cascadeOnDelete();

                $tabela->primary(
                    [
                        'genero_id',
                        'genero_pai_id',
                    ],
                    'hierarquia_generos_primaria',
                );

                /*
                 * A chave primária começa por genero_id. Este índice adicional
                 * otimiza a consulta inversa dos filhos de um género pai.
                 */
                $tabela->index(
                    'genero_pai_id',
                    'hierarquia_generos_genero_pai_indice',
                );
            },
        );

        DB::statement(
            <<<'SQL'
                ALTER TABLE `hierarquia_generos`
                ADD CONSTRAINT `hierarquia_generos_generos_distintos_verificacao`
                CHECK (`genero_id` <> `genero_pai_id`)
                SQL,
        );
    }

    /**
     * Elimina a tabela intermédia da hierarquia dos géneros.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'hierarquia_generos',
        );
    }
};
