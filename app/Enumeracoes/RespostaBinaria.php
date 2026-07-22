<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa uma resposta binária de sim ou não.
 *
 * Esta enumeração pode ser reutilizada em filtros, formulários, configurações
 * e outros pontos da aplicação que recebam uma escolha binária.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
enum RespostaBinaria: string
{
    /**
     * Resposta afirmativa.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case Sim = 'sim';

    /**
     * Resposta negativa.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case Nao = 'nao';

    /**
     * Tenta criar uma resposta binária a partir de um valor recebido.
     *
     * Os valores `yes` e `no` são temporariamente aceites para garantir
     * compatibilidade com a interface da versão 1.0.0.
     *
     * @param  mixed  $valor  - Valor a converter.
     * @return self|null - Resposta correspondente ou null quando o valor não
     *                   é reconhecido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function tentarCriar(mixed $valor): ?self
    {
        if (! is_string($valor)) {
            return null;
        }

        return match ($valor) {
            self::Sim->value,
            'yes' => self::Sim,

            self::Nao->value,
            'no' => self::Nao,

            default => null,
        };
    }

    /**
     * Converte a resposta num valor booleano.
     *
     * @return bool - Verdadeiro para sim e falso para não.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function paraBooleano(): bool
    {
        return $this === self::Sim;
    }
}
