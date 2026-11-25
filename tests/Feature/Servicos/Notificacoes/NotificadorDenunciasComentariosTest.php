<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Notificacoes;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\DenunciaComentario;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoDecisaoDenunciaComentario;
use App\Notifications\NotificacaoNovaDenunciaComentario;
use App\Servicos\Interacoes\ServicoDisponibilidadeInteracoes;
use App\Servicos\Notificacoes\NotificadorDenunciasComentarios;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NotificadorDenunciasComentariosTest extends TestCase
{
    use RefreshDatabase;

    private NotificationFake $notificacoes;

    private NotificadorDenunciasComentarios $notificador;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'queue.connections.database.connection' => DB::getDefaultConnection(),
        ]);

        $this->notificacoes = new NotificationFake;

        $this->notificador = new NotificadorDenunciasComentarios(
            $this->notificacoes,
            app(ServicoDisponibilidadeInteracoes::class),
        );
    }

    #[Test]
    public function nova_denuncia_notifica_apenas_superadministradores_ativos(): void
    {
        $denuncia = $this->criarDenuncia();

        $superadministrador = Utilizador::factory()->create([
            'papel' => PapelUtilizador::SuperAdministrador,
        ]);

        $outroSuperadministrador = Utilizador::factory()->create([
            'papel' => PapelUtilizador::SuperAdministrador,
        ]);

        $suspenso = Utilizador::factory()->create([
            'papel' => PapelUtilizador::SuperAdministrador,
            'suspenso_em' => now(),
            'motivo_suspensao' => 'Suspensão para teste.',
            'suspenso_por_id' => $superadministrador->id,
        ]);

        $administrador = Utilizador::factory()->create([
            'papel' => PapelUtilizador::Administrador,
        ]);

        $denunciante = $denuncia->denunciante()->firstOrFail();

        $this->notificador->notificarNovaDenuncia($denuncia);

        $this->notificacoes->assertSentTo(
            $superadministrador,
            NotificacaoNovaDenunciaComentario::class,
            static fn (NotificacaoNovaDenunciaComentario $notificacao): bool => $notificacao->connection === 'database'
                && $notificacao->afterCommit === false,
        );

        $this->notificacoes->assertSentTo(
            $outroSuperadministrador,
            NotificacaoNovaDenunciaComentario::class,
        );

        $this->notificacoes->assertNotSentTo(
            $suspenso,
            NotificacaoNovaDenunciaComentario::class,
        );

        $this->notificacoes->assertNotSentTo(
            $administrador,
            NotificacaoNovaDenunciaComentario::class,
        );

        $this->notificacoes->assertNotSentTo(
            $denunciante,
            NotificacaoNovaDenunciaComentario::class,
        );

        $this->notificacoes->assertCount(2);
    }

    #[Test]
    public function decisao_notifica_exclusivamente_o_denunciante(): void
    {
        $denuncia = $this->criarDenuncia();
        $denunciante = $denuncia->denunciante()->firstOrFail();

        $denuncia->estado = 'aceite';
        $denuncia->saveOrFail();

        $this->notificador->notificarDecisao($denuncia);

        $this->notificacoes->assertSentTo(
            $denunciante,
            NotificacaoDecisaoDenunciaComentario::class,
            static fn (NotificacaoDecisaoDenunciaComentario $notificacao): bool => $notificacao->connection === 'database'
                && $notificacao->afterCommit === false
                && $notificacao->toArray($denunciante)['estado'] === 'aceite',
        );

        $this->notificacoes->assertCount(1);
    }

    #[Test]
    public function denunciante_suspenso_nao_recebe_nova_notificacao_de_decisao(): void
    {
        $denuncia = $this->criarDenuncia();

        $moderador = Utilizador::factory()->create(['papel' => PapelUtilizador::SuperAdministrador]);

        Utilizador::query()
            ->whereKey($denuncia->denunciante_id)
            ->update(['suspenso_em' => now(), 'motivo_suspensao' => 'Suspensão para teste.', 'suspenso_por_id' => $moderador->id]);

        $denuncia->estado = 'rejeitada';
        $denuncia->saveOrFail();

        $this->notificador->notificarDecisao($denuncia);

        $this->notificacoes->assertNothingSent();
    }

    #[Test]
    public function rejeita_fila_numa_ligacao_diferente_da_transacao(): void
    {
        $denuncia = $this->criarDenuncia();

        config([
            'queue.connections.database.connection' => 'ligacao_invalida',
        ]);

        $this->expectException(LogicException::class);

        $this->notificador->notificarNovaDenuncia($denuncia);
    }

    #[Test]
    public function decisao_sobre_comentario_eliminado_liga_a_publicacao(): void
    {
        $denuncia = $this->criarDenuncia();
        $denunciante = $denuncia->denunciante()->firstOrFail();
        $comentario = $denuncia->comentario()->firstOrFail();

        $urlEsperado = route('metal-thursday.detalhes', [
            'metalThursday' => $comentario->comentavel_id,
        ]);

        $comentario->delete();

        $this->assertSoftDeleted($comentario);
        $this->assertDatabaseHas('denuncias_comentarios', [
            'id' => $denuncia->id,
        ]);

        $denuncia->estado = 'aceite';
        $denuncia->saveOrFail();

        $this->notificador->notificarDecisao($denuncia);

        $this->notificacoes->assertSentTo(
            $denunciante,
            NotificacaoDecisaoDenunciaComentario::class,
            static fn (NotificacaoDecisaoDenunciaComentario $notificacao): bool => $notificacao->toArray($denunciante)['ligacao'] === $urlEsperado
                && $notificacao->toMail($denunciante)->actionText === 'Ver publicação',
        );

        $this->notificacoes->assertCount(1);
    }

    private function criarDenuncia(): DenunciaComentario
    {
        $autor = Utilizador::factory()->create([
            'papel' => PapelUtilizador::Utilizador,
        ]);

        $denunciante = Utilizador::factory()->create([
            'papel' => PapelUtilizador::Utilizador,
        ]);

        $publicacao = MetalThursday::factory()->comAutor($autor)->create();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário para testar notificações de denúncias.',
            'comentario_pai_id' => null,
        ]);

        return DenunciaComentario::query()->create([
            'comentario_id' => $comentario->id,
            'denunciante_id' => $denunciante->id,
            'motivo' => 'spam',
            'detalhes' => 'Detalhes da denúncia.',
        ]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function contextosDaDecisao(): array
    {
        return [
            'comentario disponivel' => ['disponivel', 'Ver comentário'],
            'conteudo removido' => ['removido', 'Ver publicação'],
            'publicacao futura' => ['futura', 'Abrir MetalThursday'],
        ];
    }

    #[Test]
    #[DataProvider('contextosDaDecisao')]
    public function decisao_respeita_disponibilidade_do_contexto(
        string $contexto,
        string $textoEsperado,
    ): void {
        $denuncia = $this->criarDenuncia();
        $denunciante = $denuncia->denunciante()->firstOrFail();
        $comentario = $denuncia->comentario()->firstOrFail();

        $publicacao = MetalThursday::query()
            ->findOrFail($comentario->comentavel_id);

        $urlEsperado = match ($contexto) {
            'disponivel' => route('metal-thursday.detalhes', [
                'metalThursday' => $publicacao->id,
                'comentario' => $comentario->id,
            ]),
            'removido' => route('metal-thursday.detalhes', [
                'metalThursday' => $publicacao->id,
            ]),
            'futura' => route('inicio'),
            default => throw new LogicException('Contexto inválido.'),
        };

        if ($contexto === 'removido') {
            $comentario->conteudo_eliminado_em = now();
            $comentario->saveOrFail();
        }

        if ($contexto === 'futura') {
            $publicacao->updateOrFail([
                'data' => CarbonImmutable::today()->addDays(7),
            ]);
        }

        $denuncia->estado = 'rejeitada';
        $denuncia->saveOrFail();

        $this->notificador->notificarDecisao($denuncia);

        $this->notificacoes->assertSentTo(
            $denunciante,
            NotificacaoDecisaoDenunciaComentario::class,
            static function (NotificacaoDecisaoDenunciaComentario $notificacao) use (
                $denunciante,
                $urlEsperado,
                $textoEsperado,
            ): bool {
                $email = $notificacao->toMail($denunciante);

                return $notificacao->toArray($denunciante)['ligacao'] === $urlEsperado
                    && $email->actionUrl === $urlEsperado
                    && $email->actionText === $textoEsperado;
            },
        );

        $this->notificacoes->assertCount(1);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function alteracoesAposEnfileiramento(): array
    {
        return [
            'comentario eliminado' => ['eliminado'],
            'conteudo removido' => ['removido'],
            'publicacao futura' => ['futura'],
        ];
    }

    #[Test]
    #[DataProvider('alteracoesAposEnfileiramento')]
    public function decisao_revalida_contexto_antes_da_entrega(
        string $contexto,
    ): void {
        $denuncia = $this->criarDenuncia();
        $denunciante = $denuncia->denunciante()->firstOrFail();
        $comentario = $denuncia->comentario()->firstOrFail();

        $publicacao = MetalThursday::query()
            ->findOrFail($comentario->comentavel_id);

        $denuncia->estado = 'aceite';
        $denuncia->saveOrFail();

        $this->notificador->notificarDecisao($denuncia);

        if ($contexto === 'eliminado') {
            $comentario->delete();
        }

        if ($contexto === 'removido') {
            $comentario->conteudo_eliminado_em = now();
            $comentario->saveOrFail();
        }

        if ($contexto === 'futura') {
            $publicacao->updateOrFail([
                'data' => CarbonImmutable::today()->addDays(7),
            ]);
        }

        $urlEsperado = $contexto === 'futura'
            ? route('inicio')
            : route('metal-thursday.detalhes', [
                'metalThursday' => $publicacao->id,
            ]);

        $textoEsperado = $contexto === 'futura'
            ? 'Abrir MetalThursday'
            : 'Ver publicação';

        $this->notificacoes->assertSentTo(
            $denunciante,
            NotificacaoDecisaoDenunciaComentario::class,
            static function (NotificacaoDecisaoDenunciaComentario $notificacao) use (
                $denunciante,
                $urlEsperado,
                $textoEsperado,
            ): bool {
                self::assertTrue(
                    $notificacao->shouldSend($denunciante, 'database'),
                );

                self::assertSame(
                    $urlEsperado,
                    $notificacao->toArray($denunciante)['ligacao'],
                );

                $email = $notificacao->toMail($denunciante);

                self::assertSame($urlEsperado, $email->actionUrl);
                self::assertSame($textoEsperado, $email->actionText);

                return true;
            },
        );

        $this->notificacoes->assertCount(1);
    }
}
