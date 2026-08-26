<?php

declare(strict_types=1);

namespace App\Servicos\Utilizadores;

use App\Enumeracoes\AcaoAcessoUtilizador;
use App\Models\Autenticacao\RegistoAcessoUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Notifications\NotificacaoEstadoAcessoUtilizador;
use App\Notifications\NotificacaoSessoesEncerradasUtilizador;
use App\ObjetosValor\Utilizadores\MotivoSuspensaoUtilizador;
use App\Servicos\Autenticacao\ServicoSessoesUtilizador;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Gere o acesso dos utilizadores à aplicação.
 *
 * A suspensão e a reativação são executadas em transações com bloqueios
 * pessimistas. O estado atual, o histórico, o token de autenticação
 * persistente e as sessões pertencem à mesma operação transacional.
 *
 * Apenas um superadministrador com acesso ativo pode executar alterações de
 * acesso. Um utilizador não pode suspender-se ou reativar-se a si próprio.
 * Quando o utilizador afetado também é superadministrador, estas regras
 * garantem que permanece pelo menos outro superadministrador com acesso ativo.
 *
 * @since 2.0.0
 */
final class ServicoAcessoUtilizadores
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO =
        3;

    /**
     * Comprimento do token de autenticação persistente.
     *
     * O comprimento coincide com o utilizado pelo Laravel na renovação dos
     * tokens da funcionalidade «lembrar-me».
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_TOKEN_PERSISTENTE =
        60;

    /**
     * Cria o serviço.
     *
     * @param  ServicoSessoesUtilizador  $servicoSessoesUtilizador  Serviço
     *                                                              das
     *                                                              sessões.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly ServicoSessoesUtilizador $servicoSessoesUtilizador,
    ) {}

    /**
     * Suspende o acesso de um utilizador.
     *
     * O motivo é validado antes da abertura da transação. Dentro da
     * transação, o utilizador afetado e o responsável são novamente obtidos
     * com bloqueio exclusivo.
     *
     * A operação atualiza o estado, cria o histórico, renova o
     * `remember_token` e elimina todas as sessões persistidas.
     *
     * @param  Utilizador  $utilizador  Utilizador a suspender.
     * @param  Utilizador  $responsavel  Superadministrador responsável.
     * @param  string  $motivo  Motivo da suspensão.
     * @param  CarbonInterface|null  $momento  Momento da suspensão.
     * @return Utilizador Utilizador suspenso.
     *
     * @throws InvalidArgumentException Quando os utilizadores ou o motivo não
     *                                  são válidos.
     * @throws ModelNotFoundException Quando algum utilizador deixou de
     *                                existir.
     * @throws DomainException Quando a operação viola uma regra de acesso.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     */
    public function suspender(
        Utilizador $utilizador,
        Utilizador $responsavel,
        string $motivo,
        ?CarbonInterface $momento = null,
    ): Utilizador {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $utilizador,
                'O utilizador a suspender',
            );

        $identificadorResponsavel =
            $this->obterIdentificadorUtilizador(
                $responsavel,
                'O responsável pela suspensão',
            );

        $this->garantirUtilizadoresDistintos(
            $identificadorUtilizador,
            $identificadorResponsavel,
        );

        $motivoSuspensao =
            MotivoSuspensaoUtilizador::deTexto(
                $motivo,
            );

        $momentoSuspensao =
            $this->normalizarMomento(
                $momento,
            );

        return DB::transaction(
            function () use (
                $identificadorUtilizador,
                $identificadorResponsavel,
                $motivoSuspensao,
                $momentoSuspensao,
            ): Utilizador {
                [
                    $utilizadorBloqueado,
                    $responsavelBloqueado,
                ] = $this->obterUtilizadoresBloqueados(
                    $identificadorUtilizador,
                    $identificadorResponsavel,
                );

                $this->garantirResponsavelAutorizado(
                    $responsavelBloqueado,
                );

                if ($utilizadorBloqueado->estaSuspenso()) {
                    throw new DomainException(
                        'O utilizador já se encontra suspenso.',
                    );
                }

                $utilizadorBloqueado->suspenso_em =
                    $momentoSuspensao;

                $utilizadorBloqueado->motivo_suspensao =
                    $motivoSuspensao->valor();

                $utilizadorBloqueado
                    ->responsavelSuspensao()
                    ->associate(
                        $responsavelBloqueado,
                    );

                $this->renovarTokenPersistente(
                    $utilizadorBloqueado,
                );

                $utilizadorBloqueado->saveOrFail();

                $this->registarAlteracaoAcesso(
                    utilizador: $utilizadorBloqueado,
                    responsavel: $responsavelBloqueado,
                    acao: AcaoAcessoUtilizador::Suspensao,
                    momento: $momentoSuspensao,
                    motivo: $motivoSuspensao,
                );

                $this
                    ->servicoSessoesUtilizador
                    ->encerrarTodas(
                        $utilizadorBloqueado,
                    );

                $utilizadorBloqueado->notify(
                    new NotificacaoEstadoAcessoUtilizador(
                        AcaoAcessoUtilizador::Suspensao,
                        $motivoSuspensao->valor(),
                    ),
                );

                return $utilizadorBloqueado;
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Reativa o acesso de um utilizador suspenso.
     *
     * A reativação elimina novamente quaisquer sessões que possam ter sido
     * escritas por pedidos concorrentes depois da suspensão. Nenhuma sessão é
     * criada ou restaurada; o utilizador terá de iniciar uma nova sessão.
     *
     * @param  Utilizador  $utilizador  Utilizador a reativar.
     * @param  Utilizador  $responsavel  Superadministrador responsável.
     * @param  CarbonInterface|null  $momento  Momento da reativação.
     * @return Utilizador Utilizador reativado.
     *
     * @throws InvalidArgumentException Quando algum utilizador não é válido.
     * @throws ModelNotFoundException Quando algum utilizador deixou de
     *                                existir.
     * @throws DomainException Quando a operação viola uma regra de acesso.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     */
    public function reativar(
        Utilizador $utilizador,
        Utilizador $responsavel,
        ?CarbonInterface $momento = null,
    ): Utilizador {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $utilizador,
                'O utilizador a reativar',
            );

        $identificadorResponsavel =
            $this->obterIdentificadorUtilizador(
                $responsavel,
                'O responsável pela reativação',
            );

        $this->garantirUtilizadoresDistintos(
            $identificadorUtilizador,
            $identificadorResponsavel,
        );

        $momentoReativacao =
            $this->normalizarMomento(
                $momento,
            );

        return DB::transaction(
            function () use (
                $identificadorUtilizador,
                $identificadorResponsavel,
                $momentoReativacao,
            ): Utilizador {
                [
                    $utilizadorBloqueado,
                    $responsavelBloqueado,
                ] = $this->obterUtilizadoresBloqueados(
                    $identificadorUtilizador,
                    $identificadorResponsavel,
                );

                $this->garantirResponsavelAutorizado(
                    $responsavelBloqueado,
                );

                if ($utilizadorBloqueado->temAcessoAtivo()) {
                    throw new DomainException(
                        'O utilizador já possui acesso ativo.',
                    );
                }

                $utilizadorBloqueado->suspenso_em =
                    null;

                $utilizadorBloqueado->motivo_suspensao =
                    null;

                $utilizadorBloqueado
                    ->responsavelSuspensao()
                    ->dissociate();

                $this->renovarTokenPersistente(
                    $utilizadorBloqueado,
                );

                $utilizadorBloqueado->saveOrFail();

                $this->registarAlteracaoAcesso(
                    utilizador: $utilizadorBloqueado,
                    responsavel: $responsavelBloqueado,
                    acao: AcaoAcessoUtilizador::Reativacao,
                    momento: $momentoReativacao,
                );

                $this
                    ->servicoSessoesUtilizador
                    ->encerrarTodas(
                        $utilizadorBloqueado,
                    );

                $utilizadorBloqueado->notify(
                    new NotificacaoEstadoAcessoUtilizador(
                        AcaoAcessoUtilizador::Reativacao,
                    ),
                );

                return $utilizadorBloqueado;
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Encerra todas as autenticações de um utilizador sem alterar o respetivo
     * estado de acesso.
     *
     * O token persistente é renovado e todas as sessões de base de dados são
     * eliminadas. A operação pode ser aplicada a utilizadores ativos ou
     * suspensos.
     *
     * @param  Utilizador  $utilizador  Utilizador afetado.
     * @param  Utilizador  $responsavel  Superadministrador responsável.
     * @return int Número de sessões eliminadas.
     *
     * @throws InvalidArgumentException Quando algum utilizador não é válido.
     * @throws ModelNotFoundException Quando algum utilizador deixou de
     *                                existir.
     * @throws DomainException Quando o responsável não está autorizado.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     */
    public function encerrarSessoes(
        Utilizador $utilizador,
        Utilizador $responsavel,
    ): int {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $utilizador,
                'O utilizador cujas sessões serão encerradas',
            );

        $identificadorResponsavel =
            $this->obterIdentificadorUtilizador(
                $responsavel,
                'O responsável pelo encerramento das sessões',
            );

        return DB::transaction(
            function () use (
                $identificadorUtilizador,
                $identificadorResponsavel,
            ): int {
                [
                    $utilizadorBloqueado,
                    $responsavelBloqueado,
                ] = $this->obterUtilizadoresBloqueados(
                    $identificadorUtilizador,
                    $identificadorResponsavel,
                );

                $this->garantirResponsavelAutorizado(
                    $responsavelBloqueado,
                );

                $this->renovarTokenPersistente(
                    $utilizadorBloqueado,
                );

                $utilizadorBloqueado->saveOrFail();

                $numeroSessoesEncerradas =
                    $this
                        ->servicoSessoesUtilizador
                        ->encerrarTodas(
                            $utilizadorBloqueado,
                        );

                $utilizadorBloqueado->notify(
                    new NotificacaoSessoesEncerradasUtilizador,
                );

                return $numeroSessoesEncerradas;
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Obtém e bloqueia o utilizador afetado e o responsável.
     *
     * Os identificadores são ordenados antes da consulta para manter uma
     * ordem de bloqueio determinística e reduzir a possibilidade de
     * interbloqueios.
     *
     * @param  int  $identificadorUtilizador  Identificador do utilizador.
     * @param  int  $identificadorResponsavel  Identificador do responsável.
     * @return array{0: Utilizador, 1: Utilizador} Utilizadores bloqueados.
     *
     * @throws ModelNotFoundException Quando algum utilizador deixou de
     *                                existir.
     *
     * @since 2.0.0
     */
    private function obterUtilizadoresBloqueados(
        int $identificadorUtilizador,
        int $identificadorResponsavel,
    ): array {
        $identificadores = array_values(
            array_unique([
                $identificadorUtilizador,
                $identificadorResponsavel,
            ]),
        );

        sort(
            $identificadores,
            SORT_NUMERIC,
        );

        $utilizadores = Utilizador::query()
            ->whereKey(
                $identificadores,
            )
            ->orderBy(
                'id',
            )
            ->lockForUpdate()
            ->get()
            ->keyBy(
                static fn (
                    Utilizador $utilizador,
                ): int => (int) $utilizador->getKey(),
            );

        $utilizadorBloqueado =
            $utilizadores->get(
                $identificadorUtilizador,
            );

        $responsavelBloqueado =
            $utilizadores->get(
                $identificadorResponsavel,
            );

        if (
            ! $utilizadorBloqueado instanceof Utilizador
            || ! $responsavelBloqueado instanceof Utilizador
        ) {
            $excecao =
                new ModelNotFoundException;

            $excecao->setModel(
                Utilizador::class,
                $identificadores,
            );

            throw $excecao;
        }

        return [
            $utilizadorBloqueado,
            $responsavelBloqueado,
        ];
    }

    /**
     * Confirma que o responsável pode gerir o acesso dos utilizadores.
     *
     * @param  Utilizador  $responsavel  Utilizador responsável.
     *
     * @throws DomainException Quando o responsável não é um
     *                         superadministrador com acesso ativo.
     *
     * @since 2.0.0
     */
    private function garantirResponsavelAutorizado(
        Utilizador $responsavel,
    ): void {
        if (
            $responsavel->eSuperAdministrador()
            && $responsavel->temAcessoAtivo()
        ) {
            return;
        }

        throw new DomainException(
            'A gestão do acesso exige um superadministrador com acesso ativo.',
        );
    }

    /**
     * Impede que um utilizador altere o próprio estado de acesso.
     *
     * @param  int  $identificadorUtilizador  Identificador afetado.
     * @param  int  $identificadorResponsavel  Identificador responsável.
     *
     * @throws DomainException Quando os identificadores coincidem.
     *
     * @since 2.0.0
     */
    private function garantirUtilizadoresDistintos(
        int $identificadorUtilizador,
        int $identificadorResponsavel,
    ): void {
        if (
            $identificadorUtilizador
            !== $identificadorResponsavel
        ) {
            return;
        }

        throw new DomainException(
            'Um utilizador não pode alterar o próprio estado de acesso.',
        );
    }

    /**
     * Cria um registo imutável da alteração de acesso.
     *
     * @param  Utilizador  $utilizador  Utilizador afetado.
     * @param  Utilizador  $responsavel  Responsável pela alteração.
     * @param  AcaoAcessoUtilizador  $acao  Ação executada.
     * @param  CarbonImmutable  $momento  Momento da alteração.
     * @param  MotivoSuspensaoUtilizador|null  $motivo  Motivo da suspensão.
     * @return RegistoAcessoUtilizador Registo criado.
     *
     * @since 2.0.0
     */
    private function registarAlteracaoAcesso(
        Utilizador $utilizador,
        Utilizador $responsavel,
        AcaoAcessoUtilizador $acao,
        CarbonImmutable $momento,
        ?MotivoSuspensaoUtilizador $motivo = null,
    ): RegistoAcessoUtilizador {
        $registo =
            new RegistoAcessoUtilizador;

        $registo
            ->utilizador()
            ->associate(
                $utilizador,
            );

        $registo->acao =
            $acao;

        $registo->motivo =
            $motivo?->valor();

        $registo
            ->responsavel()
            ->associate(
                $responsavel,
            );

        $registo->registado_em =
            $momento;

        $registo->saveOrFail();

        return $registo;
    }

    /**
     * Renova o token da autenticação persistente.
     *
     * @param  Utilizador  $utilizador  Utilizador bloqueado.
     *
     * @since 2.0.0
     */
    private function renovarTokenPersistente(
        Utilizador $utilizador,
    ): void {
        $utilizador->setRememberToken(
            Str::random(
                self::COMPRIMENTO_TOKEN_PERSISTENTE,
            ),
        );
    }

    /**
     * Obtém o identificador de um utilizador persistido.
     *
     * @param  Utilizador  $utilizador  Utilizador recebido.
     * @param  string  $descricao  Descrição utilizada nas mensagens.
     * @return int Identificador do utilizador.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido ou não possui um
     *                                  identificador válido.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorUtilizador(
        Utilizador $utilizador,
        string $descricao,
    ): int {
        if (! $utilizador->exists) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s deve estar persistido.',
                    $descricao,
                ),
            );
        }

        $identificador =
            $utilizador->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (! is_string($identificador)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s deve possuir um identificador válido.',
                    $descricao,
                ),
            );
        }

        $identificadorNormalizado = trim(
            $identificador,
        );

        if (
            $identificadorNormalizado === ''
            || ! ctype_digit(
                $identificadorNormalizado,
            )
            || (int) $identificadorNormalizado < 1
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s deve possuir um identificador válido.',
                    $descricao,
                ),
            );
        }

        return (int) $identificadorNormalizado;
    }

    /**
     * Normaliza o momento de uma alteração de acesso.
     *
     * @param  CarbonInterface|null  $momento  Momento recebido.
     * @return CarbonImmutable Momento imutável.
     *
     * @since 2.0.0
     */
    private function normalizarMomento(
        ?CarbonInterface $momento,
    ): CarbonImmutable {
        if ($momento === null) {
            return CarbonImmutable::now();
        }

        return CarbonImmutable::instance(
            $momento,
        );
    }
}
