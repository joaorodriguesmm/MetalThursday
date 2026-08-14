<?php

declare(strict_types=1);

namespace App\Servicos\Utilizadores;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\RegistoPapelUtilizador;
use App\Models\Autenticacao\Utilizador;
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
 * Gere as alterações dos papéis dos utilizadores.
 *
 * A alteração é executada numa transação com bloqueios pessimistas. O papel
 * atual, o histórico, o token de autenticação persistente e as sessões
 * pertencem à mesma operação transacional.
 *
 * Apenas um superadministrador com acesso ativo pode alterar papéis. Um
 * utilizador não pode alterar o próprio papel. Quando o utilizador afetado
 * também é superadministrador, estas regras garantem que permanece pelo menos
 * outro superadministrador com acesso ativo.
 *
 * @since 2.0.0
 */
final class ServicoPapeisUtilizadores
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
     * Altera o papel de um utilizador.
     *
     * Dentro da transação, o utilizador afetado e o responsável são novamente
     * obtidos com bloqueio exclusivo. A operação altera o papel, cria o
     * histórico, renova o `remember_token` e elimina todas as sessões
     * persistidas do utilizador afetado.
     *
     * @param  Utilizador  $utilizador  Utilizador afetado.
     * @param  Utilizador  $responsavel  Superadministrador responsável.
     * @param  PapelUtilizador  $papelNovo  Novo papel.
     * @param  CarbonInterface|null  $momento  Momento da alteração.
     * @return Utilizador Utilizador atualizado.
     *
     * @throws InvalidArgumentException Quando algum utilizador não é válido.
     * @throws ModelNotFoundException Quando algum utilizador deixou de
     *                                existir.
     * @throws DomainException Quando a operação viola uma regra dos papéis.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     */
    public function alterar(
        Utilizador $utilizador,
        Utilizador $responsavel,
        PapelUtilizador $papelNovo,
        ?CarbonInterface $momento = null,
    ): Utilizador {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $utilizador,
                'O utilizador cujo papel será alterado',
            );

        $identificadorResponsavel =
            $this->obterIdentificadorUtilizador(
                $responsavel,
                'O responsável pela alteração do papel',
            );

        $this->garantirUtilizadoresDistintos(
            $identificadorUtilizador,
            $identificadorResponsavel,
        );

        $momentoAlteracao =
            $this->normalizarMomento(
                $momento,
            );

        return DB::transaction(
            function () use (
                $identificadorUtilizador,
                $identificadorResponsavel,
                $papelNovo,
                $momentoAlteracao,
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

                $papelAnterior =
                    $utilizadorBloqueado->papel;

                if ($papelAnterior === $papelNovo) {
                    throw new DomainException(
                        'O utilizador já possui o papel selecionado.',
                    );
                }

                $utilizadorBloqueado->papel =
                    $papelNovo;

                $this->renovarTokenPersistente(
                    $utilizadorBloqueado,
                );

                $utilizadorBloqueado->saveOrFail();

                $this->registarAlteracaoPapel(
                    utilizador: $utilizadorBloqueado,
                    responsavel: $responsavelBloqueado,
                    papelAnterior: $papelAnterior,
                    papelNovo: $papelNovo,
                    momento: $momentoAlteracao,
                );

                $this
                    ->servicoSessoesUtilizador
                    ->encerrarTodas(
                        $utilizadorBloqueado,
                    );

                return $utilizadorBloqueado;
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
     * Confirma que o responsável pode gerir os papéis dos utilizadores.
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
            'A gestão dos papéis exige um superadministrador com acesso ativo.',
        );
    }

    /**
     * Impede que um utilizador altere o próprio papel.
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
            'Um utilizador não pode alterar o próprio papel.',
        );
    }

    /**
     * Cria um registo imutável da alteração do papel.
     *
     * @param  Utilizador  $utilizador  Utilizador afetado.
     * @param  Utilizador  $responsavel  Responsável pela alteração.
     * @param  PapelUtilizador  $papelAnterior  Papel anterior.
     * @param  PapelUtilizador  $papelNovo  Novo papel.
     * @param  CarbonImmutable  $momento  Momento da alteração.
     * @return RegistoPapelUtilizador Registo criado.
     *
     * @since 2.0.0
     */
    private function registarAlteracaoPapel(
        Utilizador $utilizador,
        Utilizador $responsavel,
        PapelUtilizador $papelAnterior,
        PapelUtilizador $papelNovo,
        CarbonImmutable $momento,
    ): RegistoPapelUtilizador {
        $registo =
            new RegistoPapelUtilizador;

        $registo
            ->utilizador()
            ->associate(
                $utilizador,
            );

        $registo->papel_anterior =
            $papelAnterior;

        $registo->papel_novo =
            $papelNovo;

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
     * Normaliza o momento de uma alteração do papel.
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
