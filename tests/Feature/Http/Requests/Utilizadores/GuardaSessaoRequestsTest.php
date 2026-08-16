<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Http\Requests\Utilizadores\AlterarPapelUtilizadorRequest;
use App\Http\Requests\Utilizadores\AtualizarPalavraPasseRequest;
use App\Http\Requests\Utilizadores\AtualizarPerfilRequest;
use App\Http\Requests\Utilizadores\AtualizarPermissoesEmailRequest;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a utilização explícita do guard `sessao` nos pedidos do utilizador.
 *
 * @since 2.0.0
 */
final class GuardaSessaoRequestsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o pedido da palavra-passe utiliza apenas o guard `sessao`.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pedido_palavra_passe_utiliza_guarda_sessao(): void
    {
        $utilizador =
            new Utilizador;

        $pedidoComSessao =
            new AtualizarPalavraPasseRequest;

        $this->configurarUtilizadores(
            $pedidoComSessao,
            utilizadorSessao: $utilizador,
            utilizadorPredefinido: null,
        );

        self::assertTrue(
            $pedidoComSessao->authorize(),
        );

        $pedidoApenasPredefinido =
            new AtualizarPalavraPasseRequest;

        $this->configurarUtilizadores(
            $pedidoApenasPredefinido,
            utilizadorSessao: null,
            utilizadorPredefinido: $utilizador,
        );

        self::assertFalse(
            $pedidoApenasPredefinido->authorize(),
        );
    }

    /**
     * Confirma que o pedido do perfil utiliza apenas o guard `sessao`.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pedido_perfil_utiliza_guarda_sessao(): void
    {
        $utilizador =
            new Utilizador;

        $pedidoComSessao =
            new AtualizarPerfilRequest;

        $this->configurarUtilizadores(
            $pedidoComSessao,
            utilizadorSessao: $utilizador,
            utilizadorPredefinido: null,
        );

        self::assertTrue(
            $pedidoComSessao->authorize(),
        );

        $pedidoApenasPredefinido =
            new AtualizarPerfilRequest;

        $this->configurarUtilizadores(
            $pedidoApenasPredefinido,
            utilizadorSessao: null,
            utilizadorPredefinido: $utilizador,
        );

        self::assertFalse(
            $pedidoApenasPredefinido->authorize(),
        );
    }

    /**
     * Confirma que o pedido das permissões utiliza apenas o guard `sessao`.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pedido_permissoes_email_utiliza_guarda_sessao(): void
    {
        $utilizador =
            new Utilizador;

        $pedidoComSessao =
            new AtualizarPermissoesEmailRequest;

        $this->configurarUtilizadores(
            $pedidoComSessao,
            utilizadorSessao: $utilizador,
            utilizadorPredefinido: null,
        );

        self::assertTrue(
            $pedidoComSessao->authorize(),
        );

        $pedidoApenasPredefinido =
            new AtualizarPermissoesEmailRequest;

        $this->configurarUtilizadores(
            $pedidoApenasPredefinido,
            utilizadorSessao: null,
            utilizadorPredefinido: $utilizador,
        );

        self::assertFalse(
            $pedidoApenasPredefinido->authorize(),
        );
    }

    /**
     * Confirma que o pedido da alteração do papel utiliza apenas o guard
     * `sessao`.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pedido_alteracao_papel_utiliza_guarda_sessao(): void
    {
        $superAdministrador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::SuperAdministrador,
                )
                ->create();

        $utilizadorAfetado =
            Utilizador::factory()
                ->create();

        $pedidoComSessao =
            new AlterarPapelUtilizadorRequest;

        $this->configurarUtilizadores(
            $pedidoComSessao,
            utilizadorSessao: $superAdministrador,
            utilizadorPredefinido: null,
        );

        $this->configurarUtilizadorDaRota(
            $pedidoComSessao,
            $utilizadorAfetado,
        );

        self::assertTrue(
            $pedidoComSessao->authorize(),
        );

        self::assertSame(
            $superAdministrador,
            $pedidoComSessao->obterUtilizadorAutenticado(),
        );

        $pedidoApenasPredefinido =
            new AlterarPapelUtilizadorRequest;

        $this->configurarUtilizadores(
            $pedidoApenasPredefinido,
            utilizadorSessao: null,
            utilizadorPredefinido: $superAdministrador,
        );

        $this->configurarUtilizadorDaRota(
            $pedidoApenasPredefinido,
            $utilizadorAfetado,
        );

        self::assertFalse(
            $pedidoApenasPredefinido->authorize(),
        );

        $this->expectException(
            LogicException::class,
        );

        $pedidoApenasPredefinido
            ->obterUtilizadorAutenticado();
    }

    /**
     * Configura utilizadores diferentes para o guard `sessao` e para a
     * resolução sem indicação explícita de guard.
     *
     * @param  FormRequest  $pedido  Pedido configurado.
     * @param  Utilizador|null  $utilizadorSessao  Utilizador da sessão.
     * @param  Utilizador|null  $utilizadorPredefinido  Utilizador devolvido
     *                                                  sem guard.
     *
     * @since 2.0.0
     */
    private function configurarUtilizadores(
        FormRequest $pedido,
        ?Utilizador $utilizadorSessao,
        ?Utilizador $utilizadorPredefinido,
    ): void {
        $pedido->setUserResolver(
            static fn (
                ?string $guarda = null,
            ): ?Utilizador => $guarda === 'sessao'
                ? $utilizadorSessao
                : $utilizadorPredefinido,
        );
    }

    /**
     * Configura o utilizador associado ao parâmetro da rota.
     *
     * @param  FormRequest  $pedido  Pedido configurado.
     * @param  Utilizador  $utilizador  Utilizador da rota.
     *
     * @since 2.0.0
     */
    private function configurarUtilizadorDaRota(
        FormRequest $pedido,
        Utilizador $utilizador,
    ): void {
        $rota =
            new Route(
                [
                    'PATCH',
                ],
                'utilizadores/{utilizador}/papel',
                static fn () => null,
            );

        $rota->bind(
            Request::create(
                '/utilizadores/'.$utilizador->getKey().'/papel',
                'PATCH',
            ),
        );

        $rota->setParameter(
            'utilizador',
            $utilizador,
        );

        $pedido->setRouteResolver(
            static fn (): Route => $rota,
        );
    }
}
