<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa uma alteração administrativa do acesso de um utilizador.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
enum AcaoAcessoUtilizador: string
{
    /**
     * Suspensão do acesso à aplicação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case Suspensao = 'suspensao';

    /**
     * Reativação do acesso à aplicação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case Reativacao = 'reativacao';

    /**
     * Tenta criar uma ação a partir de um valor textual.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return self|null Ação correspondente ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function tentarCriar(
        mixed $valor,
    ): ?self {
        if (! is_string($valor)) {
            return null;
        }

        return self::tryFrom(
            mb_strtolower(
                trim(
                    $valor,
                ),
            ),
        );
    }

    /**
     * Obtém a etiqueta apresentada ao utilizador.
     *
     * @return string Etiqueta da ação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Suspensao => 'Suspensão',
            self::Reativacao => 'Reativação',
        };
    }

    /**
     * Determina se a ação corresponde a uma suspensão.
     *
     * @return bool Verdadeiro apenas para uma suspensão.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function eSuspensao(): bool
    {
        return $this === self::Suspensao;
    }

    /**
     * Determina se a ação corresponde a uma reativação.
     *
     * @return bool Verdadeiro apenas para uma reativação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function eReativacao(): bool
    {
        return $this === self::Reativacao;
    }
}
