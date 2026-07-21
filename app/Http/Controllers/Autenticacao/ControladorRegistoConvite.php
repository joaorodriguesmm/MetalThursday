<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\AceitarConviteRequest;
use App\Models\EmailPermission;
use App\Servicos\Autenticacao\ServicoConvites;
use App\Servicos\Autenticacao\ServicoRegistoPorConvite;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use SensitiveParameter;
use Throwable;

/**
 * Gere a apresentação e o processamento do registo por convite.
 *
 * O controlador limita-se a coordenar o pedido HTTP, o armazenamento da
 * fotografia e os serviços de domínio. A criação do utilizador e a utilização
 * do convite são executadas atomicamente pelo serviço de registo.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class ControladorRegistoConvite extends Controller
{
    /**
     * Apresenta o formulário de aceitação de um convite disponível.
     *
     * A mesma mensagem é apresentada para convites inexistentes, utilizados,
     * revogados ou expirados, evitando revelar o estado interno do convite.
     *
     * @param  string  $codigoConvite  - Código original do convite.
     * @param  ServicoConvites  $servicoConvites  - Serviço dos convites.
     * @return View|RedirectResponse - Formulário de registo ou
     *                               redirecionamento para a autenticação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function apresentar(
        #[SensitiveParameter]
        string $codigoConvite,
        ServicoConvites $servicoConvites,
    ): View|RedirectResponse {
        $convite = $servicoConvites
            ->encontrarDisponivelPorCodigo($codigoConvite);

        if ($convite === null) {
            return redirect()
                ->route('autenticacao.entrar')
                ->with(
                    'erro',
                    'Este convite é inválido ou já não está disponível.',
                );
        }

        $permissoesEmail = EmailPermission::query()
            ->select([
                'id',
                'slug',
                'name',
                'description',
            ])
            ->orderBy('name')
            ->get();

        return view(
            'autenticacao.aceitar-convite',
            [
                'convite' => $convite,
                'codigoConvite' => $codigoConvite,
                'permissoesEmail' => $permissoesEmail,
            ],
        );
    }

    /**
     * Cria o utilizador e marca o convite como utilizado.
     *
     * A fotografia é guardada antes da transação da base de dados. Caso o
     * registo falhe, o ficheiro criado é eliminado para evitar ficheiros
     * órfãos.
     *
     * O evento de registo é disparado apenas depois de a transação terminar
     * com sucesso.
     *
     * @param  AceitarConviteRequest  $pedido  - Pedido validado.
     * @param  ServicoRegistoPorConvite  $servicoRegisto  - Serviço responsável
     *                                                    pelo registo transacional.
     * @return RedirectResponse - Redirecionamento para a autenticação.
     *
     * @throws Throwable Quando ocorre um erro durante o registo.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function registar(
        AceitarConviteRequest $pedido,
        ServicoRegistoPorConvite $servicoRegisto,
    ): RedirectResponse {
        $dados = $pedido->validated();
        $caminhoFotografia = null;

        if ($pedido->hasFile('fotografia')) {
            $caminhoFotografia = $pedido
                ->file('fotografia')
                ?->store(
                    'fotografias/utilizadores',
                    'public',
                );
        }

        try {
            $utilizador = $servicoRegisto->registar(
                codigoConvite: $dados['codigo_convite'],
                nome: $dados['nome'],
                email: $dados['email'],
                palavraPasse: $dados['palavra_passe'],
                caminhoFotografia: $caminhoFotografia,
                identificadoresPermissoesEmail: $dados['permissoes_email'] ?? [],
            );
        } catch (Throwable $excecao) {
            if ($caminhoFotografia !== null) {
                Storage::disk('public')->delete(
                    $caminhoFotografia,
                );
            }

            throw $excecao;
        }

        event(new Registered($utilizador));

        return redirect()
            ->route('autenticacao.entrar')
            ->with(
                'estado',
                'Registo concluído. Foi enviado um link de verificação para o teu endereço de e-mail.',
            );
    }
}
