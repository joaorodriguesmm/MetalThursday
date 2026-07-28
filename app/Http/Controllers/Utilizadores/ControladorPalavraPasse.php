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
 * @version 1.2.0
 */
final class ControladorPalavraPasse extends Controller
{
    /**
     * Nome do saco de erros utilizado pelo formulário.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const SACO_ERROS = 'palavraPasse';

    /**
     * Mensagem apresentada após a atualização da palavra-passe.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private const MENSAGEM_SUCESSO =
        'Palavra-passe atualizada com sucesso.';

    /**
     * Cria o controlador.
     *
     * @param  ServicoAtualizacaoPalavraPasse  $servicoPalavraPasse  Serviço
     *                                                               responsável
     *                                                               pela
     *                                                               atualização.
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
     * @version 1.2.0
     */
    public function atualizar(
        AtualizarPalavraPasseRequest $pedido,
    ): RedirectResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado(
                $pedido,
            );

        $dados =
            $pedido->validated();

        /** @var string $palavraPasseAtual */
        $palavraPasseAtual =
            $dados['palavra_passe_atual'];

        /** @var string $novaPalavraPasse */
        $novaPalavraPasse =
            $dados['nova_palavra_passe'];

        try {
            $this
                ->servicoPalavraPasse
                ->atualizar(
                    $utilizador,
                    $palavraPasseAtual,
                    $novaPalavraPasse,
                );
        } catch (PalavraPasseAtualIncorreta $excecao) {
            return to_route(
                'perfil.editar',
            )->withErrors(
                [
                    'palavra_passe_atual' => $excecao->getMessage(),
                ],
                self::SACO_ERROS,
            );
        } catch (NovaPalavraPasseIgualAAtual $excecao) {
            return to_route(
                'perfil.editar',
            )->withErrors(
                [
                    'nova_palavra_passe' => $excecao->getMessage(),
                ],
                self::SACO_ERROS,
            );
        }

        return to_route(
            'perfil.editar',
        )->with(
            'sucesso',
            self::MENSAGEM_SUCESSO,
        );
    }

    /**
     * Obtém o utilizador autenticado associado ao pedido.
     *
     * @param  AtualizarPalavraPasseRequest  $pedido  Pedido HTTP.
     * @return Utilizador Utilizador autenticado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterUtilizadorAutenticado(
        AtualizarPalavraPasseRequest $pedido,
    ): Utilizador {
        $utilizador =
            $pedido->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para alterar a palavra-passe.',
            );
        }

        return $utilizador;
    }
}
