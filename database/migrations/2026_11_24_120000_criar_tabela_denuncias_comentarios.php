<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Regista denúncias de comentários e respetivas decisões de moderação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('denuncias_comentarios', static function (Blueprint $tabela): void {
            $tabela->id();

            $tabela->foreignId('comentario_id')
                ->constrained('comentarios')
                ->cascadeOnDelete();

            $tabela->foreignId('denunciante_id')
                ->constrained('utilizadores')
                ->cascadeOnDelete();

            $tabela->string('motivo', 24);
            $tabela->string('detalhes', 1000)->nullable();
            $tabela->string('estado', 16)->default('pendente');

            $tabela->foreignId('moderador_id')
                ->nullable()
                ->constrained('utilizadores')
                ->nullOnDelete();

            $tabela->timestamp('decidida_em')->nullable();
            $tabela->timestamps();

            $tabela->unique(
                ['comentario_id', 'denunciante_id'],
                'denuncias_comentarios_unicidade',
            );

            $tabela->index(
                ['estado', 'created_at', 'id'],
                'denuncias_comentarios_estado_ordem',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('denuncias_comentarios');
    }
};
