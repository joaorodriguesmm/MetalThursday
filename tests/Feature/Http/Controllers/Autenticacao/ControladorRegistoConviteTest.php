<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Autenticacao;

use App\Models\Autenticacao\Utilizador;
use App\Servicos\Autenticacao\ServicoConvites;
use Database\Seeders\PermissaoEmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ControladorRegistoConviteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara cada teste.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Test]
    public function apresenta_permissao_global_e_permissoes_individuais(): void
    {
        $criador =
            Utilizador::factory()
                ->create();

        $resultado =
            app(
                ServicoConvites::class,
            )->criar(
                nomeConvidado: 'Novo Utilizador',
                emailDestino: null,
                criador: $criador,
                expiraEm: null,
            );

        $this->seed(
            PermissaoEmailSeeder::class,
        );

        $resposta =
            $this->get(
                route(
                    'convites.aceitar',
                    [
                        'codigoConvite' => $resultado->obterCodigo(),
                    ],
                ),
            );

        $resposta
            ->assertOk()
            ->assertSee(
                'Todas as notificações',
            )
            ->assertSee(
                'data-permissao-email-todas',
                escape: false,
            );

        $conteudo =
            $resposta->getContent();

        self::assertSame(
            1,
            substr_count(
                $conteudo,
                'data-permissao-email-todas',
            ),
        );

        self::assertSame(
            6,
            substr_count(
                $conteudo,
                'data-permissao-email-individual',
            ),
        );
    }

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
