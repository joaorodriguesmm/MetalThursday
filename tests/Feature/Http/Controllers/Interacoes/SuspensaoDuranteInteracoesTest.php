<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Impede interações de um utilizador suspenso durante o pedido HTTP.
 *
 * @since 2.0.0
 */
final class SuspensaoDuranteInteracoesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function cenarios(): array
    {
        return [
            'adicionar gosto' => ['gosto_adicionar'],
            'remover gosto' => ['gosto_remover'],
            'criar avaliacao' => ['avaliacao_criar'],
            'alterar avaliacao' => ['avaliacao_atualizar'],
            'limpar avaliacao' => ['avaliacao_eliminar'],
            'adicionar audicao' => ['audicao_adicionar'],
            'remover audicao' => ['audicao_remover'],
        ];
    }

    /**
     * @since 2.0.0
     */
    #[Test]
    #[DataProvider('cenarios')]
    public function utilizador_suspenso_durante_pedido_nao_altera_interacao(
        string $cenario,
    ): void {
        Notification::fake();

        $autor = Utilizador::factory()->create();
        $utilizador = Utilizador::factory()->create();
        $responsavel = Utilizador::factory()->create();
        $metalThursday = MetalThursday::factory()->create();
        $comentario = $metalThursday->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário original.',
            'comentario_pai_id' => null,
        ]);

        $tipo = str_starts_with($cenario, 'gosto_')
            ? 'gosto'
            : (str_starts_with($cenario, 'avaliacao_') ? 'avaliacao' : 'audicao');

        $existia = in_array($cenario, [
            'gosto_remover',
            'avaliacao_atualizar',
            'avaliacao_eliminar',
            'audicao_remover',
        ], true);

        if ($existia) {
            if ($tipo === 'gosto') {
                $comentario->gostos()->create([
                    'utilizador_id' => $utilizador->id,
                ]);
            } elseif ($tipo === 'avaliacao') {
                $metalThursday->avaliacoes()->create([
                    'utilizador_id' => $utilizador->id,
                    'pontuacao' => 8.0,
                ]);
            } else {
                $metalThursday->audicoes()->create([
                    'utilizador_id' => $utilizador->id,
                ]);
            }
        }

        $this->actingAs($utilizador, 'sessao');

        $suspenso = false;
        $tabelaBloqueada = $tipo === 'gosto'
            ? 'comentarios'
            : 'metal_thursdays';
        $identificadorUtilizador = $utilizador->id;
        $identificadorResponsavel = $responsavel->id;

        DB::listen(
            static function (QueryExecuted $consulta) use (
                &$suspenso,
                $tabelaBloqueada,
                $identificadorUtilizador,
                $identificadorResponsavel,
            ): void {
                $sql = mb_strtolower($consulta->sql);

                // O RefreshDatabase já possui uma transação exterior.
                // A segunda transação pertence à operação HTTP.
                if (
                    $suspenso
                    || DB::transactionLevel() < 2
                    || ! str_starts_with(ltrim($sql), 'select')
                    || ! str_contains($sql, $tabelaBloqueada)
                ) {
                    return;
                }

                $afetados = DB::table('utilizadores')
                    ->where('id', $identificadorUtilizador)
                    ->update([
                        'suspenso_em' => now(),
                        'motivo_suspensao' => 'Suspensão durante interação de teste.',
                        'suspenso_por_id' => $identificadorResponsavel,
                    ]);

                $suspenso = $afetados === 1;
            },
        );

        $parametros = [
            'tipoAvaliavel' => TipoEntidadeInteracao::MetalThursday->value,
            'identificadorAvaliavel' => $metalThursday->getKey(),
        ];

        $resposta = match ($tipo) {
            'gosto' => $this->postJson(
                route('gostos.alternar', $comentario),
            ),
            'avaliacao' => $cenario === 'avaliacao_eliminar'
                ? $this->deleteJson(route('avaliacoes.eliminar', $parametros))
                : $this->postJson(
                    route('avaliacoes.guardar', $parametros),
                    ['pontuacao' => '9,0'],
                ),
            default => $this->postJson(
                route('audicoes.alternar', [
                    'tipoAudivel' => TipoEntidadeInteracao::MetalThursday->value,
                    'identificadorAudivel' => $metalThursday->getKey(),
                ]),
            ),
        };

        $resposta->assertForbidden();
        self::assertTrue($suspenso, 'A suspensão deve ocorrer durante a transação.');

        $tabela = match ($tipo) {
            'gosto' => 'gostos',
            'avaliacao' => 'avaliacoes',
            default => 'audicoes',
        };
        $registo = match ($tipo) {
            'gosto' => [
                'comentario_id' => $comentario->getKey(),
                'utilizador_id' => $identificadorUtilizador,
            ],
            'avaliacao' => [
                'tipo_avaliavel' => $metalThursday->getMorphClass(),
                'avaliavel_id' => $metalThursday->getKey(),
                'utilizador_id' => $identificadorUtilizador,
                'pontuacao' => $existia ? 8.0 : 9.0,
            ],
            default => [
                'tipo_audivel' => $metalThursday->getMorphClass(),
                'audivel_id' => $metalThursday->getKey(),
                'utilizador_id' => $identificadorUtilizador,
            ],
        };

        if ($existia) {
            $this->assertDatabaseHas($tabela, $registo);
        } else {
            $this->assertDatabaseMissing($tabela, $registo);
        }

        Notification::assertNothingSent();
    }
}
