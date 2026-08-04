<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Autenticacao\Utilizador;

/**
 * Define as regras de autorização aplicáveis à gestão dos utilizadores.
 *
 * A consulta e a gestão dos utilizadores ficam exclusivamente reservadas a
 * superadministradores com acesso ativo.
 *
 * @since 2.0.0
 *
 * @version 4.0.0
 */
final class PoliticaUtilizador
{
    /**
     * Autoriza antecipadamente as operações de consulta do
     * superadministrador ativo.
     *
     * As alterações do acesso, das sessões e dos papéis não são autorizadas
     * antecipadamente porque dependem também do utilizador afetado e, quando
     * aplicável, do respetivo estado atual.
     *
     * O nome permanece em inglês por corresponder ao método especial
     * reconhecido pelo sistema de autorização do Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  string  $capacidade  Capacidade que está a ser verificada.
     * @return bool|null Verdadeiro para as consultas autorizadas ou nulo para
     *                   continuar a avaliação normal.
     *
     * @since 2.0.0
     *
     * @version 4.0.0
     */
    public function before(
        Utilizador $utilizador,
        string $capacidade,
    ): ?bool {
        if (
            ! $utilizador->eSuperAdministrador()
            || ! $utilizador->temAcessoAtivo()
        ) {
            return null;
        }

        return in_array(
            $capacidade,
            [
                'viewAny',
                'view',
            ],
            true,
        )
            ? true
            : null;
    }

    /**
     * Determina se o utilizador pode consultar a lista de utilizadores.
     *
     * O método `before` autoriza o superadministrador ativo. Todos os
     * restantes utilizadores recebem falso.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Falso para utilizadores que não são superadministradores
     *              ativos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function viewAny(
        Utilizador $utilizador,
    ): bool {
        return false;
    }

    /**
     * Determina se o utilizador pode consultar outro utilizador.
     *
     * O método `before` autoriza o superadministrador ativo. Todos os
     * restantes utilizadores recebem falso.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Utilizador  $utilizadorConsultado  Utilizador consultado.
     * @return bool Falso para utilizadores que não são superadministradores
     *              ativos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function view(
        Utilizador $utilizador,
        Utilizador $utilizadorConsultado,
    ): bool {
        return false;
    }

    /**
     * Determina se o utilizador pode suspender o acesso de outro utilizador.
     *
     * O responsável deve ser um superadministrador ativo, não pode alterar o
     * próprio acesso e o utilizador afetado deve possuir acesso ativo.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Utilizador  $utilizadorAfetado  Utilizador a suspender.
     * @return bool Verdadeiro quando a suspensão pode ser iniciada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function suspender(
        Utilizador $utilizador,
        Utilizador $utilizadorAfetado,
    ): bool {
        return $this->podeGerirUtilizador(
            $utilizador,
            $utilizadorAfetado,
        )
            && $utilizadorAfetado->temAcessoAtivo();
    }

    /**
     * Determina se o utilizador pode reativar o acesso de outro utilizador.
     *
     * O responsável deve ser um superadministrador ativo, não pode alterar o
     * próprio acesso e o utilizador afetado deve estar suspenso.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Utilizador  $utilizadorAfetado  Utilizador a reativar.
     * @return bool Verdadeiro quando a reativação pode ser iniciada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function reativar(
        Utilizador $utilizador,
        Utilizador $utilizadorAfetado,
    ): bool {
        return $this->podeGerirUtilizador(
            $utilizador,
            $utilizadorAfetado,
        )
            && $utilizadorAfetado->estaSuspenso();
    }

    /**
     * Determina se o utilizador pode encerrar as sessões de outro utilizador.
     *
     * A operação pode ser aplicada a utilizadores ativos ou suspensos. O
     * superadministrador não pode encerrar as próprias sessões através desta
     * área administrativa.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Utilizador  $utilizadorAfetado  Utilizador cujas sessões serão
     *                                         encerradas.
     * @return bool Verdadeiro quando as sessões podem ser encerradas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function encerrarSessoes(
        Utilizador $utilizador,
        Utilizador $utilizadorAfetado,
    ): bool {
        return $this->podeGerirUtilizador(
            $utilizador,
            $utilizadorAfetado,
        );
    }

    /**
     * Determina se o utilizador pode alterar o papel de outro utilizador.
     *
     * A autorização permite iniciar a operação para utilizadores ativos ou
     * suspensos. A validade da transição, a rejeição de alterações sem efeito
     * e a proteção dos superadministradores permanecem no serviço
     * transacional.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Utilizador  $utilizadorAfetado  Utilizador cujo papel será
     *                                         alterado.
     * @return bool Verdadeiro quando a alteração pode ser iniciada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function alterarPapel(
        Utilizador $utilizador,
        Utilizador $utilizadorAfetado,
    ): bool {
        return $this->podeGerirUtilizador(
            $utilizador,
            $utilizadorAfetado,
        );
    }

    /**
     * Determina se o responsável pode gerir o utilizador afetado.
     *
     * @param  Utilizador  $responsavel  Utilizador responsável.
     * @param  Utilizador  $utilizadorAfetado  Utilizador afetado.
     * @return bool Verdadeiro quando ambos estão persistidos, são distintos e
     *              o responsável é um superadministrador ativo.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function podeGerirUtilizador(
        Utilizador $responsavel,
        Utilizador $utilizadorAfetado,
    ): bool {
        if (
            ! $responsavel->exists
            || ! $utilizadorAfetado->exists
            || ! $responsavel->eSuperAdministrador()
            || ! $responsavel->temAcessoAtivo()
        ) {
            return false;
        }

        $identificadorResponsavel =
            $responsavel->getKey();

        $identificadorAfetado =
            $utilizadorAfetado->getKey();

        return is_numeric(
            $identificadorResponsavel,
        )
            && is_numeric(
                $identificadorAfetado,
            )
            && (int) $identificadorResponsavel
            !== (int) $identificadorAfetado;
    }
}
