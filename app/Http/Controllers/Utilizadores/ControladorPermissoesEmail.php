<?php

declare(strict_types=1);

namespace App\Http\Controllers\Utilizadores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilizadores\AtualizarPermissoesEmailRequest;
use App\Models\Autenticacao\Utilizador;
use App\Servicos\Comunicacoes\ServicoPermissoesEmail;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Gere a atualização das permissões de e-mail do utilizador autenticado.
 *
 * O controlador coordena exclusivamente o fluxo HTTP. A validação dos
 * identificadores pertence ao Form Request e a sincronização das relações é
 * integralmente delegada ao serviço de permissões de e-mail.
 *
 * Uma lista vazia representa a remoção de todas as permissões atualmente
 * atribuídas ao utilizador.
 *
 * @since 2.0.0
 */
final class ControladorPermissoesEmail extends Controller
{
    /**
     * Mensagem apresentada após a atualização das permissões.
     *
     * @var string
     *
     * @since 2.0.0
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
     */
    public function __construct(
        private readonly ServicoPermissoesEmail $servicoPermissoesEmail,
    ) {}

    /**
     * Atualiza as permissões de e-mail do utilizador autenticado.
     *
     * A lista recebida contém apenas identificadores inteiros, positivos,
     * distintos e existentes. Quando a lista está vazia, o serviço remove
     * todas as permissões atribuídas.
     *
     * @param  AtualizarPermissoesEmailRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento para o perfil.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     * @throws LogicException Quando os dados validados não contêm uma lista
     *                        válida.
     *
     * @since 2.0.0
     */
    public function atualizar(
        AtualizarPermissoesEmailRequest $pedido,
    ): RedirectResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado();

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
                'É necessário iniciar sessão para atualizar as permissões de e-mail.',
            );
        }

        return $utilizador;
    }
}
