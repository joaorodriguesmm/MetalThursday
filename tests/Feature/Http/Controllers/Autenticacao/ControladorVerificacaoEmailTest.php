<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Autenticacao;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a verificação pública do endereço de e-mail.
 *
 * @since 2.0.0
 */
final class ControladorVerificacaoEmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma o limite HTTP aplicado às ligações de verificação assinadas.
     *
     * É utilizada uma ligação válida para garantir que o middleware `signed`
     * não rejeita o pedido antes de este alcançar o limitador HTTP.
     *
     * @since 2.0.0
     */
    #[Test]
    public function limita_pedidos_http_de_verificacao_do_email(): void
    {
        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.23',
        ]);

        $utilizador = Utilizador::factory()
            ->naoVerificado()
            ->create();

        $endereco =
            URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(
                    5,
                ),
                [
                    'id' => $utilizador->getKey(),

                    'hash' => sha1(
                        (string) $utilizador->getEmailForVerification(),
                    ),
                ],
            );

        for ($pedido = 0; $pedido < 6; $pedido++) {
            $this
                ->get(
                    $endereco,
                )
                ->assertRedirect(
                    route(
                        'login',
                    ),
                );
        }

        $this
            ->get(
                $endereco,
            )
            ->assertStatus(
                429,
            );
    }
}
