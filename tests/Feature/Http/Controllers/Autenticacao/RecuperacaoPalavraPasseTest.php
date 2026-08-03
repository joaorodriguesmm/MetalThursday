<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Autenticacao;

use Illuminate\Routing\Route as RotaLaravel;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa as rotas e os formulários públicos de recuperação da palavra-passe.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class RecuperacaoPalavraPasseTest extends TestCase
{
    /**
     * Confirma o contrato português das rotas de recuperação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function regista_rotas_portuguesas_de_recuperacao(): void
    {
        $rotaApresentar =
            $this->obterRota(
                'autenticacao.recuperar-palavra-passe',
            );

        self::assertSame(
            'palavra-passe/esquecida',
            $rotaApresentar->uri(),
        );

        self::assertSame(
            [
                'GET',
                'HEAD',
            ],
            $rotaApresentar->methods(),
        );

        $rotaEnviar =
            $this->obterRota(
                'autenticacao.enviar-ligacao-redefinicao',
            );

        self::assertSame(
            'palavra-passe/esquecida',
            $rotaEnviar->uri(),
        );

        self::assertSame(
            [
                'POST',
            ],
            $rotaEnviar->methods(),
        );
    }

    /**
     * Confirma o contrato português das rotas de redefinição.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function regista_rotas_portuguesas_de_redefinicao(): void
    {
        $rotaApresentar =
            $this->obterRota(
                'autenticacao.redefinir-palavra-passe',
            );

        self::assertSame(
            'palavra-passe/redefinir/{token}',
            $rotaApresentar->uri(),
        );

        self::assertSame(
            [
                'GET',
                'HEAD',
            ],
            $rotaApresentar->methods(),
        );

        $rotaAtualizar =
            $this->obterRota(
                'autenticacao.atualizar-palavra-passe',
            );

        self::assertSame(
            'palavra-passe/redefinir',
            $rotaAtualizar->uri(),
        );

        self::assertSame(
            [
                'POST',
            ],
            $rotaAtualizar->methods(),
        );
    }

    /**
     * Garante que os nomes antigos não permanecem registados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function nao_regista_nomes_antigos_das_rotas(): void
    {
        foreach (
            [
                'password.request',
                'password.email',
                'password.reset',
                'password.store',
            ] as $nomeRota
        ) {
            self::assertFalse(
                Route::has(
                    $nomeRota,
                ),
            );
        }
    }

    /**
     * Apresenta os formulários públicos sem referências a rotas inexistentes.
     *
     * Este teste cobre a falha original, na qual a vista de recuperação
     * lançava uma exceção ao tentar gerar o endereço do formulário.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function apresenta_formularios_publicos_sem_erros(): void
    {
        $enderecoRecuperacao =
            route(
                'autenticacao.recuperar-palavra-passe',
            );

        $enderecoEnvio =
            route(
                'autenticacao.enviar-ligacao-redefinicao',
            );

        $enderecoAtualizacao =
            route(
                'autenticacao.atualizar-palavra-passe',
            );

        $this
            ->get(
                route('login'),
            )
            ->assertOk()
            ->assertSee(
                $enderecoRecuperacao,
                false,
            );

        $this
            ->get(
                $enderecoRecuperacao,
            )
            ->assertOk()
            ->assertViewIs(
                'autenticacao.recuperar-palavra-passe',
            )
            ->assertSee(
                $enderecoEnvio,
                false,
            );

        $this
            ->get(
                route(
                    'autenticacao.redefinir-palavra-passe',
                    [
                        'token' => 'codigo-redefinicao-teste',

                        'email' => 'utilizador@example.com',
                    ],
                ),
            )
            ->assertOk()
            ->assertViewIs(
                'autenticacao.redefinir-palavra-passe',
            )
            ->assertSee(
                $enderecoAtualizacao,
                false,
            )
            ->assertSee(
                $enderecoRecuperacao,
                false,
            );
    }

    /**
     * Obtém uma rota registada pelo respetivo nome.
     *
     * @param  string  $nome  Nome da rota.
     * @return RotaLaravel Rota registada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterRota(
        string $nome,
    ): RotaLaravel {
        $rota =
            Route::getRoutes()
                ->getByName(
                    $nome,
                );

        self::assertInstanceOf(
            RotaLaravel::class,
            $rota,
        );

        return $rota;
    }
}
