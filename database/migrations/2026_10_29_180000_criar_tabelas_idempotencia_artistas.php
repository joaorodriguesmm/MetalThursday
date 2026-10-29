<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Serializa criações por nome e identifica tentativas repetidas de criação.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'bloqueios_nomes_artistas',
            static function (Blueprint $tabela): void {
                // Mesma collation da tabela de artistas para nomes equivalentes.
                $tabela->string('nome', 255)->primary();
            },
        );

        Schema::create(
            'submissoes_artistas',
            static function (Blueprint $tabela): void {
                $tabela->id();

                $tabela
                    ->foreignId('utilizador_id')
                    ->constrained('utilizadores')
                    ->cascadeOnDelete();

                $tabela->uuid('chave');

                $tabela
                    ->char('assinatura', 64)
                    ->charset('ascii')
                    ->collation('ascii_bin');

                // Sem FK: a submissão mantém-se mesmo após eliminar o artista.
                $tabela->unsignedBigInteger('artista_id')->nullable();
                $tabela->timestamps();

                $tabela->unique(
                    ['utilizador_id', 'chave'],
                    'submissoes_artistas_utilizador_chave_unica',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('submissoes_artistas');
        Schema::dropIfExists('bloqueios_nomes_artistas');
    }
};
