<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;

/**
 * Define as regras de autorização aplicáveis à gestão dos convites.
 *
 * A consulta e a criação ficam reservadas a superadministradores com acesso
 * ativo. A revogação exige ainda que o convite esteja persistido e não tenha
 * sido utilizado.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class PoliticaConvite
{
    /**
     * Autoriza antecipadamente a consulta e a criação.
     *
     * A revogação não é autorizada antecipadamente porque depende também do
     * estado do convite afetado.
     *
     * O nome permanece em inglês por corresponder ao método especial
     * reconhecido pelo sistema de autorização do Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  string  $capacidade  Capacidade verificada.
     * @return bool|null Verdadeiro para operações gerais autorizadas ou nulo
     *                   para continuar a avaliação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
                'create',
            ],
            true,
        )
            ? true
            : null;
    }

    /**
     * Determina se o utilizador pode consultar os convites.
     *
     * O método `before` autoriza o superadministrador ativo. Todos os
     * restantes utilizadores recebem falso.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Falso para utilizadores não autorizados antecipadamente.
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
     * Determina se o utilizador pode criar convites.
     *
     * O nome permanece em inglês por corresponder à capacidade convencional
     * utilizada pelo Laravel.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return bool Falso para utilizadores não autorizados antecipadamente.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function create(
        Utilizador $utilizador,
    ): bool {
        return false;
    }

    /**
     * Determina se o utilizador pode revogar um convite.
     *
     * Uma revogação repetida continua autorizada para que a operação permaneça
     * idempotente. Convites utilizados nunca podem ser revogados.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  Convite  $convite  Convite afetado.
     * @return bool Verdadeiro quando a revogação pode ser iniciada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function revogar(
        Utilizador $utilizador,
        Convite $convite,
    ): bool {
        if (
            ! $utilizador->exists
            || ! $convite->exists
            || ! $utilizador->eSuperAdministrador()
            || ! $utilizador->temAcessoAtivo()
            || $convite->foiUtilizado()
        ) {
            return false;
        }

        return $this->possuiIdentificadorValido(
            $utilizador,
        )
            && $this->possuiIdentificadorValido(
                $convite,
            );
    }

    /**
     * Determina se um modelo possui um identificador inteiro positivo.
     *
     * @param  Utilizador|Convite  $modelo  Modelo recebido.
     * @return bool Verdadeiro quando o identificador é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function possuiIdentificadorValido(
        Utilizador|Convite $modelo,
    ): bool {
        $identificador =
            $modelo->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return true;
        }

        return is_string($identificador)
            && trim($identificador) !== ''
            && ctype_digit(
                trim(
                    $identificador,
                ),
            )
            && (int) trim(
                $identificador,
            ) > 0;
    }
}
