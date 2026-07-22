<?php

declare(strict_types=1);

namespace App\Http\Controllers\Utilizadores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilizadores\AtualizarPerfilRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\EmailPermission;
use App\Servicos\Utilizadores\ServicoAtualizacaoPerfil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use LogicException;
use Throwable;

/**
 * Gere a apresentação e atualização dos dados gerais do perfil.
 *
 * A atualização da palavra-passe e das permissões de e-mail pertence a
 * controladores próprios, evitando concentrar casos de uso independentes
 * nesta classe.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class ControladorPerfil extends Controller
{
    /**
     * Apresenta a página de edição do perfil.
     *
     * @param  Request  $pedido  - Pedido autenticado.
     * @return View - Página de edição do perfil.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function editar(Request $pedido): View
    {
        $utilizador = $this->obterUtilizadorAutenticado(
            $pedido,
        );

        $permissoesEmail = EmailPermission::query()
            ->select([
                'id',
                'name',
                'slug',
                'description',
            ])
            ->orderBy('name')
            ->get();

        $identificadoresPermissoesEmail = $utilizador
            ->permissoesEmail()
            ->pluck('email_permissions.id')
            ->map(
                static fn(mixed $identificador): int => (int) $identificador,
            )
            ->all();

        return view(
            'utilizadores.perfil.editar',
            [
                'utilizador' => $utilizador,
                'permissoesEmail' => $permissoesEmail,
                'identificadoresPermissoesEmail' => $identificadoresPermissoesEmail,
            ],
        );
    }

    /**
     * Atualiza os dados gerais do perfil.
     *
     * Quando o endereço de e-mail muda, a sessão é terminada e é enviada uma
     * nova notificação de verificação.
     *
     * @param  AtualizarPerfilRequest  $pedido  - Pedido validado.
     * @param  ServicoAtualizacaoPerfil  $servicoPerfil  - Serviço da atualização.
     * @return RedirectResponse - Redirecionamento após a atualização.
     *
     * @throws Throwable Quando a atualização falha.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function atualizar(
        AtualizarPerfilRequest $pedido,
        ServicoAtualizacaoPerfil $servicoPerfil,
    ): RedirectResponse {
        $utilizador = $this->obterUtilizadorAutenticado(
            $pedido,
        );

        $dados = $pedido->validated();
        $fotografia = $this->obterFotografia($pedido);

        $resultado = $servicoPerfil->atualizar(
            utilizador: $utilizador,
            nome: $dados['nome'],
            email: $dados['email'],
            fotografia: $fotografia,
        );

        if (! $resultado->emailFoiAlterado()) {
            return redirect()
                ->route('perfil.editar')
                ->with(
                    'estado',
                    'perfil-atualizado',
                );
        }

        $utilizadorAtualizado =
            $resultado->obterUtilizador();

        $notificacaoEnviada = true;

        try {
            $utilizadorAtualizado
                ->sendEmailVerificationNotification();
        } catch (Throwable $excecao) {
            $notificacaoEnviada = false;

            Log::error(
                'Não foi possível enviar a verificação do novo endereço de e-mail.',
                [
                    'utilizador_id' => $utilizadorAtualizado->getKey(),

                    'excecao' => $excecao::class,
                    'mensagem' => $excecao->getMessage(),
                ],
            );
        }

        Auth::guard('web')->logout();

        $pedido->session()->invalidate();
        $pedido->session()->regenerateToken();

        if (! $notificacaoEnviada) {
            return redirect()
                ->route('login')
                ->with(
                    'erro',
                    'O perfil foi atualizado, mas não foi possível enviar a mensagem de verificação. Solicita uma nova mensagem antes de iniciares sessão.',
                );
        }

        return redirect()
            ->route('login')
            ->with(
                'estado',
                'O perfil foi atualizado. Verifica o novo endereço de e-mail antes de iniciares sessão novamente.',
            );
    }

    /**
     * Obtém o utilizador autenticado com o tipo esperado.
     *
     * @param  Request  $pedido  - Pedido autenticado.
     * @return Utilizador - Utilizador autenticado.
     *
     * @throws LogicException Quando não existe um utilizador válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterUtilizadorAutenticado(
        Request $pedido,
    ): Utilizador {
        $utilizador = $pedido->user();

        if (! $utilizador instanceof Utilizador) {
            throw new LogicException(
                'Não existe um utilizador autenticado válido.',
            );
        }

        return $utilizador;
    }

    /**
     * Obtém a fotografia validada do pedido.
     *
     * @param  AtualizarPerfilRequest  $pedido  - Pedido validado.
     * @return UploadedFile|null - Fotografia ou nulo.
     *
     * @throws LogicException Quando o campo não contém um único ficheiro.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterFotografia(
        AtualizarPerfilRequest $pedido,
    ): ?UploadedFile {
        if (! $pedido->hasFile('fotografia')) {
            return null;
        }

        $fotografia = $pedido->file('fotografia');

        if (! $fotografia instanceof UploadedFile) {
            throw new LogicException(
                'A fotografia recebida não é um ficheiro válido.',
            );
        }

        return $fotografia;
    }
}
