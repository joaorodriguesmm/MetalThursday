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
use Illuminate\Support\Facades\Auth;

/**
 * Gere a atualização da palavra-passe do utilizador autenticado.
 *
 * O controlador coordena exclusivamente o fluxo HTTP. A confirmação da
 * palavra-passe atual começa no Form Request e a validação definitiva, a
 * persistência e a rotação da credencial persistente pertencem ao serviço de
 * atualização da palavra-passe.
 *
 * @since 2.0.0
 */
final class ControladorPalavraPasse extends Controller
{
    /**
     * Nome do saco de erros utilizado pelo formulário.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const SACO_ERROS =
        'palavraPasse';

    /**
     * Mensagem apresentada quando a palavra-passe atual não corresponde à
     * credencial persistida.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_PALAVRA_PASSE_ATUAL_INCORRETA =
        'A palavra-passe atual introduzida não está correta.';

    /**
     * Mensagem apresentada quando a nova palavra-passe coincide com a atual.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_NOVA_PALAVRA_PASSE_IGUAL =
        'A nova palavra-passe deve ser diferente da palavra-passe atual.';

    /**
     * Mensagem apresentada após a atualização da palavra-passe.
     *
     * @var string
     *
     * @since 2.0.0
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
     */
    public function __construct(
        private readonly ServicoAtualizacaoPalavraPasse $servicoPalavraPasse,
    ) {}

    /**
     * Atualiza a palavra-passe do utilizador autenticado.
     *
     * O pedido fornece exclusivamente valores já validados. O serviço volta
     * a confirmar o estado persistido dentro da operação de atualização,
     * protegendo o fluxo contra alterações concorrentes.
     *
     * A sessão atual permanece autenticada. A rotação da credencial
     * persistente realizada pelo serviço invalida autenticações futuras
     * baseadas no token anterior.
     *
     * @param  AtualizarPalavraPasseRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento para o perfil.
     *
     * @throws AuthenticationException Quando não existe um utilizador
     *                                 autenticado válido.
     *
     * @since 2.0.0
     */
    public function atualizar(
        AtualizarPalavraPasseRequest $pedido,
    ): RedirectResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        $palavraPasseAtual =
            $pedido->obterPalavraPasseAtual();

        $novaPalavraPasse =
            $pedido->obterNovaPalavraPasse();

        try {
            $this
                ->servicoPalavraPasse
                ->atualizar(
                    $utilizador,
                    $palavraPasseAtual,
                    $novaPalavraPasse,
                );
        } catch (PalavraPasseAtualIncorreta) {
            return to_route(
                'perfil.editar',
            )->withErrors(
                [
                    'palavra_passe_atual' => self::MENSAGEM_PALAVRA_PASSE_ATUAL_INCORRETA,
                ],
                self::SACO_ERROS,
            );
        } catch (NovaPalavraPasseIgualAAtual) {
            return to_route(
                'perfil.editar',
            )->withErrors(
                [
                    'nova_palavra_passe' => self::MENSAGEM_NOVA_PALAVRA_PASSE_IGUAL,
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
     * Obtém o utilizador autenticado através do guard da aplicação.
     *
     * @return Utilizador Utilizador autenticado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     */
    private function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador =
            Auth::guard(
                'sessao',
            )->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para alterar a palavra-passe.',
            );
        }

        return $utilizador;
    }
}
