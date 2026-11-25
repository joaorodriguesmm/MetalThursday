<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
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
 * Testa a interface de denúncia e a área administrativa de moderação.
 */
final class InterfaceModeracaoComentariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    #[Test]
    public function visitante_nao_consulta_painel(): void
    {
        $this->get(route('moderacao.denuncias-comentarios.painel'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function utilizadores_sem_privilegios_nao_consultam_painel(): void
    {
        foreach ([null, PapelUtilizador::Administrador] as $papel) {
            $utilizador = $papel === null
                ? Utilizador::factory()->create()
                : Utilizador::factory()->comPapel($papel)->create();

            $this->actingAs($utilizador, 'sessao')
                ->get(route('moderacao.denuncias-comentarios.painel'))
                ->assertForbidden();
        }
    }

    #[Test]
    public function superadministrador_consulta_denuncia_e_acoes_da_fila(): void
    {
        [$comentario, $denuncia] = $this->criarDenuncia();
        $moderador = $this->criarSuperadministrador();

        $this->actingAs($moderador, 'sessao')
            ->get(route('moderacao.denuncias-comentarios.painel'))
            ->assertOk()
            ->assertSee('Moderação de comentários')
            ->assertSee('Rejeitar denúncia')
            ->assertSee('Aceitar denúncia')
            ->assertSee($comentario->conteudo)
            ->assertSee(route('moderacao.denuncias-comentarios.decidir', $denuncia), false)
            ->assertSee(route('metal-thursday.detalhes', [
                'metalThursday' => $comentario->comentavel_id,
                'comentario' => $comentario->id,
            ]), false);
    }

    #[Test]
    public function detalhes_da_denuncia_sao_escapados(): void
    {
        [, $denuncia] = $this->criarDenuncia();
        $denuncia->detalhes = '<script>alert("inseguro")</script>';
        $denuncia->saveOrFail();

        $this->actingAs($this->criarSuperadministrador(), 'sessao')
            ->get(route('moderacao.denuncias-comentarios.painel'))
            ->assertOk()
            ->assertDontSee('<script>alert("inseguro")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    #[Test]
    public function painel_filtra_estado_e_esconde_acoes_apos_decisao(): void
    {
        [, $denuncia] = $this->criarDenuncia();
        $denuncia->estado = 'rejeitada';
        $denuncia->moderador_id = $this->criarSuperadministrador()->id;
        $denuncia->decidida_em = now();
        $denuncia->saveOrFail();

        $this->actingAs($this->criarSuperadministrador(), 'sessao')
            ->get(route('moderacao.denuncias-comentarios.painel'))
            ->assertOk()
            ->assertDontSee('Denúncia #'.$denuncia->id);

        $this->get(route('moderacao.denuncias-comentarios.painel', [
            'estado' => 'rejeitada',
        ]))
            ->assertOk()
            ->assertSee('Denúncia #'.$denuncia->id)
            ->assertSee('Decisão: Rejeitada')
            ->assertDontSee('Rejeitar denúncia');
    }

    #[Test]
    public function formulario_regista_decisao_e_regressa_ao_painel(): void
    {
        [, $denuncia] = $this->criarDenuncia();
        $moderador = $this->criarSuperadministrador();

        $this->actingAs($moderador, 'sessao')
            ->patch(route('moderacao.denuncias-comentarios.decidir', $denuncia), [
                'decisao' => 'aceitar',
            ])
            ->assertRedirect(route('moderacao.denuncias-comentarios.painel'))
            ->assertSessionHas('sucesso');

        $this->assertDatabaseHas('denuncias_comentarios', [
            'id' => $denuncia->id,
            'estado' => 'aceite',
            'moderador_id' => $moderador->id,
        ]);
    }

    #[Test]
    public function botao_denunciar_aparece_apenas_a_terceiros(): void
    {
        $autor = Utilizador::factory()->create();
        $terceiro = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()
            ->comAutor($autor)
            ->create();

        $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário de teste para denúncia.',
            'comentario_pai_id' => null,
        ]);

        $endereco = route('comentarios.indice', [
            'tipoComentavel' => TipoEntidadeInteracao::MetalThursday->value,
            'identificadorComentavel' => $publicacao->id,
        ]);

        $respostaAutor = $this->actingAs($autor, 'sessao')
            ->getJson($endereco)
            ->assertOk()
            ->json('comentarios_html');

        self::assertIsString($respostaAutor);
        self::assertStringNotContainsString('data-formulario-denuncia-comentario', $respostaAutor);

        $respostaTerceiro = $this->actingAs($terceiro, 'sessao')
            ->getJson($endereco)
            ->assertOk()
            ->json('comentarios_html');

        self::assertIsString($respostaTerceiro);
        self::assertStringContainsString('data-formulario-denuncia-comentario', $respostaTerceiro);
    }

    #[Test]
    public function denuncia_anterior_oculta_acao_apos_recarregar_comentarios(): void
    {
        $autor = Utilizador::factory()->create();
        $denunciante = Utilizador::factory()->create();
        $outroUtilizador = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()
            ->comAutor($autor)
            ->create();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário denunciado para testar o recarregamento.',
            'comentario_pai_id' => null,
        ]);

        $endereco = route('comentarios.indice', [
            'tipoComentavel' => TipoEntidadeInteracao::MetalThursday->value,
            'identificadorComentavel' => $publicacao->id,
        ]);

        $this->actingAs($denunciante, 'sessao')
            ->postJson(route('comentarios.denuncias.guardar', $comentario), [
                'motivo' => 'spam',
            ])
            ->assertCreated();

        $html = $this->getJson($endereco)
            ->assertOk()
            ->json('comentarios_html');

        self::assertIsString($html);
        self::assertStringContainsString('Já denunciaste este comentário', $html);
        self::assertStringNotContainsString('data-abrir-denuncia-comentario', $html);
        self::assertStringNotContainsString('data-formulario-denuncia-comentario', $html);
        self::assertStringNotContainsString('Denúncia enviada com sucesso.', $html);

        $htmlOutro = $this->actingAs($outroUtilizador, 'sessao')
            ->getJson($endereco)
            ->assertOk()
            ->json('comentarios_html');

        self::assertIsString($htmlOutro);
        self::assertStringContainsString('data-abrir-denuncia-comentario', $htmlOutro);
    }

    /** @return array{Comentario, DenunciaComentario} */
    private function criarDenuncia(): array
    {
        $autor = Utilizador::factory()->create();
        $denunciante = Utilizador::factory()->create();
        $publicacao = MetalThursday::factory()
            ->comAutor($autor)
            ->create();
        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Conteúdo denunciado para revisão.',
            'comentario_pai_id' => null,
        ]);
        $denuncia = DenunciaComentario::query()->create([
            'comentario_id' => $comentario->id,
            'denunciante_id' => $denunciante->id,
            'motivo' => 'spam',
        ]);

        return [$comentario, $denuncia];
    }

    private function criarSuperadministrador(): Utilizador
    {
        return Utilizador::factory()->comPapel(PapelUtilizador::SuperAdministrador)->create();
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function comentariosParaAceitacao(): array
    {
        return [
            'sem respostas' => [false],
            'com respostas' => [true],
        ];
    }

    #[Test]
    #[DataProvider('comentariosParaAceitacao')]
    public function painel_liga_a_publicacao_apos_aceitacao(
        bool $comRespostas,
    ): void {
        [$comentario, $denuncia] = $this->criarDenuncia();

        $moderador = $this->criarSuperadministrador();

        if ($comRespostas) {
            $publicacao = MetalThursday::query()
                ->findOrFail($comentario->comentavel_id);

            $publicacao->comentarios()->create([
                'utilizador_id' => $moderador->id,
                'conteudo' => 'Resposta preservada.',
                'comentario_pai_id' => $comentario->id,
            ]);
        }

        $this->actingAs($moderador, 'sessao')
            ->patch(
                route('moderacao.denuncias-comentarios.decidir', $denuncia),
                ['decisao' => 'aceitar'],
            )
            ->assertRedirect(route('moderacao.denuncias-comentarios.painel'));

        $this->get(route('moderacao.denuncias-comentarios.painel', [
            'estado' => 'aceite',
        ]))
            ->assertOk()
            ->assertSee('Decisão: Aceite')
            ->assertSee('O comentário foi eliminado.')
            ->assertSee('Ver publicação')
            ->assertSee(route('metal-thursday.detalhes', [
                'metalThursday' => $comentario->comentavel_id,
            ]), false)
            ->assertDontSee('Ver comentário')
            ->assertDontSee($comentario->conteudo)
            ->assertDontSee('Aceitar denúncia')
            ->assertDontSee('Rejeitar denúncia');

        $denunciante = $denuncia->denunciante;

        self::assertInstanceOf(Utilizador::class, $denunciante);

        $html = $this->actingAs($denunciante, 'sessao')
            ->getJson(route('comentarios.indice', [
                'tipoComentavel' => TipoEntidadeInteracao::MetalThursday->value,
                'identificadorComentavel' => $comentario->comentavel_id,
            ]))
            ->assertOk()
            ->json('comentarios_html');

        self::assertIsString($html);
        self::assertStringNotContainsString($comentario->conteudo, $html);
    }

    #[Test]
    public function painel_nao_liga_a_publicacao_futura(): void
    {
        [$comentario, $denuncia] = $this->criarDenuncia();

        $publicacao = MetalThursday::query()
            ->findOrFail($comentario->comentavel_id);

        $publicacao->updateOrFail([
            'data' => CarbonImmutable::today()->addDays(7),
        ]);

        $this->actingAs($this->criarSuperadministrador(), 'sessao')
            ->get(route('moderacao.denuncias-comentarios.painel'))
            ->assertOk()
            ->assertSee('Denúncia #'.$denuncia->id)
            ->assertSee('Aceitar denúncia')
            ->assertDontSee('Ver comentário')
            ->assertDontSee('Ver publicação');
    }
}
