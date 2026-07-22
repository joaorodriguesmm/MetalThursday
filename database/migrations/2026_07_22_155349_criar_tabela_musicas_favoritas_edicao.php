<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria a tabela das músicas favoritas escolhidas em cada edição.
 *
 * Cada utilizador pode selecionar três músicas favoritas por edição,
 * indicando a respetiva posição de preferência.
 *
 * @return Migration Migração das músicas favoritas das edições.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Cria a tabela das músicas favoritas das edições.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function up(): void
    {
        Schema::create(
            'musicas_favoritas_edicao',
            function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('edicao_id')
                    ->constrained('edicoes')
                    ->cascadeOnDelete();

                /*
                 * Utilizador a quem pertence a escolha.
                 *
                 * A referência fica anulável para permitir preservar o
                 * histórico caso a conta seja eliminada fisicamente.
                 */
                $tabela
                    ->foreignId('utilizador_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                /*
                 * Posição da música entre as três favoritas.
                 *
                 * O intervalo entre 1 e 3 será validado na aplicação.
                 */
                $tabela->unsignedTinyInteger(
                    'posicao',
                );

                /*
                 * A estrutura antiga guardava a escolha como texto livre.
                 *
                 * Mantemos esse comportamento até existir uma entidade
                 * própria para músicas.
                 */
                $tabela->string('musica');

                /*
                 * Utilizador que registou a escolha.
                 *
                 * Pode ser diferente do utilizador proprietário quando um
                 * administrador regista escolhas em nome de outra pessoa.
                 */
                $tabela
                    ->foreignId('registado_por_id')
                    ->nullable()
                    ->constrained('utilizadores')
                    ->nullOnDelete();

                $tabela->timestamps();

                /*
                 * Um utilizador só pode ocupar cada posição uma vez dentro
                 * da mesma edição.
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
                 * Impede que o utilizador escolha exatamente a mesma música
                 * mais do que uma vez na mesma edição.
                 */
                $tabela->unique(
                    [
                        'edicao_id',
                        'utilizador_id',
                        'musica',
                    ],
                    'musicas_favoritas_edicao_musica_unica',
                );

                $tabela->index(
                    [
                        'edicao_id',
                        'posicao',
                    ],
                    'musicas_favoritas_edicao_posicao_indice',
                );
            },
        );
    }

    /**
     * Elimina a tabela das músicas favoritas das edições.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'musicas_favoritas_edicao',
        );
    }
};
