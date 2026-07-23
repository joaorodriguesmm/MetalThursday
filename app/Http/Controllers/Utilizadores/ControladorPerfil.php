<?php

declare(strict_types=1);

namespace App\Http\Controllers\Utilizadores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilizadores\AtualizarPerfilRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Servicos\Utilizadores\ServicoAtualizacaoPerfil;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use LogicException;
use Throwable;

/**
 * Gere a apresentação e a atualização dos dados gerais do perfil.
 *
 * A palavra-passe e as permissões de e-mail são geridas por controladores
 * próprios.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class ControladorPerfil extends Controller
{
    /**
     * Estado utilizado depois de atualizar o perfil.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ESTADO_PERFIL_ATUALIZADO =
        'perfil-atualizado';

    /**
     * Cria o controlador.
     *
     * @param  ServicoAtualizacaoPerfil  $servicoPerfil  Serviço responsável pela
     *                                                   atualização do
     *                                                   perfil.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        private readonly ServicoAtualizacaoPerfil $servicoPerfil,
    ) {}

    /**
     * Apresenta a página de edição do perfil.
     *
     * @param  Request  $pedido  Pedido autenticado.
     * @return View Página de edição do perfil.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function editar(
        Request $pedido,
    ): View {
        $utilizador =
            $this->obterUtilizadorAutenticado(
                $pedido,
            );

        $permissoesEmail = PermissaoEmail::query()
            ->select([
                'id',
                'identificador',
                'nome',
                'descricao',
            ])
            ->orderBy('nome')
            ->orderBy('id')
            ->get();

        $identificadoresPermissoesEmail =
            $utilizador
                ->permissoesEmail()
                ->pluck(
                    'permissoes_email.id',
                )
                ->map(
                    static fn (
                        mixed $identificador,
                    ): int => (int) $identificador,
                )
                ->sort()
                ->values()
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
     * Quando o endereço de e-mail é alterado, a verificação anterior deixa de
     * ser válida, é enviada uma nova notificação e a sessão é terminada.
     *
     * @param  AtualizarPerfilRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento após a atualização.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     * @throws Throwable Quando a atualização do perfil falha.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function atualizar(
        AtualizarPerfilRequest $pedido,
    ): RedirectResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado(
                $pedido,
            );

        $dados = $pedido->validated();

        /** @var string $nome */
        $nome = $dados['nome'];

        /** @var string $email */
        $email = $dados['email'];

        $resultado =
            $this->servicoPerfil->atualizar(
                $utilizador,
                $nome,
                $email,
                $this->obterFotografia(
                    $pedido,
                ),
            );

        if (! $resultado->emailFoiAlterado()) {
            return to_route(
                'perfil.editar',
            )->with(
                'estado',
                self::ESTADO_PERFIL_ATUALIZADO,
            );
        }

        $utilizadorAtualizado =
            $resultado->obterUtilizador();

        $notificacaoEnviada =
            $this->enviarNotificacaoVerificacao(
                $utilizadorAtualizado,
            );

        $this->terminarSessao(
            $pedido,
        );

        if (! $notificacaoEnviada) {
            return to_route(
                'login',
            )->with(
                'erro',
                'O perfil foi atualizado, mas não foi possível enviar a mensagem de verificação do novo endereço de e-mail.',
            );
        }

        return to_route(
            'login',
        )->with(
            'estado',
            'O perfil foi atualizado. Verifica o novo endereço de e-mail antes de iniciares sessão novamente.',
        );
    }

    /**
     * Envia a notificação de verificação do novo endereço de e-mail.
     *
     * A atualização do perfil já foi concluída quando este método é chamado.
     * Uma falha no envio é reportada, mas não tenta reverter os dados.
     *
     * @param  Utilizador  $utilizador  Utilizador atualizado.
     * @return bool Verdadeiro quando a notificação foi enviada.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function enviarNotificacaoVerificacao(
        Utilizador $utilizador,
    ): bool {
        try {
            $utilizador
                ->sendEmailVerificationNotification();

            return true;
        } catch (Throwable $excecao) {
            report(
                $excecao,
            );

            return false;
        }
    }

    /**
     * Termina a sessão autenticada e renova o token CSRF.
     *
     * @param  Request  $pedido  Pedido HTTP.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function terminarSessao(
        Request $pedido,
    ): void {
        Auth::guard('web')->logout();

        $pedido
            ->session()
            ->invalidate();

        $pedido
            ->session()
            ->regenerateToken();
    }

    /**
     * Obtém o utilizador autenticado.
     *
     * @param  Request  $pedido  Pedido autenticado.
     * @return Utilizador Utilizador autenticado.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function obterUtilizadorAutenticado(
        Request $pedido,
    ): Utilizador {
        $utilizador = $pedido->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para atualizar o perfil.',
            );
        }

        return $utilizador;
    }

    /**
     * Obtém a fotografia validada do pedido.
     *
     * @param  AtualizarPerfilRequest  $pedido  Pedido validado.
     * @return UploadedFile|null Fotografia ou nulo.
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

        $fotografia = $pedido->file(
            'fotografia',
        );

        if (! $fotografia instanceof UploadedFile) {
            throw new LogicException(
                'A fotografia recebida não é um ficheiro válido.',
            );
        }

        return $fotografia;
    }
}
