<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Http\Requests\Autenticacao\CriarConviteRequest;
use App\Http\Requests\Autenticacao\RevogarConviteRequest;
use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a utilização explícita do guard `sessao` nos pedidos dos convites.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class GuardaSessaoConvitesRequestsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma o guard utilizado pelo pedido de criação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function pedido_criacao_utiliza_guarda_sessao(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $pedidoComSessao =
            new CriarConviteRequest;

        $this->configurarUtilizadores(
            $pedidoComSessao,
            utilizadorSessao: $superAdministrador,
            utilizadorPredefinido: null,
        );

        self::assertTrue(
            $pedidoComSessao->authorize(),
        );

        self::assertSame(
            $superAdministrador,
            $pedidoComSessao->obterUtilizadorAutenticado(),
        );

        $pedidoApenasPredefinido =
            new CriarConviteRequest;

        $this->configurarUtilizadores(
            $pedidoApenasPredefinido,
            utilizadorSessao: null,
            utilizadorPredefinido: $superAdministrador,
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
     * Confirma o guard utilizado pelo pedido de revogação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function pedido_revogacao_utiliza_guarda_sessao(): void
    {
        $superAdministrador =
            $this->criarSuperAdministrador();

        $convite =
            Convite::factory()
                ->create();

        $pedidoComSessao =
            new RevogarConviteRequest;

        $this->configurarUtilizadores(
            $pedidoComSessao,
            utilizadorSessao: $superAdministrador,
            utilizadorPredefinido: null,
        );

        $this->configurarConviteDaRota(
            $pedidoComSessao,
            $convite,
        );

        self::assertTrue(
            $pedidoComSessao->authorize(),
        );

        self::assertSame(
            $superAdministrador,
            $pedidoComSessao->obterUtilizadorAutenticado(),
        );

        $pedidoApenasPredefinido =
            new RevogarConviteRequest;

        $this->configurarUtilizadores(
            $pedidoApenasPredefinido,
            utilizadorSessao: null,
            utilizadorPredefinido: $superAdministrador,
        );

        $this->configurarConviteDaRota(
            $pedidoApenasPredefinido,
            $convite,
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
     * Configura utilizadores diferentes para cada resolução.
     *
     * @param  FormRequest  $pedido  Pedido configurado.
     * @param  Utilizador|null  $utilizadorSessao  Utilizador da sessão.
     * @param  Utilizador|null  $utilizadorPredefinido  Utilizador sem guard.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     * Configura o convite associado ao parâmetro da rota.
     *
     * @param  FormRequest  $pedido  Pedido configurado.
     * @param  Convite  $convite  Convite da rota.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function configurarConviteDaRota(
        FormRequest $pedido,
        Convite $convite,
    ): void {
        $rota =
            new Route(
                [
                    'PATCH',
                ],
                'convites/{convite}/revogar',
                static fn () => null,
            );

        $rota->bind(
            Request::create(
                '/convites/'.$convite->getKey().'/revogar',
                'PATCH',
            ),
        );

        $rota->setParameter(
            'convite',
            $convite,
        );

        $pedido->setRouteResolver(
            static fn (): Route => $rota,
        );
    }

    /**
     * Cria um superadministrador ativo.
     *
     * @return Utilizador Superadministrador criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarSuperAdministrador(): Utilizador
    {
        return Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();
    }
}
