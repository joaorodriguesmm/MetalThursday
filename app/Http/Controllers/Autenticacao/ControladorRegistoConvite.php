<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\AceitarConviteRequest;
use App\Models\Comunicacao\PermissaoEmail;
use App\Servicos\Autenticacao\ServicoConvites;
use App\Servicos\Autenticacao\ServicoRegistoPorConvite;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use SensitiveParameter;
use Throwable;

/**
 * Gere a apresentação e o processamento do registo por convite.
 *
 * O controlador coordena o pedido HTTP, o armazenamento da fotografia e os
 * serviços responsáveis pela validação do convite e pelo registo
 * transacional do utilizador.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class ControladorRegistoConvite extends Controller
{
    /**
     * Disco utilizado para armazenar fotografias públicas.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const DISCO_FOTOGRAFIAS = 'public';

    /**
     * Diretório utilizado para armazenar fotografias de utilizadores.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const DIRETORIO_FOTOGRAFIAS =
        'fotografias/utilizadores';

    /**
     * Apresenta o formulário de aceitação de um convite disponível.
     *
     * A mesma mensagem é apresentada para convites inexistentes, utilizados,
     * revogados ou expirados, evitando revelar o estado interno do convite.
     *
     * @param  string  $codigoConvite  Código original do convite.
     * @param  ServicoConvites  $servicoConvites  Serviço dos convites.
     * @return View|RedirectResponse Formulário ou redirecionamento.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function apresentar(
        #[SensitiveParameter]
        string $codigoConvite,
        ServicoConvites $servicoConvites,
    ): View|RedirectResponse {
        $convite = $servicoConvites
            ->encontrarDisponivelPorCodigo(
                $codigoConvite,
            );

        if ($convite === null) {
            return redirect()
                ->route('login')
                ->with(
                    'erro',
                    'Este convite é inválido ou já não está disponível.',
                );
        }

        $permissoesEmail = PermissaoEmail::query()
            ->select([
                'id',
                'identificador',
                'nome',
                'descricao',
            ])
            ->orderBy('nome')
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
     * A fotografia é armazenada antes do registo transacional. Quando o
     * registo falha, o ficheiro criado é eliminado sem ocultar a exceção
     * original.
     *
     * O evento de registo é emitido apenas depois da conclusão do serviço
     * transacional.
     *
     * @param  AceitarConviteRequest  $pedido  Pedido validado.
     * @param  ServicoRegistoPorConvite  $servicoRegisto  Serviço de registo.
     * @return RedirectResponse Redirecionamento para a autenticação.
     *
     * @throws Throwable Quando o armazenamento ou o registo falham.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function registar(
        AceitarConviteRequest $pedido,
        ServicoRegistoPorConvite $servicoRegisto,
    ): RedirectResponse {
        $dados = $pedido->validated();

        $caminhoFotografia =
            $this->guardarFotografia(
                $pedido,
            );

        try {
            $utilizador = $servicoRegisto->registar(
                codigoConvite: $dados['codigo_convite'],

                nome: $dados['nome'],

                email: $dados['email'],

                palavraPasse: $dados['palavra_passe'],

                caminhoFotografia: $caminhoFotografia,

                identificadoresPermissoesEmail: $dados['permissoes_email']
                    ?? [],
            );
        } catch (Throwable $excecao) {
            $this->eliminarFotografiaSemOcultarExcecao(
                $caminhoFotografia,
            );

            throw $excecao;
        }

        event(
            new Registered(
                $utilizador,
            ),
        );

        return redirect()
            ->route('login')
            ->with(
                'estado',
                'Registo concluído. Consulta o teu e-mail para confirmares a conta.',
            );
    }

    /**
     * Armazena a fotografia enviada no pedido.
     *
     * @param  AceitarConviteRequest  $pedido  Pedido validado.
     * @return string|null Caminho armazenado ou nulo.
     *
     * @throws RuntimeException Quando o ficheiro não pode ser armazenado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function guardarFotografia(
        AceitarConviteRequest $pedido,
    ): ?string {
        if (! $pedido->hasFile('fotografia')) {
            return null;
        }

        $fotografia = $pedido->file(
            'fotografia',
        );

        if (
            ! $fotografia instanceof UploadedFile
            || ! $fotografia->isValid()
        ) {
            throw new RuntimeException(
                'A fotografia enviada não é válida.',
            );
        }

        $caminho = $fotografia->store(
            self::DIRETORIO_FOTOGRAFIAS,
            self::DISCO_FOTOGRAFIAS,
        );

        if (
            ! is_string($caminho)
            || $caminho === ''
        ) {
            throw new RuntimeException(
                'Não foi possível guardar a fotografia.',
            );
        }

        return $caminho;
    }

    /**
     * Elimina uma fotografia sem substituir a exceção original do registo.
     *
     * Eventuais falhas durante a limpeza são reportadas, mas não interrompem
     * o relançamento da exceção que causou a falha do registo.
     *
     * @param  string|null  $caminho  Caminho da fotografia.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function eliminarFotografiaSemOcultarExcecao(
        ?string $caminho,
    ): void {
        if ($caminho === null) {
            return;
        }

        try {
            $eliminada = Storage::disk(
                self::DISCO_FOTOGRAFIAS,
            )->delete(
                $caminho,
            );

            if (! $eliminada) {
                report(
                    new RuntimeException(
                        sprintf(
                            'Não foi possível eliminar a fotografia órfã "%s".',
                            $caminho,
                        ),
                    ),
                );
            }
        } catch (Throwable $excecaoLimpeza) {
            report(
                $excecaoLimpeza,
            );
        }
    }
}
