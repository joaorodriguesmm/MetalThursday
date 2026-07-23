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
 * @version 1.1.0
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
     * São aceites valores booleanos, inteiros binários e representações
     * textuais utilizadas habitualmente em formulários e filtros.
     *
     * A comparação textual ignora espaços adicionais e diferenças entre
     * letras maiúsculas e minúsculas.
     *
     * @param  mixed  $valor  Valor a converter.
     * @return self|null Resposta correspondente ou nula quando o valor não é
     *                   reconhecido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public static function tentarCriar(
        mixed $valor,
    ): ?self {
        if (is_bool($valor)) {
            return self::deBooleano(
                $valor,
            );
        }

        if (is_int($valor)) {
            return match ($valor) {
                1 => self::Sim,
                0 => self::Nao,
                default => null,
            };
        }

        if (! is_string($valor)) {
            return null;
        }

        $valorNormalizado = mb_strtolower(
            trim($valor),
        );

        return match ($valorNormalizado) {
            self::Sim->value,
            'yes',
            'true',
            '1' => self::Sim,

            self::Nao->value,
            'não',
            'no',
            'false',
            '0' => self::Nao,

            default => null,
        };
    }

    /**
     * Cria uma resposta binária a partir de um valor booleano.
     *
     * @param  bool  $valor  Valor booleano.
     * @return self Resposta binária correspondente.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     *
     * @version 1.0.0
     */
    public function paraBooleano(): bool
    {
        return $this === self::Sim;
    }
}
