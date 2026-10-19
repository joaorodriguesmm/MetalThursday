<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Contracts\Notifications\Dispatcher as DespachanteNotificacoes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class NotificacoesInteracoesTransacionaisTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Indica se existe uma tabela jobs temporária nesta ligação de testes.
     */
    private bool $filaTemporariaCriada = false;

    /**
     * Cria uma tabela jobs isolada, sem executar DDL permanente nem
     * confirmar implicitamente a transação aberta pelo RefreshDatabase.
     */
    private function prepararFilaTemporaria(): void
    {
        if (
            ! app()->environment('testing')
            || DB::getDefaultConnection() !== 'mariadb'
            || DB::connection()->getDatabaseName() !== 'metalthursday_testes'
        ) {
            throw new RuntimeException(
                'A fila temporária só pode ser criada na base de dados MariaDB de testes.',
            );
        }

        DB::statement(<<<'SQL'
CREATE TEMPORARY TABLE `jobs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `queue` varchar(255) NOT NULL,
    `payload` longtext NOT NULL,
    `attempts` tinyint unsigned NOT NULL,
    `reserved_at` int unsigned DEFAULT NULL,
    `available_at` int unsigned NOT NULL,
    `created_at` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB
SQL);

        $this->filaTemporariaCriada = true;

        config()->set(
            'queue.connections.database.connection',
            DB::getDefaultConnection(),
        );

        config()->set('queue.connections.database.table', 'jobs');
    }

    /**
     * A tabela temporária não é removida pelo rollback do RefreshDatabase.
     */
    protected function tearDown(): void
    {
        try {
            if ($this->filaTemporariaCriada) {
                DB::statement('DROP TEMPORARY TABLE IF EXISTS `jobs`');
            }
        } finally {
            parent::tearDown();
        }
    }

    private function simularFalhaNoDespacho(): void
    {
        config()->set(
            'queue.connections.database.connection',
            DB::getDefaultConnection(),
        );

        $despachante = Mockery::mock(DespachanteNotificacoes::class);

        $despachante
            ->shouldReceive('send')
            ->once()
            ->andThrow(
                new RuntimeException('Falha simulada B24-25.'),
            );

        $this->app->instance(
            DespachanteNotificacoes::class,
            $despachante,
        );
    }

    #[Test]
    public function falha_no_despacho_reverte_adicao_de_gosto(): void
    {
        $autor = Utilizador::factory()->create();
        $utilizador = Utilizador::factory()->create();
        $alvo = MetalThursday::factory()->create();

        $comentario = $alvo->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário para testar gostos.',
            'comentario_pai_id' => null,
        ]);

        $this->simularFalhaNoDespacho();

        $this->actingAs($utilizador, 'sessao')
            ->postJson(route('gostos.alternar', $comentario))
            ->assertInternalServerError();

        $this->assertDatabaseMissing('gostos', [
            'comentario_id' => $comentario->id,
            'utilizador_id' => $utilizador->id,
        ]);
    }

    #[Test]
    public function falha_no_despacho_reverte_nova_avaliacao(): void
    {
        Utilizador::factory()->create();

        $utilizador = Utilizador::factory()->create();
        $alvo = MetalThursday::factory()->create();

        $this->simularFalhaNoDespacho();

        $this->actingAs($utilizador, 'sessao')
            ->postJson(
                route('avaliacoes.guardar', [
                    'tipoAvaliavel' => TipoEntidadeInteracao::MetalThursday->value,
                    'identificadorAvaliavel' => $alvo->id,
                ]),
                ['pontuacao' => '8,5'],
            )
            ->assertInternalServerError();

        $this->assertDatabaseMissing('avaliacoes', [
            'utilizador_id' => $utilizador->id,
            'tipo_avaliavel' => $alvo->getMorphClass(),
            'avaliavel_id' => $alvo->id,
        ]);
    }

    #[Test]
    public function falha_no_despacho_reverte_nova_audicao(): void
    {
        Utilizador::factory()->create();

        $utilizador = Utilizador::factory()->create();
        $alvo = MetalThursday::factory()->create();

        $this->simularFalhaNoDespacho();

        $this->actingAs($utilizador, 'sessao')
            ->postJson(
                route('audicoes.alternar', [
                    'tipoAudivel' => TipoEntidadeInteracao::MetalThursday->value,
                    'identificadorAudivel' => $alvo->id,
                ]),
            )
            ->assertInternalServerError();

        $this->assertDatabaseMissing('audicoes', [
            'utilizador_id' => $utilizador->id,
            'tipo_audivel' => $alvo->getMorphClass(),
            'audivel_id' => $alvo->id,
        ]);
    }

    /**
     * Se a notificação falhar ao alterar uma avaliação existente,
     * a pontuação anterior deve permanecer intacta.
     */
    #[Test]
    public function falha_no_despacho_preserva_pontuacao_anterior(): void
    {
        Utilizador::factory()->create();
        $utilizador = Utilizador::factory()->create();
        $alvo = MetalThursday::factory()->create();

        $alvo->avaliacoes()->create([
            'utilizador_id' => $utilizador->id,
            'pontuacao' => 4.0,
        ]);

        $this->simularFalhaNoDespacho();

        $this->actingAs($utilizador, 'sessao')
            ->postJson(
                route('avaliacoes.guardar', [
                    'tipoAvaliavel' => TipoEntidadeInteracao::MetalThursday->value,
                    'identificadorAvaliavel' => $alvo->id,
                ]),
                ['pontuacao' => '8,5'],
            )
            ->assertInternalServerError();

        $this->assertDatabaseHas('avaliacoes', [
            'utilizador_id' => $utilizador->id,
            'tipo_avaliavel' => $alvo->getMorphClass(),
            'avaliavel_id' => $alvo->id,
            'pontuacao' => 4.0,
        ]);

        $this->assertDatabaseCount('avaliacoes', 1);
    }

    #[Test]
    public function gosto_adicionado_enfileira_e_remocao_nao_enfileira(): void
    {
        $this->prepararFilaTemporaria();

        $autor = Utilizador::factory()->create();
        $utilizador = Utilizador::factory()->create();
        $alvo = MetalThursday::factory()->create();
        $comentario = $alvo->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário para verificar a fila de gostos.',
            'comentario_pai_id' => null,
        ]);

        $url = route('gostos.alternar', $comentario);
        // O notificador cria um trabalho por destinatário elegível.
        $numeroDestinatarios = Utilizador::query()
            ->comAcessoAtivo()
            ->whereKeyNot($utilizador->getKey())
            ->count();

        self::assertGreaterThan(0, $numeroDestinatarios);
        $antes = DB::table('jobs')->count();

        $this->actingAs($utilizador, 'sessao')
            ->postJson($url)
            ->assertOk()
            ->assertJsonPath('adicionado', true);

        $depoisDaAdicao = DB::table('jobs')->count();
        self::assertSame($antes + $numeroDestinatarios, $depoisDaAdicao);

        $this->postJson($url)
            ->assertOk()
            ->assertJsonPath('adicionado', false);

        self::assertSame($depoisDaAdicao, DB::table('jobs')->count());
    }

    #[Test]
    public function avaliacao_enfileira_apenas_quando_pontuacao_muda(): void
    {
        $this->prepararFilaTemporaria();

        Utilizador::factory()->create();
        $utilizador = Utilizador::factory()->create();
        $alvo = MetalThursday::factory()->create();
        $url = route('avaliacoes.guardar', [
            'tipoAvaliavel' => TipoEntidadeInteracao::MetalThursday->value,
            'identificadorAvaliavel' => $alvo->id,
        ]);
        // O notificador cria um trabalho por destinatário elegível.
        $numeroDestinatarios = Utilizador::query()
            ->comAcessoAtivo()
            ->whereKeyNot($utilizador->getKey())
            ->count();

        self::assertGreaterThan(0, $numeroDestinatarios);
        $antes = DB::table('jobs')->count();

        $this->actingAs($utilizador, 'sessao')
            ->postJson($url, ['pontuacao' => '8,5'])
            ->assertOk();

        $depoisDaCriacao = DB::table('jobs')->count();
        self::assertSame($antes + $numeroDestinatarios, $depoisDaCriacao);

        $this->postJson($url, ['pontuacao' => '8,5'])->assertOk();
        self::assertSame($depoisDaCriacao, DB::table('jobs')->count());

        $this->postJson($url, ['pontuacao' => '9,0'])->assertOk();
        self::assertSame($depoisDaCriacao + $numeroDestinatarios, DB::table('jobs')->count());
    }

    #[Test]
    public function audicao_adicionada_enfileira_e_remocao_nao_enfileira(): void
    {
        $this->prepararFilaTemporaria();

        Utilizador::factory()->create();
        $utilizador = Utilizador::factory()->create();
        $alvo = MetalThursday::factory()->create();
        $url = route('audicoes.alternar', [
            'tipoAudivel' => TipoEntidadeInteracao::MetalThursday->value,
            'identificadorAudivel' => $alvo->id,
        ]);
        // O notificador cria um trabalho por destinatário elegível.
        $numeroDestinatarios = Utilizador::query()
            ->comAcessoAtivo()
            ->whereKeyNot($utilizador->getKey())
            ->count();

        self::assertGreaterThan(0, $numeroDestinatarios);
        $antes = DB::table('jobs')->count();

        $this->actingAs($utilizador, 'sessao')
            ->postJson($url)
            ->assertOk()
            ->assertJsonPath('marcado_como_ouvido', true);

        $depoisDaAdicao = DB::table('jobs')->count();
        self::assertSame($antes + $numeroDestinatarios, $depoisDaAdicao);

        $this->postJson($url)
            ->assertOk()
            ->assertJsonPath('marcado_como_ouvido', false);

        self::assertSame($depoisDaAdicao, DB::table('jobs')->count());
    }
}
