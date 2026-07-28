<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\SolicitarRedefinicaoPalavraPasseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Gere o pedido de envio da ligação de redefinição da palavra-passe.
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
final class ControladorLigacaoRedefinicaoPalavraPasse extends Controller
{
    /**
     * Mensagem genérica apresentada depois do pedido.
     *
     * A mesma mensagem é utilizada independentemente da existência da conta,
     * do envio da ligação ou da aplicação de uma limitação temporária,
     * impedindo a enumeração de utilizadores.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private const MENSAGEM_PEDIDO_RECEBIDO =
        'Se existir uma conta associada ao endereço indicado, será enviada uma ligação para redefinir a palavra-passe.';

    /**
     * Apresenta o formulário de recuperação da palavra-passe.
     *
     * @return View Formulário de recuperação.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function apresentar(): View
    {
        return view(
            'autenticacao.recuperar-palavra-passe',
        );
    }

    /**
     * Solicita o envio da ligação de redefinição da palavra-passe.
     *
     * A resposta apresentada ao visitante é sempre igual,
     * independentemente do resultado devolvido pelo gestor de
     * palavras-passe.
     *
     * @param  SolicitarRedefinicaoPalavraPasseRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento para o formulário.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    public function enviar(
        SolicitarRedefinicaoPalavraPasseRequest $pedido,
    ): RedirectResponse {
        $email =
            $pedido->email();

        /*
         * A chave `email` pertence ao contrato interno do gestor de
         * palavras-passe do Laravel.
         */
        Password::sendResetLink([
            'email' => $email,
        ]);

        return to_route(
            'password.request',
        )
            ->withInput([
                'email' => $email,
            ])
            ->with(
                'informacao',
                self::MENSAGEM_PEDIDO_RECEBIDO,
            );
    }
}
