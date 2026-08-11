<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Autenticacao;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ControladorRegistoConviteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function convite_inexistente_nao_revela_se_email_ja_existe(): void
    {
        $codigoConvite =
            'MT-Convite-Inexistente-123456';

        Utilizador::factory()
            ->create([
                'email' => 'existente@exemplo.pt',
            ]);

        $resposta =
            $this->post(
                route(
                    'convites.registar',
                ),
                [
                    'codigo_convite' => $codigoConvite,
                    'nome' => 'Novo Utilizador',
                    'email' => 'existente@exemplo.pt',
                    'palavra_passe' => 'MetalThursday#2026',
                    'confirmacao_palavra_passe' => 'MetalThursday#2026',
                    'permissoes_email' => [],
                ],
            );

        $resposta
            ->assertRedirect(
                route(
                    'convites.aceitar',
                    [
                        'codigoConvite' => $codigoConvite,
                    ],
                ),
            )
            ->assertSessionDoesntHaveErrors([
                'email',
            ])
            ->assertSessionHas(
                'erro',
                'Não foi possível concluir o registo. Confirma os dados e verifica se o convite continua disponível.',
            );
    }
}
