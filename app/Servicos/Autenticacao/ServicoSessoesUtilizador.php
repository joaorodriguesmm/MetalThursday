<?php

declare(strict_types=1);

namespace App\Servicos\Autenticacao;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Gere as sessões persistidas dos utilizadores.
 *
 * O serviço atua diretamente sobre a tabela técnica `sessoes`, utilizada
 * pelo driver de sessões de base de dados da aplicação.
 *
 * A eliminação participa numa transação exterior quando o método é chamado
 * durante uma operação transacional de acesso.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoSessoesUtilizador
{
    /**
     * Nome da tabela técnica das sessões.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TABELA_SESSOES =
        'sessoes';

    /**
     * Encerra todas as sessões persistidas de um utilizador.
     *
     * @param  Utilizador  $utilizador  Utilizador cujas sessões serão
     *                                  encerradas.
     * @return int Número de sessões eliminadas.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido ou não possui um
     *                                  identificador válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function encerrarTodas(
        Utilizador $utilizador,
    ): int {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $utilizador,
            );

        return DB::table(
            self::TABELA_SESSOES,
        )
            ->where(
                'user_id',
                $identificadorUtilizador,
            )
            ->delete();
    }

    /**
     * Obtém o identificador de um utilizador persistido.
     *
     * @param  Utilizador  $utilizador  Utilizador recebido.
     * @return int Identificador do utilizador.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido ou não possui um
     *                                  identificador inteiro positivo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorUtilizador(
        Utilizador $utilizador,
    ): int {
        if (! $utilizador->exists) {
            throw new InvalidArgumentException(
                'O utilizador deve estar persistido para encerrar as sessões.',
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
                'O utilizador deve possuir um identificador válido.',
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
                'O utilizador deve possuir um identificador válido.',
            );
        }

        return (int) $identificadorNormalizado;
    }
}
