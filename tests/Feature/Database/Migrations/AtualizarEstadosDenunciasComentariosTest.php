<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\DenunciaComentario;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AtualizarEstadosDenunciasComentariosTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function converte_estados_sem_perder_dados_e_permite_reversao(): void
    {
        $autor = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()->comAutor($autor)->create();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário para testar a migração.',
            'comentario_pai_id' => null,
        ]);

        foreach (['pendente', 'ignorada', 'procedente'] as $estado) {
            $denunciante = Utilizador::factory()->create();

            $denuncia = DenunciaComentario::query()->create([
                'comentario_id' => $comentario->id,
                'denunciante_id' => $denunciante->id,
                'motivo' => 'spam',
                'detalhes' => 'Detalhes da denúncia.',
            ]);

            DB::table('denuncias_comentarios')
                ->where('id', $denuncia->id)
                ->update(['estado' => $estado]);
        }

        $consulta = DB::table('denuncias_comentarios')->orderBy('id');

        $colunas = [
            'id',
            'comentario_id',
            'denunciante_id',
            'motivo',
            'detalhes',
            'moderador_id',
            'decidida_em',
            'created_at',
            'updated_at',
        ];

        $dadosOriginais = $consulta->get($colunas)->all();

        $migracao = require database_path(
            'migrations/2026_11_25_090000_atualizar_estados_denuncias_comentarios.php',
        );

        self::assertInstanceOf(Migration::class, $migracao);

        if (! method_exists($migracao, 'up') || ! method_exists($migracao, 'down')) {
            self::fail('A migração não disponibiliza os métodos up e down.');
        }

        $migracao->up();

        self::assertSame(
            ['pendente', 'rejeitada', 'aceite'],
            $consulta->pluck('estado')->all(),
        );

        self::assertEquals(
            $dadosOriginais,
            $consulta->get($colunas)->all(),
        );

        $migracao->down();

        self::assertSame(
            ['pendente', 'ignorada', 'procedente'],
            $consulta->pluck('estado')->all(),
        );

        self::assertEquals(
            $dadosOriginais,
            $consulta->get($colunas)->all(),
        );

        $migracao->up();

        self::assertSame(
            ['pendente', 'rejeitada', 'aceite'],
            $consulta->pluck('estado')->all(),
        );

        $this->assertDatabaseCount('denuncias_comentarios', 3);
    }
}
