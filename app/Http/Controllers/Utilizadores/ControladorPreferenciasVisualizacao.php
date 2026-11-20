<?php

declare(strict_types=1);

namespace App\Http\Controllers\Utilizadores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilizadores\AtualizarPreferenciasVisualizacaoRequest;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;

/**
 * Atualiza apenas as preferências de visualização do utilizador autenticado.
 *
 * @since 2.0.0
 */
final class ControladorPreferenciasVisualizacao extends Controller
{
    public function atualizar(
        AtualizarPreferenciasVisualizacaoRequest $pedido,
    ): RedirectResponse {
        $utilizador = $pedido->user('sessao');

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para atualizar as preferências.',
            );
        }

        $utilizador->preferencias_visualizacao_ligacoes =
            $pedido->obterPreferencias();

        $utilizador->save();

        return to_route('perfil.editar')->with(
            'sucesso',
            'Preferências de visualização guardadas com sucesso.',
        );
    }
}
