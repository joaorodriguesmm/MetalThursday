<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das músicas favoritas escolhidas em cada edição.
 *
 * Cada utilizador pode selecionar até três músicas favoritas por edição,
 * atribuindo-lhes posições de preferência entre um e três.
 *
 * A identificação da música permanece guardada como texto livre enquanto
 * não existir uma entidade própria para representar músicas.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das músicas favoritas das edições.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function up(): void
    {
        Schema::create(
            'musicas_favoritas_edicao',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId(
                        'edicao_id',
                    )
                    ->constrained(
                        table: 'edicoes',
                    )
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                /*
                 * Utilizador a quem pertence a escolha.
                 *
                 * A eliminação física é restringida para preservar a
                 * classificação histórica associada à edição.
                 */
                $tabela
                    ->foreignId(
                        'utilizador_id',
                    )
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                $tabela->unsignedTinyInteger(
                    'posicao',
                );

                $tabela->string(
                    'musica',
                    255,
                );

                /*
                 * Utilizador que registou a escolha.
                 *
                 * Pode ser diferente do proprietário quando um administrador
                 * regista as escolhas em nome de outra pessoa.
                 */
                $tabela
                    ->foreignId(
                        'registado_por_id',
                    )
                    ->nullable()
                    ->constrained(
                        table: 'utilizadores',
                    )
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

                $tabela->timestamps();

                /*
                 * Cada posição pode ser ocupada apenas uma vez por utilizador
                 * dentro da mesma edição.
                 */
                $tabela->unique(
                    [
                        'edicao_id',
                        'utilizador_id',
                        'posicao',
                    ],
                    'musicas_favoritas_edicao_posicao_unica',
                );

                /*
                 * A mesma música não pode ser selecionada mais do que uma vez
                 * pelo mesmo utilizador dentro da mesma edição.
                 */
                $tabela->unique(
                    [
                        'edicao_id',
                        'utilizador_id',
                        'musica',
                    ],
                    'musicas_favoritas_edicao_musica_unica',
                );

                /*
                 * Otimiza a agregação das escolhas por edição e posição.
                 */
                $tabela->index(
                    [
                        'edicao_id',
                        'posicao',
                    ],
                    'musicas_favoritas_edicao_posicao_indice',
                );
            },
        );

        DB::statement(
            <<<'SQL'
                ALTER TABLE `musicas_favoritas_edicao`
                ADD CONSTRAINT `musicas_favoritas_edicao_posicao_valida_verificacao`
                CHECK (`posicao` BETWEEN 1 AND 3)
                SQL,
        );

        DB::statement(
            <<<'SQL'
                ALTER TABLE `musicas_favoritas_edicao`
                ADD CONSTRAINT `musicas_favoritas_edicao_musica_valida_verificacao`
                CHECK (CHAR_LENGTH(TRIM(`musica`)) BETWEEN 1 AND 255)
                SQL,
        );
    }

    /**
     * Elimina a tabela das músicas favoritas das edições.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'musicas_favoritas_edicao',
        );
    }
};
