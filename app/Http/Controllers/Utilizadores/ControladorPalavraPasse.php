<?php

declare(strict_types=1);

namespace App\Http\Controllers\Utilizadores;

use App\Excecoes\Autenticacao\NovaPalavraPasseIgualAAtual;
use App\Excecoes\Autenticacao\PalavraPasseAtualIncorreta;
use App\Http\Controllers\Controller;
use App\Http\Requests\Utilizadores\AtualizarPalavraPasseRequest;
use App\Models\Autenticacao\Utilizador;
use App\Servicos\Autenticacao\ServicoAtualizacaoPalavraPasse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;

/**
 * Gere a atualização da palavra-passe do utilizador autenticado.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorPalavraPasse extends Controller
{
    /**
     * Cria o controlador.
     *
     * @param  ServicoAtualizacaoPalavraPasse  $servicoPalavraPasse  Serviço
     *                                                               responsável pela atualização segura da palavra-passe.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        private readonly ServicoAtualizacaoPalavraPasse $servicoPalavraPasse,
    ) {}

    /**
     * Atualiza a palavra-passe do utilizador autenticado.
     *
     * @param  AtualizarPalavraPasseRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento para o perfil.
     *
     * @throws AuthenticationException Quando não existe um utilizador
     *                                 autenticado válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function atualizar(
        AtualizarPalavraPasseRequest $pedido,
    ): RedirectResponse {
        $utilizador = $pedido->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para alterar a palavra-passe.',
            );
        }

        $dadosValidados = $pedido->validated();

        /** @var string $palavraPasseAtual */
        $palavraPasseAtual =
            $dadosValidados['palavra_passe_atual'];

        /** @var string $novaPalavraPasse */
        $novaPalavraPasse =
            $dadosValidados['nova_palavra_passe'];

        try {
            /*
             * A chamada é posicional para não depender dos nomes internos
             * dos parâmetros do serviço.
             */
            $this->servicoPalavraPasse->atualizar(
                $utilizador,
                $palavraPasseAtual,
                $novaPalavraPasse,
            );
        } catch (
            PalavraPasseAtualIncorreta $excecao
        ) {
            return to_route(
                'perfil.editar',
            )->withErrors(
                [
                    'palavra_passe_atual' => $excecao->getMessage(),
                ],
                'palavraPasse',
            );
        } catch (
            NovaPalavraPasseIgualAAtual $excecao
        ) {
            return to_route(
                'perfil.editar',
            )->withErrors(
                [
                    'nova_palavra_passe' => $excecao->getMessage(),
                ],
                'palavraPasse',
            );
        }

        return to_route(
            'perfil.editar',
        )->with(
            'estado',
            'palavra-passe-atualizada',
        );
    }
}
