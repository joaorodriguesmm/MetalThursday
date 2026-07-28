<?php

declare(strict_types=1);

namespace App\Http\Controllers\Utilizadores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilizadores\AtualizarPerfilRequest;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Servicos\Utilizadores\ServicoAtualizacaoPerfil;
use DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

/**
 * Gere a apresentação e a atualização dos dados gerais do perfil.
 *
 * A palavra-passe e as permissões de e-mail são atualizadas por
 * controladores próprios.
 *
 * @since 1.0.0
 *
 * @version 3.2.0
 */
final class ControladorPerfil extends Controller
{
    /**
     * Nome do saco de erros utilizado pelo formulário do perfil.
     *
     * @var string
     *
     * @since 3.2.0
     *
     * @version 1.0.0
     */
    private const SACO_ERROS = 'perfil';

    /**
     * Identificador da permissão que representa todas as notificações.
     *
     * @var string
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    private const IDENTIFICADOR_PERMISSAO_TODAS = 'todas';

    /**
     * Mensagem apresentada depois de atualizar os dados gerais do perfil.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private const MENSAGEM_PERFIL_ATUALIZADO =
        'O perfil foi atualizado com sucesso.';

    /**
     * Cria o controlador.
     *
     * @param  ServicoAtualizacaoPerfil  $servicoPerfil  Serviço responsável
     *                                                   pela atualização do
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
     * As permissões de e-mail são preparadas para apresentação antes de
     * serem enviadas para a view.
     *
     * @param  Request  $pedido  Pedido autenticado.
     * @return View Página de edição do perfil.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    public function editar(
        Request $pedido,
    ): View {
        $utilizador =
            $this->obterUtilizadorAutenticado(
                $pedido,
            );

        $permissoesEmail =
            PermissaoEmail::query()
                ->select([
                    'id',
                    'identificador',
                    'nome',
                    'descricao',
                ])
                ->orderBy(
                    'id',
                )
                ->get();

        $identificadoresPermissoesEmail =
            $this->obterIdentificadoresPermissoesEmail(
                $utilizador,
            );

        return view(
            'utilizadores.perfil.editar',
            [
                'utilizador' => $utilizador,

                'permissoesEmailFormulario' => $this->prepararPermissoesEmailFormulario(
                    $pedido,
                    $permissoesEmail,
                    $identificadoresPermissoesEmail,
                ),
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
     * @throws Throwable Quando a atualização do perfil falha devido a um erro
     *                   técnico inesperado.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    public function atualizar(
        AtualizarPerfilRequest $pedido,
    ): RedirectResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado(
                $pedido,
            );

        $nome =
            $pedido->obterNome();

        $email =
            $pedido->obterEmail();

        try {
            $resultado =
                $this
                    ->servicoPerfil
                    ->atualizar(
                        $utilizador,
                        $nome,
                        $email,
                        $pedido->obterFotografia(),
                    );
        } catch (DomainException $excecao) {
            return to_route(
                'perfil.editar',
            )
                ->withInput([
                    'nome' => $nome,

                    'email' => $email,
                ])
                ->withErrors(
                    [
                        'email' => $excecao->getMessage(),
                    ],
                    self::SACO_ERROS,
                );
        }

        if (! $resultado->emailFoiAlterado()) {
            return to_route(
                'perfil.editar',
            )->with(
                'sucesso',
                self::MENSAGEM_PERFIL_ATUALIZADO,
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
            'sucesso',
            'O perfil foi atualizado. Verifica o novo endereço de e-mail antes de iniciares sessão novamente.',
        );
    }

    /**
     * Obtém os identificadores das permissões de e-mail do utilizador.
     *
     * @param  Utilizador  $utilizador  Utilizador consultado.
     * @return array<int, int> Identificadores normalizados e ordenados.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadoresPermissoesEmail(
        Utilizador $utilizador,
    ): array {
        $identificadores =
            $utilizador
                ->permissoesEmail()
                ->pluck(
                    'permissoes_email.id',
                )
                ->all();

        $identificadoresNormalizados =
            $this->normalizarListaIdentificadores(
                $identificadores,
            );

        sort(
            $identificadoresNormalizados,
            SORT_NUMERIC,
        );

        return $identificadoresNormalizados;
    }

    /**
     * Prepara as permissões de e-mail utilizadas pelo formulário.
     *
     * A permissão global é apresentada primeiro. As restantes permissões são
     * ordenadas alfabeticamente pelo nome.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Collection<int, PermissaoEmail>  $permissoesEmail  Permissões
     *                                                            disponíveis.
     * @param  array<int, int>  $identificadoresAtuais  Permissões atualmente
     *                                                  atribuídas.
     * @return array<int, array{
     *     identificador: int,
     *     nome: string,
     *     descricao: string|null,
     *     ePermissaoTodas: bool,
     *     selecionada: bool
     * }> Permissões preparadas.
     *
     * @since 3.0.0
     *
     * @version 1.1.0
     */
    private function prepararPermissoesEmailFormulario(
        Request $pedido,
        Collection $permissoesEmail,
        array $identificadoresAtuais,
    ): array {
        $identificadoresSelecionados =
            $this->normalizarListaIdentificadores(
                $pedido->old(
                    'permissoes_email',
                    $identificadoresAtuais,
                ),
            );

        $permissoesOrdenadas =
            $permissoesEmail
                ->sort(
                    function (
                        PermissaoEmail $primeiraPermissao,
                        PermissaoEmail $segundaPermissao,
                    ): int {
                        $primeiraETodas =
                            $this->ePermissaoTodas(
                                $primeiraPermissao,
                            );

                        $segundaETodas =
                            $this->ePermissaoTodas(
                                $segundaPermissao,
                            );

                        if ($primeiraETodas !== $segundaETodas) {
                            return $primeiraETodas
                                ? -1
                                : 1;
                        }

                        $nomePrimeira =
                            $this->normalizarTexto(
                                $primeiraPermissao->nome,
                            )
                            ?? '';

                        $nomeSegunda =
                            $this->normalizarTexto(
                                $segundaPermissao->nome,
                            )
                            ?? '';

                        $comparacaoNomes =
                            strcasecmp(
                                $nomePrimeira,
                                $nomeSegunda,
                            );

                        if ($comparacaoNomes !== 0) {
                            return $comparacaoNomes;
                        }

                        return (int) $primeiraPermissao->getKey()
                            <=>
                            (int) $segundaPermissao->getKey();
                    },
                )
                ->values();

        $permissoesPreparadas = [];

        foreach ($permissoesOrdenadas as $permissao) {
            $identificador =
                $this->normalizarIdentificador(
                    $permissao->getKey(),
                );

            $nome =
                $this->normalizarTexto(
                    $permissao->nome,
                );

            if (
                $identificador === null
                || $nome === null
            ) {
                continue;
            }

            $permissoesPreparadas[] = [
                'identificador' => $identificador,

                'nome' => $nome,

                'descricao' => $this->normalizarTexto(
                    $permissao->descricao,
                ),

                'ePermissaoTodas' => $this->ePermissaoTodas(
                    $permissao,
                ),

                'selecionada' => in_array(
                    $identificador,
                    $identificadoresSelecionados,
                    true,
                ),
            ];
        }

        return $permissoesPreparadas;
    }

