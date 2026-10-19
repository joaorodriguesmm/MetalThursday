<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identifica tentativas de criação de comentários e respostas.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'submissoes_comentarios',
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

                $tabela
                    ->unsignedBigInteger('comentario_id')
                    ->nullable();

                $tabela->timestamps();

                $tabela->unique(
                    ['utilizador_id', 'chave'],
                    'submissoes_comentarios_utilizador_chave_unica',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'submissoes_comentarios',
        );
    }
};
