<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\Interacoes\Comentario;
use App\Models\Interacoes\DenunciaComentario;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoDecisaoDenunciaComentario;
use App\Notifications\NotificacaoNovaDenunciaComentario;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Valida as notificações internas e as preferências opcionais de e-mail
 * associadas às denúncias e às decisões de moderação.
 */
final class NotificacoesDenunciasComentariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissaoEmailSeeder::class)->run();
    }

    #[Test]
    public function nova_denuncia_so_notifica_superadministradores(): void
    {
        $utilizador = Utilizador::factory()->create();
        $superadministrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $notificacao = new NotificacaoNovaDenunciaComentario(12);

        self::assertSame([], $notificacao->via($utilizador));

        $this->atribuirPermissao(
            $utilizador,
            IdentificadorPermissaoEmail::NovasDenunciasComentarios,
        );
        self::assertSame([], $notificacao->via($utilizador));
        self::assertSame(['database'], $notificacao->via($superadministrador));
        self::assertSame('nova_denuncia_comentario', $notificacao->toArray($superadministrador)['tipo']);
        self::assertSame(12, $notificacao->toArray($superadministrador)['identificador_denuncia']);
    }

    #[Test]
    public function nova_denuncia_respeita_permissao_especifica_ou_global(): void
    {
        $superadministrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();
        $notificacao = new NotificacaoNovaDenunciaComentario(3);

        $this->atribuirPermissao(
            $superadministrador,
            IdentificadorPermissaoEmail::NovasDenunciasComentarios,
        );
        self::assertSame(['database', 'mail'], $notificacao->via($superadministrador));

        $superadministrador->permissoesEmail()->detach();
        $superadministrador->unsetRelation('permissoesEmail');
        self::assertSame(['database'], $notificacao->via($superadministrador));

        $this->atribuirPermissao(
            $superadministrador,
            IdentificadorPermissaoEmail::TodasNotificacoes,
        );
        self::assertSame(['database', 'mail'], $notificacao->via($superadministrador));
    }

    #[Test]
    public function revogacao_de_permissao_impede_email_antes_da_entrega(): void
    {
        $superadministrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();
        $denunciante = Utilizador::factory()->create();

        $this->atribuirPermissao(
            $superadministrador,
            IdentificadorPermissaoEmail::NovasDenunciasComentarios,
        );
        $this->atribuirPermissao(
            $denunciante,
            IdentificadorPermissaoEmail::DecisoesDenunciasComentarios,
        );

        $denuncia = $this->criarDenunciaPendente($denunciante);
        $nova = new NotificacaoNovaDenunciaComentario($denuncia->id);
        $decisao = new NotificacaoDecisaoDenunciaComentario(1, 'rejeitada');

        self::assertTrue($nova->shouldSend($superadministrador, 'mail'));
        self::assertTrue($decisao->shouldSend($denunciante, 'mail'));

        $superadministrador->permissoesEmail()->detach();
        $denunciante->permissoesEmail()->detach();

        self::assertFalse($nova->shouldSend($superadministrador, 'mail'));
        self::assertFalse($decisao->shouldSend($denunciante, 'mail'));
        self::assertTrue($nova->shouldSend($superadministrador, 'database'));
        self::assertTrue($decisao->shouldSend($denunciante, 'database'));
    }

    #[Test]
    public function privilegios_revogados_impedem_ambos_os_canais_da_nova_denuncia(): void
    {
        $superadministrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();
        $this->atribuirPermissao(
            $superadministrador,
            IdentificadorPermissaoEmail::NovasDenunciasComentarios,
        );

        $denunciante = Utilizador::factory()->create();
        $denuncia = $this->criarDenunciaPendente($denunciante);

        $notificacao = new NotificacaoNovaDenunciaComentario($denuncia->id);
        self::assertSame(['database', 'mail'], $notificacao->via($superadministrador));

        self::assertTrue($notificacao->shouldSend($superadministrador, 'database'));
        self::assertTrue($notificacao->shouldSend($superadministrador, 'mail'));

        $superadministrador->papel = PapelUtilizador::Utilizador;
        $superadministrador->saveOrFail();

        self::assertFalse($notificacao->shouldSend($superadministrador, 'database'));
        self::assertFalse($notificacao->shouldSend($superadministrador, 'mail'));
    }

    #[Test]
    public function decisao_e_comunicada_na_aplicacao_sem_email_por_predefinicao(): void
    {
        $denunciante = Utilizador::factory()->create();
        $notificacao = new NotificacaoDecisaoDenunciaComentario(8, 'aceite');

        self::assertSame(['database'], $notificacao->via($denunciante));
        self::assertSame('decisao_denuncia_comentario', $notificacao->toArray($denunciante)['tipo']);
        self::assertSame('aceite', $notificacao->toArray($denunciante)['estado']);
        self::assertStringContainsString(
            'aceite',
            $notificacao->toArray($denunciante)['mensagem'],
        );
    }

    #[Test]
    public function decisao_envia_email_com_permissao_e_liga_ao_comentario(): void
    {
        $denunciante = Utilizador::factory()->create();
        $this->atribuirPermissao(
            $denunciante,
            IdentificadorPermissaoEmail::DecisoesDenunciasComentarios,
        );

        [$publicacao, $comentario] = $this->criarContextoDaDecisao();

        $notificacao = new NotificacaoDecisaoDenunciaComentario(
            4,
            'rejeitada',
            $publicacao->id,
            $comentario->id,
        );

        self::assertSame(['database', 'mail'], $notificacao->via($denunciante));
        self::assertSame(
            route('metal-thursday.detalhes', [
                'metalThursday' => $publicacao->id,
                'comentario' => $comentario->id,
            ]),
            $notificacao->toArray($denunciante)['ligacao'],
        );
        self::assertStringContainsString(
            'rejeitada',
            $notificacao->toArray($denunciante)['mensagem'],
        );
    }

    #[Test]
    public function identificadores_e_estado_invalidos_sao_rejeitados(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new NotificacaoDecisaoDenunciaComentario(0, 'pendente');
    }

    #[Test]
    public function suspensao_do_denunciante_impede_ambos_os_canais(): void
    {
        $denunciante = Utilizador::factory()->create();
        $moderador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $this->atribuirPermissao(
            $denunciante,
            IdentificadorPermissaoEmail::DecisoesDenunciasComentarios,
        );

        $notificacao = new NotificacaoDecisaoDenunciaComentario(1, 'aceite');

        self::assertSame(['database', 'mail'], $notificacao->via($denunciante));
        self::assertTrue($notificacao->shouldSend($denunciante, 'database'));
        self::assertTrue($notificacao->shouldSend($denunciante, 'mail'));

        Utilizador::query()->whereKey($denunciante->id)->update([
            'suspenso_em' => now(),
            'motivo_suspensao' => 'Suspensão para teste.',
            'suspenso_por_id' => $moderador->id,
        ]);

        self::assertTrue($denunciante->temAcessoAtivo());
        self::assertFalse($notificacao->shouldSend($denunciante, 'database'));
        self::assertFalse($notificacao->shouldSend($denunciante, 'mail'));
        self::assertSame([], $notificacao->via($denunciante->refresh()));
    }

    #[Test]
    public function decisao_apresenta_ligacoes_alternativas(): void
    {
        $denunciante = Utilizador::factory()->create();

        [$publicacao, $comentario] = $this->criarContextoDaDecisao();

        $comComentario = new NotificacaoDecisaoDenunciaComentario(1, 'aceite', $publicacao->id, $comentario->id);
        $semComentario = new NotificacaoDecisaoDenunciaComentario(1, 'aceite', $publicacao->id);
        $semPublicacao = new NotificacaoDecisaoDenunciaComentario(1, 'aceite');

        $urlComentario = route('metal-thursday.detalhes', ['metalThursday' => $publicacao->id, 'comentario' => $comentario->id]);
        $urlPublicacao = route('metal-thursday.detalhes', ['metalThursday' => $publicacao->id]);
        $urlInicio = route('inicio');

        self::assertSame('Ver comentário', $comComentario->toMail($denunciante)->actionText);
        self::assertSame($urlComentario, $comComentario->toMail($denunciante)->actionUrl);

        self::assertSame('Ver publicação', $semComentario->toMail($denunciante)->actionText);
        self::assertSame($urlPublicacao, $semComentario->toMail($denunciante)->actionUrl);
        self::assertSame($urlPublicacao, $semComentario->toArray($denunciante)['ligacao']);

        self::assertSame('Abrir MetalThursday', $semPublicacao->toMail($denunciante)->actionText);
        self::assertSame($urlInicio, $semPublicacao->toMail($denunciante)->actionUrl);
        self::assertSame($urlInicio, $semPublicacao->toArray($denunciante)['ligacao']);
    }

    /**
     * @return array{MetalThursday, Comentario}
     */
    private function criarContextoDaDecisao(): array
    {
        $autor = Utilizador::factory()->create();

        $publicacao = MetalThursday::factory()
            ->comAutor($autor)
            ->create();

        $comentario = $publicacao->comentarios()->create([
            'utilizador_id' => $autor->id,
            'conteudo' => 'Comentário para testar ligações das notificações.',
            'comentario_pai_id' => null,
        ]);

        return [$publicacao, $comentario];
    }

    /**
     * Cria uma denúncia pendente para validar a entrega efetiva.
     */
    private function criarDenunciaPendente(
        Utilizador $denunciante,
    ): DenunciaComentario {
        [, $comentario] = $this->criarContextoDaDecisao();

        return DenunciaComentario::query()->create([
            'comentario_id' => $comentario->id,
            'denunciante_id' => $denunciante->id,
            'motivo' => 'spam',
        ]);
    }

    private function atribuirPermissao(
        Utilizador $utilizador,
        IdentificadorPermissaoEmail $identificador,
    ): void {
        $permissao = PermissaoEmail::query()
            ->where('identificador', $identificador->value)
            ->sole();

        $utilizador->permissoesEmail()->syncWithoutDetaching([
            $permissao->id,
        ]);
        $utilizador->unsetRelation('permissoesEmail');
    }
}
