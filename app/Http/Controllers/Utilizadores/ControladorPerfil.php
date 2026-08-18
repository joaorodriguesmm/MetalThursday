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
use LogicException;
use Throwable;

/**
 * Gere a apresentação e a atualização dos dados gerais do perfil.
 *
 * A alteração da palavra-passe e das permissões de e-mail pertence aos
 * respetivos controladores especializados.
 *
 * Quando o endereço de e-mail é alterado, a sessão atual é encerrada e o
 * utilizador tem de confirmar o novo endereço antes de voltar a autenticar-se.
 *
 * @since 1.0.0
 */
final class ControladorPerfil extends Controller
{
    /**
     * Nome do saco de erros utilizado pelo formulário do perfil.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const SACO_ERROS =
        'perfil';

    /**
     * Identificador da permissão que representa todas as comunicações.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const IDENTIFICADOR_PERMISSAO_TODAS =
        'todas_notificacoes';

    /**
     * Mensagem apresentada depois de atualizar os dados gerais do perfil.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_PERFIL_ATUALIZADO =
        'O perfil foi atualizado com sucesso.';

    /**
     * Mensagem apresentada quando o endereço já pertence a outra conta.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_EMAIL_INDISPONIVEL =
        'O endereço de e-mail já está associado a outro utilizador.';

    /**
     * Mensagem apresentada quando a nova verificação não pode ser enviada.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_VERIFICACAO_NAO_ENVIADA =
        'O perfil foi atualizado, mas não foi possível enviar a mensagem de verificação do novo endereço de e-mail.';

    /**
     * Mensagem apresentada depois de alterar o endereço de e-mail.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_EMAIL_ALTERADO =
        'O perfil foi atualizado. Verifica o novo endereço de e-mail antes de iniciares sessão novamente.';

    /**
     * Cria o controlador.
     *
     * @param  ServicoAtualizacaoPerfil  $servicoPerfil  Serviço responsável
     *                                                   pela atualização do
     *                                                   perfil.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoAtualizacaoPerfil $servicoPerfil,
    ) {}

    /**
     * Apresenta a página de edição do perfil.
     *
     * As permissões de e-mail são apresentadas pela ordem de domínio definida
     * nos respetivos registos.
     *
     * @param  Request  $pedido  Pedido autenticado.
     * @return View Página de edição do perfil.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     * @throws LogicException Quando uma permissão persistida não possui um
     *                        identificador válido.
     *
     * @since 1.0.0
     */
    public function editar(
        Request $pedido,
    ): View {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        $permissoesEmail =
            PermissaoEmail::query()
                ->select([
                    'id',
                    'identificador',
                    'nome',
                    'descricao',
                    'ordem',
                ])
                ->orderBy(
                    'ordem',
                )
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
     * A persistência dos dados e a substituição da fotografia são delegadas
     * ao serviço de atualização do perfil.
     *
     * Quando o endereço de e-mail é alterado, a sessão é terminada antes do
     * envio da nova notificação de verificação. Uma eventual falha do envio
     * não reverte os dados já persistidos.
     *
     * @param  AtualizarPerfilRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento após a atualização.
     *
     * @throws AuthenticationException Quando não existe autenticação válida.
     * @throws Throwable Quando a atualização do perfil falha devido a um erro
     *                   técnico inesperado.
     *
     * @since 1.0.0
     */
    public function atualizar(
        AtualizarPerfilRequest $pedido,
    ): RedirectResponse {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        $nome =
            $pedido->obterNome();

        $email =
            $pedido->obterEmail();

        $fotografia =
            $pedido->obterFotografia();

        try {
            $resultado =
                $this
                    ->servicoPerfil
                    ->atualizar(
                        utilizador: $utilizador,
                        nome: $nome,
                        email: $email,
                        fotografia: $fotografia,
                    );
        } catch (DomainException) {
            return to_route(
                'perfil.editar',
            )
                ->withInput([
                    'nome' => $nome,

                    'email' => $email,
                ])
                ->withErrors(
                    [
                        'email' => self::MENSAGEM_EMAIL_INDISPONIVEL,
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

        /*
         * A sessão deixa de ser válida assim que o endereço anteriormente
         * verificado é substituído.
         */
        $this->terminarSessao(
            $pedido,
        );

        if (
            ! $this->enviarNotificacaoVerificacao(
                $utilizadorAtualizado,
            )
        ) {
            return to_route(
                'login',
            )->with(
                'erro',
                self::MENSAGEM_VERIFICACAO_NAO_ENVIADA,
            );
        }

        return to_route(
            'login',
        )->with(
            'sucesso',
            self::MENSAGEM_EMAIL_ALTERADO,
        );
    }

    /**
     * Obtém os identificadores das permissões de e-mail do utilizador.
     *
     * @param  Utilizador  $utilizador  Utilizador consultado.
     * @return list<int> Identificadores normalizados e ordenados.
     *
     * @since 2.0.0
     */
    private function obterIdentificadoresPermissoesEmail(
        Utilizador $utilizador,
    ): array {
        $valores =
            $utilizador
                ->permissoesEmail()
                ->pluck(
                    'permissoes_email.id',
                )
                ->all();

        $identificadores =
            $this->normalizarListaIdentificadores(
                $valores,
            );

        sort(
            $identificadores,
            SORT_NUMERIC,
        );

        return $identificadores;
    }

    /**
     * Prepara as permissões de e-mail utilizadas pelo formulário.
     *
     * A ordem recebida da base de dados é preservada. A permissão global e as
     * restantes permissões seguem a coluna `ordem` do respetivo modelo.
     *
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  Collection<int, PermissaoEmail>  $permissoesEmail  Permissões
     *                                                            disponíveis.
     * @param  list<int>  $identificadoresAtuais  Permissões atualmente
     *                                            atribuídas.
     * @return list<array{
     *     identificador: int,
     *     nome: string,
     *     descricao: string|null,
     *     ePermissaoTodas: bool,
     *     selecionada: bool
     * }> Permissões preparadas.
     *
     * @throws LogicException Quando uma permissão persistida não possui um
     *                        identificador válido.
     *
     * @since 2.0.0
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

        $permissoesPreparadas = [];

        foreach ($permissoesEmail as $permissao) {
            $identificador =
                $this->obterIdentificadorPermissao(
                    $permissao,
                );

            $permissoesPreparadas[] = [
                'identificador' => $identificador,

                'nome' => $permissao->nome,

                'descricao' => $permissao->descricao,

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
     * Obtém o identificador persistido de uma permissão.
     *
     * @param  PermissaoEmail  $permissao  Permissão consultada.
     * @return int Identificador positivo.
     *
     * @throws LogicException Quando a permissão não possui um identificador
     *                        persistido válido.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorPermissao(
        PermissaoEmail $permissao,
    ): int {
        $identificador =
            $this->normalizarIdentificador(
                $permissao->getKey(),
            );

        if ($identificador === null) {
            throw new LogicException(
                'Foi encontrada uma permissão de e-mail sem um identificador persistido válido.',
            );
        }

        return $identificador;
    }

    /**
     * Determina se uma permissão representa todas as comunicações.
     *
     * @param  PermissaoEmail  $permissao  Permissão analisada.
     * @return bool Verdadeiro quando é a permissão global.
     *
     * @since 2.0.0
     */
    private function ePermissaoTodas(
        PermissaoEmail $permissao,
    ): bool {
        return $permissao->identificador
            === self::IDENTIFICADOR_PERMISSAO_TODAS;
    }

    /**
     * Envia a notificação de verificação do novo endereço de e-mail.
     *
     * A atualização do perfil já foi confirmada quando este método é chamado.
     * Uma falha no envio é reportada, mas não tenta reverter os dados.
     *
     * @param  Utilizador  $utilizador  Utilizador atualizado.
     * @return bool Verdadeiro quando a notificação foi enviada.
     *
     * @since 2.0.0
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
     * Termina a sessão autenticada, invalida os respetivos dados e regenera o
     * token CSRF.
     *
     * @param  Request  $pedido  Pedido HTTP.
     *
     * @since 2.0.0
     */
    private function terminarSessao(
        Request $pedido,
    ): void {
        Auth::guard(
            'sessao',
        )->logout();

        $pedido
            ->session()
            ->invalidate();

        $pedido
            ->session()
            ->regenerateToken();
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
                'É necessário iniciar sessão para atualizar o perfil.',
            );
        }

        return $utilizador;
    }

    /**
     * Normaliza um identificador positivo.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return int|null Identificador válido ou nulo.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificador(
        mixed $valor,
    ): ?int {
        if (is_int($valor)) {
            return $valor >= 1
                ? $valor
                : null;
        }

        if (! is_string($valor)) {
            return null;
        }

        $valorNormalizado =
            trim(
                $valor,
            );

        if (
            $valorNormalizado === ''
            || ! ctype_digit(
                $valorNormalizado,
            )
        ) {
            return null;
        }

        $identificador =
            filter_var(
                $valorNormalizado,
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 1,
                    ],
                ],
            );

        return $identificador === false
            ? null
            : $identificador;
    }

    /**
     * Normaliza uma lista de identificadores.
     *
     * Identificadores inválidos são ignorados apenas durante a reconstrução
     * visual do formulário. Valores repetidos são removidos.
     *
     * @param  mixed  $valores  Valores recebidos.
     * @return list<int> Identificadores positivos e únicos.
     *
     * @since 2.0.0
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
}
