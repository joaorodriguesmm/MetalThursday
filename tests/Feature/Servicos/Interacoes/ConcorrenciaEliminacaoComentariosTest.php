<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use App\Servicos\Interacoes\ServicoEliminacaoComentarios;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class ConcorrenciaEliminacaoComentariosTest extends TestCase
{
    use DatabaseMigrations;

    protected function beforeRefreshingDatabase(): void
    {
        if (
            ! app()->environment('testing')
            || DB::getDefaultConnection() !== 'mariadb'
            || DB::connection()->getDatabaseName() !== 'metalthursday_testes'
        ) {
            throw new RuntimeException('Base de dados de testes obrigatória.');
        }
    }

    #[Test]
    public function eliminacao_utiliza_estado_atual_das_respostas(): void
    {
        $autor = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()->comAutor($autor)->create();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário denunciado.',
            'comentario_pai_id' => null,
        ]);

        config([
            'database.connections.concorrencia_denuncias' => config('database.connections.mariadb'),
        ]);

        $segundaLigacao = DB::connection('concorrencia_denuncias');
        $servico = app(ServicoEliminacaoComentarios::class);

        self::assertNotSame(
            DB::connection()->getPdo(),
            $segundaLigacao->getPdo(),
        );

        try {
            $identificadores = [];
            $tentativasInsercao = 0;

            DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');

            $respostas = DB::transaction(function () use (
                $comentario,
                $autor,
                $segundaLigacao,
                $servico,
                &$identificadores,
                &$tentativasInsercao,
            ): array {
                $tentativasInsercao++;
                $primeiraTentativa = $tentativasInsercao === 1;

                self::assertSame($primeiraTentativa ? 0 : 2, $comentario->respostas()->count());

                // As inserções concorrentes são efetuadas apenas uma vez.
                if ($primeiraTentativa) {
                    for ($indice = 1; $indice <= 2; $indice++) {
                        $identificadores[] = $segundaLigacao
                            ->table('comentarios')
                            ->insertGetId([
                                'utilizador_id' => $autor->id,
                                'tipo_comentavel' => $comentario->tipo_comentavel,
                                'comentavel_id' => $comentario->comentavel_id,
                                'comentario_pai_id' => $comentario->id,
                                'conteudo' => 'Resposta '.$indice,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }

                $bloqueado = Comentario::query()
                    ->whereKey($comentario->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                self::assertSame($primeiraTentativa ? 0 : 2, $bloqueado->respostas()->count());

                $resultado = $servico->eliminarComentarioBloqueado($bloqueado);

                self::assertSame('marcador', $resultado['modo']);

                return $identificadores;
            }, 3);

            self::assertCount(2, $respostas);

            $this->assertNotSoftDeleted($comentario);
            self::assertTrue($comentario->refresh()->temConteudoEliminado());
            self::assertSame(2, $comentario->respostas()->count());

            $tentativasEliminacao = 0;

            DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');

            DB::transaction(function () use (
                $comentario,
                $respostas,
                $segundaLigacao,
                $servico,
                &$tentativasEliminacao,
            ): void {
                $tentativasEliminacao++;
                $primeiraTentativa = $tentativasEliminacao === 1;

                self::assertSame($primeiraTentativa ? 2 : 1, $comentario->respostas()->count());

                // A eliminação concorrente não deve ser repetida.
                if ($primeiraTentativa) {
                    self::assertSame(
                        1,
                        $segundaLigacao->table('comentarios')
                            ->where('id', $respostas[0])
                            ->update(['deleted_at' => now()]),
                    );
                }

                $ultimaResposta = Comentario::query()
                    ->whereKey($respostas[1])
                    ->lockForUpdate()
                    ->firstOrFail();

                $resultado = $servico->eliminarComentarioBloqueado($ultimaResposta);

                self::assertSame('remover', $resultado['modo']);
                self::assertContains(
                    $comentario->id,
                    $resultado['comentarios_removidos_ids'],
                );
            }, 3);

            $this->assertSoftDeleted($comentario);

            foreach ($respostas as $identificador) {
                $this->assertSoftDeleted('comentarios', [
                    'id' => $identificador,
                ]);
            }
        } finally {
            DB::purge('concorrencia_denuncias');
        }
    }
}
