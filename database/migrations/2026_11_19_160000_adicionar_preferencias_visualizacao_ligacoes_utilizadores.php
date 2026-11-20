<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda as preferências pessoais de apresentação das plataformas musicais.
 *
 * O valor nulo mantém as ligações visíveis sem carregar incorporações.
 *
 * @since 2.0.0
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'utilizadores',
            static function (Blueprint $tabela): void {
                $tabela
                    ->json('preferencias_visualizacao_ligacoes')
                    ->nullable();
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'utilizadores',
            static function (Blueprint $tabela): void {
                $tabela->dropColumn('preferencias_visualizacao_ligacoes');
            },
        );
    }
};
