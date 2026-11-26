<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\PapelUtilizador;
use App\Http\Controllers\Interacoes\ControladorDenunciaComentario;
use App\Http\Controllers\Interacoes\ControladorModeracaoDenuncias;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\Interacoes\DenunciaComentario;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoDecisaoDenunciaComentario;
use App\Notifications\NotificacaoNovaDenunciaComentario;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class NotificacoesDenunciasTransacionaisTest extends TestCase
{
    use RefreshDatabase;

    private bool $filaTemporariaCriada = false;

    private function prepararFilaTemporaria(): void
    {
        if (
            ! app()->environment('testing')
            || DB::getDefaultConnection() !== 'mariadb'
            || DB::connection()->getDatabaseName() !== 'metalthursday_testes'
        ) {
            throw new RuntimeException(
                'A fila temporária exige a base de dados MariaDB de testes.',
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

        config([
            'queue.connections.database.connection' => DB::getDefaultConnection(),
            'queue.connections.database.table' => 'jobs',
        ]);
    }

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

    /**
     * @return array<string, array{string, string}>
     */
    public static function decisoes(): array
    {
        return [
            'aceitar' => ['aceitar', 'aceite'],
            'rejeitar' => ['rejeitar', 'rejeitada'],
        ];
    }

    #[Test]
    #[DataProvider('decisoes')]
    public function denuncia_e_decisao_enfileiram_sem_duplicacao(
        string $decisao,
        string $estadoEsperado,
    ): void {
        $this->prepararFilaTemporaria();

        $autor = Utilizador::factory()->create([
            'papel' => PapelUtilizador::Utilizador,
        ]);

        $denunciante = Utilizador::factory()->create([
            'papel' => PapelUtilizador::Utilizador,
        ]);

        $moderador = Utilizador::factory()->create([
            'papel' => PapelUtilizador::SuperAdministrador,
        ]);

        $publicacao = MetalThursday::factory()->comAutor($autor)->create();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário para testar notificações transacionais.',
            'comentario_pai_id' => null,
        ]);

        $urlDenuncia = action(
            [ControladorDenunciaComentario::class, 'guardar'],
            ['comentario' => $comentario->id],
        );

        $this->assertDatabaseCount('jobs', 0);

        $this->actingAs($denunciante, 'sessao')
            ->postJson($urlDenuncia, ['motivo' => 'spam'])
            ->assertCreated()
            ->assertJsonPath('nova', true);

        $this->assertDatabaseCount('denuncias_comentarios', 1);
        $this->assertDatabaseCount('jobs', 1);

        self::assertSame(
            1,
            DB::table('jobs')
                ->where('payload', 'like', '%NotificacaoNovaDenunciaComentario%')
                ->count(),
        );

        $this->postJson($urlDenuncia, ['motivo' => 'spam'])
            ->assertOk()
            ->assertJsonPath('nova', false);

        $this->assertDatabaseCount('denuncias_comentarios', 1);
        $this->assertDatabaseCount('jobs', 1);

        $denuncia = DenunciaComentario::query()
            ->where('comentario_id', $comentario->id)
            ->where('denunciante_id', $denunciante->id)
            ->firstOrFail();

        $urlDecisao = action(
            [ControladorModeracaoDenuncias::class, 'decidir'],
            ['denuncia' => $denuncia->id],
        );

        $this->actingAs($moderador, 'sessao')
            ->patchJson($urlDecisao, ['decisao' => $decisao])
            ->assertOk();

        $this->assertDatabaseHas('denuncias_comentarios', [
            'id' => $denuncia->id,
            'estado' => $estadoEsperado,
            'moderador_id' => $moderador->id,
        ]);

        $this->assertDatabaseCount('jobs', 2);

        self::assertSame(
            1,
            DB::table('jobs')
                ->where('payload', 'like', '%NotificacaoDecisaoDenunciaComentario%')
                ->count(),
        );

        if ($decisao === 'aceitar') {
            $this->assertSoftDeleted($comentario);
        } else {
            $this->assertNotSoftDeleted($comentario);
            $this->assertDatabaseHas('comentarios', [
                'id' => $comentario->id,
                'conteudo_eliminado_em' => null,
            ]);
        }

        $this->patchJson($urlDecisao, ['decisao' => $decisao])
            ->assertConflict();

        $this->assertDatabaseCount('jobs', 2);
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function operacoesComFalha(): array
    {
        return [
            'nova denuncia' => [false],
            'decisao de moderacao' => [true],
        ];
    }

    #[Test]
    #[DataProvider('operacoesComFalha')]
    public function falha_apos_enfileiramento_reverte_operacao(
        bool $decidir,
    ): void {
        $this->prepararFilaTemporaria();

        $autor = Utilizador::factory()->create([
            'papel' => PapelUtilizador::Utilizador,
        ]);

        $denunciante = Utilizador::factory()->create([
            'papel' => PapelUtilizador::Utilizador,
        ]);

        $moderador = Utilizador::factory()->create([
            'papel' => PapelUtilizador::SuperAdministrador,
        ]);

        $publicacao = MetalThursday::factory()->comAutor($autor)->create();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário para testar atomicidade das denúncias.',
            'comentario_pai_id' => null,
        ]);

        $trabalhosAntesDaFalha = null;

        Event::listen(
            JobQueued::class,
            static function () use (&$trabalhosAntesDaFalha): never {
                $trabalhosAntesDaFalha = DB::table('jobs')->count();

                throw new RuntimeException(
                    'Falha simulada após enfileiramento da notificação.',
                );
            },
        );

        if ($decidir) {
            $denuncia = DenunciaComentario::query()->create([
                'comentario_id' => $comentario->id,
                'denunciante_id' => $denunciante->id,
                'motivo' => 'spam',
            ]);

            $this->actingAs($moderador, 'sessao')
                ->patchJson(
                    action(
                        [ControladorModeracaoDenuncias::class, 'decidir'],
                        ['denuncia' => $denuncia->id],
                    ),
                    ['decisao' => 'aceitar'],
                )
                ->assertInternalServerError();

            $this->assertDatabaseHas('denuncias_comentarios', [
                'id' => $denuncia->id,
                'estado' => 'pendente',
                'moderador_id' => null,
                'decidida_em' => null,
            ]);

            $this->assertDatabaseCount('denuncias_comentarios', 1);
        } else {
            $this->actingAs($denunciante, 'sessao')
                ->postJson(
                    action(
                        [ControladorDenunciaComentario::class, 'guardar'],
                        ['comentario' => $comentario->id],
                    ),
                    ['motivo' => 'spam'],
                )
                ->assertInternalServerError();

            $this->assertDatabaseCount('denuncias_comentarios', 0);
        }

        $this->assertNotSoftDeleted($comentario);
        $this->assertDatabaseHas('comentarios', [
            'id' => $comentario->id,
            'conteudo_eliminado_em' => null,
        ]);

        self::assertSame(1, $trabalhosAntesDaFalha);

        $this->assertDatabaseCount('jobs', 0);
    }

    /**
     * @return array<string, array{bool, bool}>
     */
    public static function preferenciasNaEntrega(): array
    {
        return [
            'entrega imediata com permissoes mantidas' => [false, false],
            'entrega imediata com permissao revogada' => [true, false],
            'entrega tardia com permissoes mantidas' => [false, true],
            'entrega tardia com permissao revogada' => [true, true],
        ];
    }

    #[Test]
    #[DataProvider('preferenciasNaEntrega')]
    public function worker_entrega_notificacoes_de_denuncias(
        bool $revogarEmail,
        bool $decidirAntesDaEntrega,
    ): void {
        $this->prepararFilaTemporaria();

        config([
            'mail.default' => 'denuncias_teste',
            'mail.mailers.denuncias_teste.transport' => 'array',
            'mail.from.address' => 'teste@example.com',
            'mail.from.name' => 'MetalThursday',
        ]);

        app(PermissaoEmailSeeder::class)->run();

        $autor = Utilizador::factory()->create([
            'papel' => PapelUtilizador::Utilizador,
        ]);

        $denunciante = Utilizador::factory()->create([
            'papel' => PapelUtilizador::Utilizador,
        ]);

        $moderador = Utilizador::factory()->create([
            'papel' => PapelUtilizador::SuperAdministrador,
        ]);

        $permissaoNova = PermissaoEmail::query()
            ->where('identificador', 'novas_denuncias_comentarios')
            ->sole();

        $permissaoDecisao = PermissaoEmail::query()
            ->where('identificador', 'decisoes_denuncias_comentarios')
            ->sole();

        $moderador->permissoesEmail()->attach($permissaoNova->id);
        $denunciante->permissoesEmail()->attach($permissaoDecisao->id);

        $publicacao = MetalThursday::factory()->comAutor($autor)->create();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário para testar a entrega pelo worker.',
            'comentario_pai_id' => null,
        ]);

        $this->actingAs($denunciante, 'sessao')
            ->postJson(
                route('comentarios.denuncias.guardar', $comentario),
                ['motivo' => 'spam'],
            )
            ->assertCreated();

        $denuncia = DenunciaComentario::query()
            ->where('comentario_id', $comentario->id)
            ->where('denunciante_id', $denunciante->id)
            ->sole();

        $entregas = [];
        $emailsEnviados = 0;

        Event::listen(
            NotificationSent::class,
            static function ($evento) use (&$entregas): void {
                if ($evento->notifiable instanceof Utilizador) {
                    $entregas[] = $evento->notifiable->id.':'.$evento->channel;
                }
            },
        );

        Event::listen(
            MessageSent::class,
            static function () use (&$emailsEnviados): void {
                $emailsEnviados++;
            },
        );

        $fila = config('queue.connections.database.queue');

        self::assertIsString($fila);

        $worker = app('queue.worker');
        self::assertInstanceOf(Worker::class, $worker);

        $processar = static function (int $total) use ($worker, $fila): void {
            for ($indice = 0; $indice < $total; $indice++) {
                $worker->runNextJob(
                    'database',
                    $fila,
                    new WorkerOptions(
                        sleep: 0,
                        maxTries: 1,
                    ),
                );
            }
        };

        $this->assertDatabaseCount('jobs', 2);

        if (! $decidirAntesDaEntrega) {
            $processar(2);

            $this->assertDatabaseCount('jobs', 0);

            self::assertContains($moderador->id.':database', $entregas);
            self::assertContains($moderador->id.':mail', $entregas);
        }

        $this->actingAs($moderador, 'sessao')
            ->patchJson(
                action(
                    [ControladorModeracaoDenuncias::class, 'decidir'],
                    ['denuncia' => $denuncia->id],
                ),
                ['decisao' => 'aceitar'],
            )
            ->assertOk();

        $this->assertDatabaseCount('jobs', $decidirAntesDaEntrega ? 4 : 2);

        if ($revogarEmail) {
            $denunciante->permissoesEmail()
                ->detach($permissaoDecisao->id);
        }

        $processar($decidirAntesDaEntrega ? 4 : 2);

        $this->assertDatabaseCount('jobs', 0);

        self::assertSame(
            $decidirAntesDaEntrega ? 0 : 1,
            $moderador->notifications()
                ->where('type', NotificacaoNovaDenunciaComentario::class)
                ->count(),
        );

        self::assertSame(
            1,
            $denunciante->notifications()
                ->where('type', NotificacaoDecisaoDenunciaComentario::class)
                ->count(),
        );

        self::assertSame(0, $autor->notifications()->count());

        $notificacaoDecisao = $denunciante->notifications()
            ->where('type', NotificacaoDecisaoDenunciaComentario::class)
            ->sole();

        self::assertSame(
            route('metal-thursday.detalhes', ['metalThursday' => $publicacao->id]),
            $notificacaoDecisao->data['ligacao'],
        );

        if ($decidirAntesDaEntrega) {
            self::assertNotContains($moderador->id.':database', $entregas);
            self::assertNotContains($moderador->id.':mail', $entregas);
        } else {
            self::assertContains($moderador->id.':database', $entregas);
            self::assertContains($moderador->id.':mail', $entregas);
        }

        self::assertContains($denunciante->id.':database', $entregas);

        if ($revogarEmail) {
            self::assertNotContains($denunciante->id.':mail', $entregas);
        } else {
            self::assertContains($denunciante->id.':mail', $entregas);
        }

        $emailsEsperados = ($decidirAntesDaEntrega ? 0 : 1)
            + ($revogarEmail ? 0 : 1);

        $entregasEsperadas = ($decidirAntesDaEntrega ? 1 : 2)
            + $emailsEsperados;

        self::assertCount($entregasEsperadas, $entregas);
        self::assertSame($emailsEsperados, $emailsEnviados);
    }
}