    /**
     * Determina se uma permissão representa todas as notificações.
     *
     * @param  PermissaoEmail  $permissao  Permissão analisada.
     * @return bool Verdadeiro quando é a permissão global.
     *
     * @since 3.0.0
     *
     * @version 1.1.0
     */
    private function ePermissaoTodas(
        PermissaoEmail $permissao,
    ): bool {
        $identificador =
            $this->normalizarTexto(
                $permissao->identificador,
            );

        return $identificador !== null
            && mb_strtolower(
                $identificador,
            ) === self::IDENTIFICADOR_PERMISSAO_TODAS;
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
        Auth::guard(
            'web',
        )->logout();

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
        $utilizador =
            $pedido->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'É necessário iniciar sessão para atualizar o perfil.',
            );
        }

        return $utilizador;
    }

    /**
     * Normaliza um identificador.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return int|null Identificador válido ou nulo.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarIdentificador(
        mixed $valor,
    ): ?int {
        if (
            ! is_int($valor)
            && ! is_string($valor)
        ) {
            return null;
        }

        $identificador =
            trim(
                (string) $valor,
            );

        if (
            $identificador === ''
            || ! ctype_digit(
                $identificador,
            )
            || (int) $identificador < 1
        ) {
            return null;
        }

        return (int) $identificador;
    }

    /**
     * Normaliza uma lista de identificadores.
     *
     * Identificadores inválidos são ignorados e valores repetidos são
     * removidos.
     *
     * @param  mixed  $valores  Valores recebidos.
     * @return array<int, int> Identificadores únicos.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarListaIdentificadores(
        mixed $valores,
    ): array {
        if (! is_array($valores)) {
            return [];
        }

        $identificadores = [];

        foreach ($valores as $valor) {
            $identificador =
                $this->normalizarIdentificador(
                    $valor,
                );

            if ($identificador === null) {
                continue;
            }

            $identificadores[$identificador] =
                $identificador;
        }

        return array_values(
            $identificadores,
        );
    }

    /**
     * Normaliza um texto opcional.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return string|null Texto normalizado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function normalizarTexto(
        mixed $valor,
    ): ?string {
        if (! is_string($valor)) {
            return null;
        }

        $texto =
            trim(
                $valor,
            );

        return $texto !== ''
            ? $texto
            : null;
    }
}
