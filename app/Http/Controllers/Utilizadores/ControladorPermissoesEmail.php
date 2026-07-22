<?php

declare(strict_types=1);

namespace App\Http\Controllers\Utilizadores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilizadores\AtualizarPermissoesEmailRequest;
use App\Models\Autenticacao\Utilizador;
use App\Servicos\Comunicacoes\ServicoPermissoesEmail;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;

/**
 * Gere a atualização das permissões de e-mail do utilizador.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorPermissoesEmail extends Controller
{
    /**
     * Cria o controlador.
     *
     * @param  ServicoPermissoesEmail  $servicoPermissoesEmail  Serviço
     *                                                          responsável pela sincronização das permissões.
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
     * @throws AuthenticationException Quando não existe um utilizador
     *                                 autenticado válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function atualizar(
        AtualizarPermissoesEmailRequest $pedido,
    ): RedirectResponse {
        $utilizador = $pedido->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para atualizar as permissões de e-mail.',
            );
        }

        /**
         * A validação garante que este valor é sempre uma lista.
         *
         * @var array<int, int|string> $identificadoresPermissoes
         */
        $identificadoresPermissoes = $pedido->validated(
            'permissoes_email',
            [],
        );

        /*
         * A chamada é propositadamente posicional. Assim, o controlador não
         * fica dependente dos nomes internos dos parâmetros do serviço.
         */
        $this->servicoPermissoesEmail->sincronizar(
            $utilizador,
            $identificadoresPermissoes,
        );

        return to_route(
            'perfil.editar',
        )->with(
            'estado',
            'permissoes-email-atualizadas',
        );
    }
}
