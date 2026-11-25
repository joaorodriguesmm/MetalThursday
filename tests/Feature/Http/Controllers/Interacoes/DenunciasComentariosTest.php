<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\Interacoes\DenunciaComentario;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Valida o registo de denúncias e a autorização das decisões de moderação.
 */
final class DenunciasComentariosTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function utilizador_denuncia_comentario_publicado(): void
    {
        [$comentario, $autor, $denunciante] = $this->criarCenario();

        $this->actingAs($denunciante, 'sessao')
            ->postJson(route('comentarios.denuncias.guardar', $comentario), [
                'motivo' => 'spam',
            ])
            ->assertCreated()
            ->assertJsonPath('estado', 'pendente')
            ->assertJsonPath('nova', true);

        $this->assertDatabaseHas('denuncias_comentarios', [
            'comentario_id' => $comentario->id,
            'denunciante_id' => $denunciante->id,
            'motivo' => 'spam',
            'estado' => 'pendente',
        ]);
    }

    #[Test]
    public function denuncias_repetidas_sao_idempotentes(): void
    {
        [$comentario, $autor, $denunciante] = $this->criarCenario();
        $endereco = route('comentarios.denuncias.guardar', $comentario);

        $this->actingAs($denunciante, 'sessao')
            ->postJson($endereco, ['motivo' => 'spam'])
            ->assertCreated()
            ->assertJsonPath('nova', true);

        $this->postJson($endereco, ['motivo' => 'outro', 'detalhes' => 'Segunda tentativa.'])
            ->assertOk()
            ->assertJsonPath('nova', false);

        self::assertSame(1, DenunciaComentario::query()->count());
        $this->assertDatabaseHas('denuncias_comentarios', [
            'comentario_id' => $comentario->id,
            'motivo' => 'spam',
        ]);
    }

    #[Test]
    public function autor_nao_pode_denunciar_proprio_comentario(): void
    {
        [$comentario, $autor] = $this->criarCenario();

        $this->actingAs($autor, 'sessao')
            ->postJson(route('comentarios.denuncias.guardar', $comentario), [
                'motivo' => 'spam',
            ])
            ->assertForbidden();

        self::assertSame(0, DenunciaComentario::query()->count());
    }

    #[Test]
    public function rejeita_motivo_invalido_e_detalhes_em_falta(): void
    {
        [$comentario, $autor, $denunciante] = $this->criarCenario();
        $endereco = route('comentarios.denuncias.guardar', $comentario);

        $this->actingAs($denunciante, 'sessao')
            ->postJson($endereco, ['motivo' => 'inexistente'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('motivo');

        $this->postJson($endereco, ['motivo' => 'outro'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('detalhes');

        self::assertSame(0, DenunciaComentario::query()->count());
    }

    #[Test]
    public function nao_aceita_denuncia_de_publicacao_futura(): void
    {
        $autor = Utilizador::factory()->create();
        $denunciante = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()
            ->comAutor($autor)
            ->create();

        $publicacao->updateOrFail([
            'data' => CarbonImmutable::today()->addDays(7),
        ]);

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário indisponível.',
            'comentario_pai_id' => null,
        ]);

        $this->actingAs($denunciante, 'sessao')
            ->postJson(route('comentarios.denuncias.guardar', $comentario), [
                'motivo' => 'spam',
            ])
            ->assertNotFound();
    }

    #[Test]
    public function nao_aceita_denuncia_de_conteudo_removido(): void
    {
        [$comentario, $autor, $denunciante] = $this->criarCenario();
        $comentario->conteudo_eliminado_em = now();
        $comentario->saveOrFail();

        $this->actingAs($denunciante, 'sessao')
            ->postJson(route('comentarios.denuncias.guardar', $comentario), [
                'motivo' => 'spam',
            ])
            ->assertStatus(410);

        self::assertSame(0, DenunciaComentario::query()->count());
    }

    #[Test]
    public function utilizador_comum_nao_acede_a_fila_de_moderacao(): void
    {
        $utilizador = Utilizador::factory()->create();

        $this->actingAs($utilizador, 'sessao')
            ->getJson(route('moderacao.denuncias-comentarios.indice'))
            ->assertForbidden();
    }

    #[Test]
    public function superadministrador_consulta_denuncias_pendentes(): void
    {
        [$comentario, $autor, $denunciante] = $this->criarCenario();
        $moderador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        DenunciaComentario::query()->create([
            'comentario_id' => $comentario->id,
            'denunciante_id' => $denunciante->id,
            'motivo' => 'spam',
        ]);

        $this->actingAs($moderador, 'sessao')
            ->getJson(route('moderacao.denuncias-comentarios.indice'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.motivo', 'spam');
    }

    #[Test]
    public function decisao_de_moderacao_e_unica_e_auditavel(): void
    {
        [$comentario, $autor, $denunciante] = $this->criarCenario();
        $moderador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $denuncia = DenunciaComentario::query()->create([
            'comentario_id' => $comentario->id,
            'denunciante_id' => $denunciante->id,
            'motivo' => 'ofensivo',
        ]);

        $endereco = route('moderacao.denuncias-comentarios.decidir', $denuncia);

        $this->actingAs($moderador, 'sessao')
            ->patchJson($endereco, ['decisao' => 'rejeitar'])
            ->assertOk();

        $this->assertDatabaseHas('denuncias_comentarios', [
            'id' => $denuncia->id,
            'estado' => 'rejeitada',
            'moderador_id' => $moderador->id,
        ]);

        $this->patchJson($endereco, ['decisao' => 'aceitar'])
            ->assertStatus(409);
    }

    #[Test]
    public function utilizador_comum_nao_pode_decidir_denuncia(): void
    {
        [$comentario, $autor, $denunciante] = $this->criarCenario();
        $denuncia = DenunciaComentario::query()->create([
            'comentario_id' => $comentario->id,
            'denunciante_id' => $denunciante->id,
            'motivo' => 'spam',
        ]);

        $this->actingAs($denunciante, 'sessao')
            ->patchJson(route('moderacao.denuncias-comentarios.decidir', $denuncia), [
                'decisao' => 'rejeitar',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function privilegio_revogado_impede_decisao_de_moderacao(): void
    {
        [$comentario, $autor, $denunciante] = $this->criarCenario();
        $moderador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $denuncia = DenunciaComentario::query()->create([
            'comentario_id' => $comentario->id,
            'denunciante_id' => $denunciante->id,
            'motivo' => 'spam',
        ]);

        $this->actingAs($moderador, 'sessao');

        $moderador->papel = PapelUtilizador::Utilizador;
        $moderador->saveOrFail();

        $this->patchJson(
            route('moderacao.denuncias-comentarios.decidir', $denuncia),
            ['decisao' => 'rejeitar'],
        )->assertForbidden();

        $this->assertDatabaseHas('denuncias_comentarios', [
            'id' => $denuncia->id,
            'estado' => 'pendente',
        ]);
    }

    /**
     * @return array{Comentario, Utilizador, Utilizador}
     */
    private function criarCenario(): array
    {
        $autor = Utilizador::factory()->create();
        $denunciante = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()->comAutor($autor)->create();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário disponível para denúncia.',
            'comentario_pai_id' => null,
        ]);

        return [$comentario, $autor, $denunciante];
    }

    /**
     * @return array<string, array{bool, bool}>
     */
    public static function estadosComentariosDenunciados(): array
    {
        return [
            'comentario com respostas' => [true, false],
            'comentario ja eliminado' => [false, true],
            'marcador anteriormente eliminado' => [true, true],
        ];
    }

    #[Test]
    #[DataProvider('estadosComentariosDenunciados')]
    public function aceitacao_preserva_estrutura_da_conversa(
        bool $comRespostas,
        bool $jaEliminado,
    ): void {
        [$comentario, $autor, $denunciante] = $this->criarCenario();

        $moderador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $denuncia = DenunciaComentario::query()->create([
            'comentario_id' => $comentario->id,
            'denunciante_id' => $denunciante->id,
            'motivo' => 'spam',
        ]);

        $resposta = null;

        if ($comRespostas) {
            $publicacao = MetalThursday::query()
                ->findOrFail($comentario->comentavel_id);

            $resposta = $publicacao->comentarios()->create([
                'utilizador_id' => $denunciante->id,
                'conteudo' => 'Resposta que deve permanecer disponível.',
                'comentario_pai_id' => $comentario->id,
            ]);
        }

        if ($jaEliminado) {
            if ($comRespostas) {
                $comentario->conteudo_eliminado_em = now()->subDay();
                $comentario->saveOrFail();
            } else {
                $comentario->deleteOrFail();
            }
        }

        $comentario->refresh();

        $dataEliminacao = $comentario->getRawOriginal('deleted_at');
        $dataRemocaoConteudo = $comentario->getRawOriginal('conteudo_eliminado_em');

        $this->actingAs($moderador, 'sessao')
            ->patchJson(
                route('moderacao.denuncias-comentarios.decidir', $denuncia),
                ['decisao' => 'aceitar'],
            )
            ->assertOk();

        $comentario->refresh();

        if ($comRespostas) {
            $this->assertNotSoftDeleted($comentario);
            self::assertTrue($comentario->temConteudoEliminado());

            self::assertInstanceOf(Comentario::class, $resposta);

            $this->assertNotSoftDeleted($resposta);

            $this->assertDatabaseHas('comentarios', [
                'id' => $resposta->id,
                'comentario_pai_id' => $comentario->id,
                'conteudo' => 'Resposta que deve permanecer disponível.',
                'conteudo_eliminado_em' => null,
            ]);
        } else {
            $this->assertSoftDeleted($comentario);
        }

        if ($jaEliminado) {
            self::assertSame(
                $dataEliminacao,
                $comentario->getRawOriginal('deleted_at'),
            );

            self::assertSame(
                $dataRemocaoConteudo,
                $comentario->getRawOriginal('conteudo_eliminado_em'),
            );
        }

        $this->assertDatabaseHas('denuncias_comentarios', [
            'id' => $denuncia->id,
            'comentario_id' => $comentario->id,
            'estado' => 'aceite',
            'moderador_id' => $moderador->id,
        ]);
    }

    #[Test]
    public function aceitacao_remove_marcador_ancestral_sem_respostas(): void
    {
        [$pai, $autor, $denunciante] = $this->criarCenario();

        $moderador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $publicacao = MetalThursday::query()
            ->findOrFail($pai->comentavel_id);

        $resposta = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Última resposta denunciada.',
            'comentario_pai_id' => $pai->id,
        ]);

        $pai->conteudo_eliminado_em = now();
        $pai->saveOrFail();

        $denuncia = DenunciaComentario::query()->create([
            'comentario_id' => $resposta->id,
            'denunciante_id' => $denunciante->id,
            'motivo' => 'spam',
        ]);

        $this->actingAs($moderador, 'sessao')
            ->patchJson(
                route('moderacao.denuncias-comentarios.decidir', $denuncia),
                ['decisao' => 'aceitar'],
            )
            ->assertOk();

        $this->assertSoftDeleted($resposta);
        $this->assertSoftDeleted($pai);

        $this->assertDatabaseHas('denuncias_comentarios', [
            'id' => $denuncia->id,
            'comentario_id' => $resposta->id,
            'estado' => 'aceite',
            'moderador_id' => $moderador->id,
        ]);
    }

    #[Test]
    public function aceita_denuncias_distintas_do_mesmo_comentario(): void
    {
        [$comentario, $autor, $denunciante] = $this->criarCenario();

        $segundoDenunciante = Utilizador::factory()->create();

        $moderador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $primeira = DenunciaComentario::query()->create([
            'comentario_id' => $comentario->id,
            'denunciante_id' => $denunciante->id,
            'motivo' => 'spam',
        ]);

        $segunda = DenunciaComentario::query()->create([
            'comentario_id' => $comentario->id,
            'denunciante_id' => $segundoDenunciante->id,
            'motivo' => 'ofensivo',
        ]);

        $this->actingAs($moderador, 'sessao')
            ->patchJson(
                route('moderacao.denuncias-comentarios.decidir', $primeira),
                ['decisao' => 'aceitar'],
            )
            ->assertOk();

        $this->assertSoftDeleted($comentario);

        $comentario->refresh();

        $dataEliminacao = $comentario->getRawOriginal('deleted_at');

        $this->assertDatabaseHas('denuncias_comentarios', [
            'id' => $primeira->id,
            'estado' => 'aceite',
        ]);

        $this->assertDatabaseHas('denuncias_comentarios', [
            'id' => $segunda->id,
            'estado' => 'pendente',
        ]);

        $this->patchJson(
            route('moderacao.denuncias-comentarios.decidir', $segunda),
            ['decisao' => 'aceitar'],
        )->assertOk();

        $comentario->refresh();

        self::assertSame(
            $dataEliminacao,
            $comentario->getRawOriginal('deleted_at'),
        );

        $this->assertDatabaseHas('denuncias_comentarios', [
            'id' => $segunda->id,
            'estado' => 'aceite',
            'moderador_id' => $moderador->id,
        ]);

        $this->assertDatabaseCount('denuncias_comentarios', 2);
        $this->assertSoftDeleted($comentario);
    }
}
