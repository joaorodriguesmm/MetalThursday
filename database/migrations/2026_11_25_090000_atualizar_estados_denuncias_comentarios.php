<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Uniformiza a terminologia dos estados das denúncias de comentários.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(static function (): void {
            DB::table('denuncias_comentarios')
                ->where('estado', 'ignorada')
                ->update(['estado' => 'rejeitada']);

            DB::table('denuncias_comentarios')
                ->where('estado', 'procedente')
                ->update(['estado' => 'aceite']);
        });
    }

    public function down(): void
    {
        DB::transaction(static function (): void {
            DB::table('denuncias_comentarios')
                ->where('estado', 'rejeitada')
                ->update(['estado' => 'ignorada']);

            DB::table('denuncias_comentarios')
                ->where('estado', 'aceite')
                ->update(['estado' => 'procedente']);
        });
    }
};
