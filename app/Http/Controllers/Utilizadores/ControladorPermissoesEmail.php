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
 * @version 1.1.0
 */
final class ControladorPermissoesEmail extends Controller
{
    /**
     * Estado enviado após a atualização das permissões.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ESTADO_PERMISSOES_ATUALIZADAS =
        'permissoes-email-atualizadas';

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
     * @throws LogicException Quando os dados validados não contêm uma lista.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function atualizar(
        AtualizarPermissoesEmailRequest $pedido,
    ): RedirectResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado(
                $pedido,
            );

        $dados = $pedido->validated();

        $identificadoresPermissoes =
            $dados['permissoes_email']
            ?? [];

        if (! is_array($identificadoresPermissoes)) {
            throw new LogicException(
                'As permissões de e-mail validadas não formam uma lista.',
            );
        }

        $this->servicoPermissoesEmail->sincronizar(
            $utilizador,
            $identificadoresPermissoes,
        );

        return to_route(
            'perfil.editar',
        )->with(
            'estado',
            self::ESTADO_PERMISSOES_ATUALIZADAS,
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
        $utilizador = $pedido->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para atualizar as permissões de e-mail.',
            );
        }

        return $utilizador;
    }
}
