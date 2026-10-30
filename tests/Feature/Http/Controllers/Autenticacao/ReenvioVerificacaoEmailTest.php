<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Autenticacao;

use App\Models\Autenticacao\Utilizador;
use App\Notifications\NotificacaoVerificacaoEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class ReenvioVerificacaoEmailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function apresenta_formulario_e_ligacao_no_inicio_de_sessao(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('autenticacao.reenviar-verificacao-email'));

        $this->get(route('autenticacao.reenviar-verificacao-email'))
            ->assertOk()
            ->assertSee('Enviar nova ligação de verificação')
            ->assertSee(route('verification.send'));
    }

    #[Test]
    public function recupera_conta_depois_de_expirar_a_ligacao_original(): void
    {
        Notification::fake();
        $utilizador = Utilizador::factory()->naoVerificado()->create();
        $ligacaoOriginal = (new NotificacaoVerificacaoEmail)->toMail($utilizador)->actionUrl;

        $this->travel(61)->minutes();

        $this->get($ligacaoOriginal)->assertForbidden();
        self::assertFalse($utilizador->refresh()->hasVerifiedEmail());

        $this->post(route('verification.send'), [
            'email' => ' '.mb_strtoupper($utilizador->email).' ',
        ])
            ->assertRedirect(route('autenticacao.reenviar-verificacao-email'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('_old_input.email', $utilizador->email);

        $notificacao = Notification::sent($utilizador, NotificacaoVerificacaoEmail::class)->sole();
        $ligacaoNova = $notificacao->toMail($utilizador)->actionUrl;

        self::assertNotSame($ligacaoOriginal, $ligacaoNova);
        $this->get($ligacaoNova)->assertRedirect(route('login'));
        self::assertTrue($utilizador->refresh()->hasVerifiedEmail());
        $this->assertGuest('sessao');
    }

    #[Test]
    public function nao_revela_contas_inexistentes_verificadas_ou_suspensas(): void
    {
        Notification::fake();
        $verificado = Utilizador::factory()->create();
        $suspenso = Utilizador::factory()->naoVerificado()->suspensoPor($verificado)->create();
        $pendente = Utilizador::factory()->naoVerificado()->create();
        $mensagens = [];

        foreach (['inexistente@example.com', $verificado->email, $suspenso->email, $pendente->email] as $email) {
            $this->post(route('verification.send'), ['email' => $email])
                ->assertRedirect(route('autenticacao.reenviar-verificacao-email'))
                ->assertSessionHasNoErrors();

            $mensagens[] = session('informacao');
        }

        self::assertCount(1, array_unique($mensagens));
        Notification::assertNotSentTo($verificado, NotificacaoVerificacaoEmail::class);
        Notification::assertNotSentTo($suspenso, NotificacaoVerificacaoEmail::class);
        Notification::assertSentToTimes($pendente, NotificacaoVerificacaoEmail::class, 1);
        Notification::assertCount(1);
    }

    #[Test]
    public function limita_reenvios_do_mesmo_endereco_entre_ips_e_permite_nova_tentativa(): void
    {
        Notification::fake();
        $utilizador = Utilizador::factory()->naoVerificado()->create();

        foreach (['203.0.113.40', '203.0.113.41'] as $ip) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->post(route('verification.send'), ['email' => $utilizador->email])
                ->assertSessionHasNoErrors();
        }

        Notification::assertSentToTimes($utilizador, NotificacaoVerificacaoEmail::class, 1);

        $this->travel(61)->seconds();
        $this->post(route('verification.send'), ['email' => $utilizador->email])
            ->assertSessionHasNoErrors();

        Notification::assertSentToTimes($utilizador, NotificacaoVerificacaoEmail::class, 2);
    }

    #[Test]
    public function valida_o_email_sem_enviar_notificacoes(): void
    {
        Notification::fake();

        foreach ([null, [], 'endereco-invalido', str_repeat('a', 256).'@example.com'] as $email) {
            $this->post(route('verification.send'), ['email' => $email])
                ->assertSessionHasErrors('email');
        }

        Notification::assertNothingSent();
    }

    #[Test]
    public function limita_pedidos_http_incluindo_emails_invalidos(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42']);

        for ($pedido = 0; $pedido < 6; $pedido++) {
            $this->post(route('verification.send'), [])->assertRedirect();
        }

        $this->post(route('verification.send'), [])->assertTooManyRequests();
    }

    #[Test]
    public function falha_de_envio_preserva_resposta_generica_e_permite_repetir(): void
    {
        $utilizador = Utilizador::factory()->naoVerificado()->create();
        Log::spy();
        Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Credencial SMTP secreta'));

        $this->post(route('verification.send'), ['email' => $utilizador->email])
            ->assertRedirect(route('autenticacao.reenviar-verificacao-email'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('informacao');

        self::assertStringNotContainsString('Credencial SMTP', (string) session('informacao'));
        Log::shouldHaveReceived('error')->once()->with(
            'Ocorreu um erro ao reenviar a verificação do endereço de e-mail.',
            ['excecao' => RuntimeException::class],
        );
        self::assertFalse($utilizador->refresh()->hasVerifiedEmail());

        Notification::fake();
        $this->travel(61)->seconds();
        $this->post(route('verification.send'), ['email' => $utilizador->email])
            ->assertSessionHasNoErrors();
        Notification::assertSentTo($utilizador, NotificacaoVerificacaoEmail::class);
    }

    #[Test]
    public function nao_processa_reenvios_de_utilizadores_autenticados(): void
    {
        Notification::fake();
        $utilizador = Utilizador::factory()->create();

        $this->actingAs($utilizador, 'sessao')
            ->get(route('autenticacao.reenviar-verificacao-email'))
            ->assertRedirect(route('inicio'));

        $this->post(route('verification.send'), ['email' => $utilizador->email])
            ->assertRedirect(route('inicio'));
        Notification::assertNothingSent();
    }
}
