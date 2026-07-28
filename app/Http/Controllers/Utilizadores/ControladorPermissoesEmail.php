<?php

declare(strict_types=1);

namespace App\Http\Controllers\Utilizadores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilizadores\AtualizarPermissoesEmailRequest;
use App\Models\Autenticacao\Utilizador;
use App\Servicos\Comunicacoes\ServicoPermissoesEmail;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use LogicException;

/**
 * Gere a atualização das permissões de e-mail do utilizador autenticado.
 *
 * @since 2.0.0
 *
 * @version 1.3.0
 */
final class ControladorPermissoesEmail extends Controller
{
    /**
     * Mensagem apresentada após a atualização das permissões.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private const MENSAGEM_SUCESSO =
        'Permissões de e-mail atualizadas com sucesso.';

    /**
     * Cria o controlador.
     *
     * @param  ServicoPermissoesEmail  $servicoPermissoesEmail  Serviço
     *                                                          responsável
     *                                                          pela
     *                                                          sincronização.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        private readonly ServicoPermissoesEmail $servicoPermissoesEmail,
    ) {}

    /**
     * Atualiza as permissões de e-mail do utilizador autenticado.
     *
     * @param  AtualizarPermissoesEmailRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento para o perfil.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     * @throws LogicException Quando os dados validados não contêm uma lista
     *                        válida.
     *
     * @since 2.0.0
     *
     * @version 1.3.0
     */
    public function atualizar(
        AtualizarPermissoesEmailRequest $pedido,
    ): RedirectResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado(
                $pedido,
            );

        $identificadoresPermissoes =
            $pedido->obterIdentificadoresPermissoes();

        $this
            ->servicoPermissoesEmail
            ->sincronizar(
                $utilizador,
                $identificadoresPermissoes,
            );

        return to_route(
            'perfil.editar',
        )->with(
            'sucesso',
            self::MENSAGEM_SUCESSO,
        );
    }

    /**
     * Obtém o utilizador autenticado.
     *
     * @param  AtualizarPermissoesEmailRequest  $pedido  Pedido autenticado.
     * @return Utilizador Utilizador autenticado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterUtilizadorAutenticado(
        AtualizarPermissoesEmailRequest $pedido,
    ): Utilizador {
        $utilizador =
            $pedido->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para atualizar as permissões de e-mail.',
            );
        }

        return $utilizador;
    }
}
