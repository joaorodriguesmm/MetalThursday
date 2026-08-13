<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa uma resposta binária de sim ou não.
 *
 * A interpretação de valores recebidos externamente é estrita. Apenas os
 * valores textuais públicos `sim` e `nao` são aceites por
 * {@see tentarCriar()}.
 *
 * Conversões internas de valores booleanos devem ser efetuadas
 * explicitamente através de {@see deBooleano()}.
 *
 * @since 2.0.0
 */
enum RespostaBinaria: string
{
    /**
     * Resposta afirmativa.
     *
     * @since 2.0.0
     */
    case Sim = 'sim';

    /**
     * Resposta negativa.
     *
     * @since 2.0.0
     */
    case Nao = 'nao';

    /**
     * Tenta criar uma resposta a partir de um valor textual.
     *
     * Apenas os valores públicos definidos pela própria enumeração são
     * aceites. Booleanos, inteiros e representações textuais alternativas não
     * são convertidos implicitamente.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return self|null Resposta correspondente ou nula.
     *
     * @since 2.0.0
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
     * Cria uma resposta a partir de um valor booleano explícito.
     *
     * @param  bool  $valor  Valor booleano.
     * @return self Resposta correspondente.
     *
     * @since 2.0.0
     */
    public static function deBooleano(
        bool $valor,
    ): self {
        return $valor
            ? self::Sim
            : self::Nao;
    }

    /**
     * Converte a resposta num valor booleano.
     *
     * @return bool Verdadeiro para sim e falso para não.
     *
     * @since 2.0.0
     */
    public function paraBooleano(): bool
    {
        return $this === self::Sim;
    }

    /**
     * Obtém a etiqueta apresentada ao utilizador.
     *
     * @return string Etiqueta da resposta.
     *
     * @since 2.0.0
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Sim => 'Sim',
            self::Nao => 'Não',
        };
    }
}
