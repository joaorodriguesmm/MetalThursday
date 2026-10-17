<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoInteracaoUtilizador;
use App\Notifications\NotificacaoMetalThursdayCriada;
use App\Notifications\NotificacaoSessoesEncerradasUtilizador;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verifica que a entrega de e-mails respeita as permissões atuais e
 * preserva as notificações internas e as comunicações administrativas.
 *
 * @since 2.0.0
 */
final class NotificacaoAplicacaoEntregaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissaoEmailSeeder::class)->run();
    }

    #[Test]
    public function revogacao_de_novas_publicacoes_cancela_apenas_email_pendente(): void
    {
        $criador = Utilizador::factory()->create();
        $destinatario = Utilizador::factory()->create();
        $permissao = $this->obterPermissao(
            IdentificadorPermissaoEmail::NovasPublicacoes,
        );

        $destinatario->permissoesEmail()->attach($permissao->getKey());

        $metalThursday = MetalThursday::factory()
            ->comAutor($criador)
            ->comProximoNomeado($criador)
            ->create();

        $notificacao = new NotificacaoMetalThursdayCriada($metalThursday);
        $modeloDesatualizado = $destinatario->fresh();

        self::assertInstanceOf(Utilizador::class, $modeloDesatualizado);
        $modeloDesatualizado->load('permissoesEmail');
        self::assertSame(['database', 'mail'], $notificacao->via($modeloDesatualizado));

        $destinatario->permissoesEmail()->detach($permissao->getKey());

        self::assertTrue($modeloDesatualizado->temPermissaoEmail($permissao->identificador));
        self::assertFalse($notificacao->shouldSend($modeloDesatualizado, 'mail'));
        self::assertTrue($notificacao->shouldSend($modeloDesatualizado, 'database'));
    }

    #[Test]
    public function permissao_global_continua_a_autorizar_email(): void
    {
        $criador = Utilizador::factory()->create();
        $destinatario = Utilizador::factory()->create();
        $especifica = $this->obterPermissao(
            IdentificadorPermissaoEmail::NovasPublicacoes,
        );
        $global = $this->obterPermissao(
            IdentificadorPermissaoEmail::TodasNotificacoes,
        );

        $destinatario->permissoesEmail()->attach([
            $especifica->getKey(),
            $global->getKey(),
        ]);

        $metalThursday = MetalThursday::factory()
            ->comAutor($criador)
            ->comProximoNomeado($criador)
            ->create();

        $notificacao = new NotificacaoMetalThursdayCriada($metalThursday);
        $destinatarioDesatualizado = $destinatario->fresh();
        self::assertInstanceOf(Utilizador::class, $destinatarioDesatualizado);

        $destinatario->permissoesEmail()->detach($especifica->getKey());
        self::assertTrue($notificacao->shouldSend($destinatarioDesatualizado, 'mail'));

        $destinatario->permissoesEmail()->detach($global->getKey());
        self::assertFalse($notificacao->shouldSend($destinatarioDesatualizado, 'mail'));
    }

    #[Test]
    public function notificacoes_de_interacoes_tambem_revalidam_email(): void
    {
        $causador = Utilizador::factory()->create();
        $destinatario = Utilizador::factory()->create();
        $permissao = $this->obterPermissao(
            IdentificadorPermissaoEmail::TodasInteracoes,
        );
        $destinatario->permissoesEmail()->attach($permissao->getKey());

        $metalThursday = MetalThursday::factory()
            ->comAutor($causador)
            ->comProximoNomeado($causador)
            ->create();

        $comentario = $metalThursday->comentarios()->create([
            'utilizador_id' => $causador->getKey(),
            'conteudo' => 'Comentário para verificar permissões de email.',
            'comentario_pai_id' => null,
        ]);

        $notificacao = new NotificacaoInteracaoUtilizador(
            $metalThursday,
            $causador,
            'comentou',
            $comentario,
        );

        $destinatarioDesatualizado = $destinatario->fresh();
        self::assertInstanceOf(Utilizador::class, $destinatarioDesatualizado);
        $destinatarioDesatualizado->load('permissoesEmail');
        self::assertSame(['database', 'mail'], $notificacao->via($destinatarioDesatualizado));

        self::assertTrue($notificacao->shouldSend($destinatarioDesatualizado, 'mail'));

        $destinatario->permissoesEmail()->detach($permissao->getKey());

        self::assertFalse($notificacao->shouldSend($destinatarioDesatualizado, 'mail'));
        self::assertTrue($notificacao->shouldSend($destinatarioDesatualizado, 'database'));
    }

    #[Test]
    public function comunicacao_administrativa_chega_a_utilizador_suspenso_sem_permissoes(): void
    {
        $responsavel = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();
        $destinatario = Utilizador::factory()->create();

        $destinatario->forceFill([
            'suspenso_em' => now(),
            'motivo_suspensao' => 'Suspensão administrativa para testar a entrega.',
            'suspenso_por_id' => $responsavel->getKey(),
        ])->saveOrFail();

        $notificacao = new NotificacaoSessoesEncerradasUtilizador;

        self::assertTrue($notificacao->shouldSend($destinatario, 'mail'));
    }

    private function obterPermissao(
        IdentificadorPermissaoEmail $identificador,
    ): PermissaoEmail {
        return PermissaoEmail::query()
            ->where('identificador', $identificador->value)
            ->sole();
    }
}
