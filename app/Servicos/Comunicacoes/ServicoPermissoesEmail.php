<?php

declare(strict_types=1);

namespace App\Servicos\Comunicacoes;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Gere a validação e sincronização das permissões de e-mail.
 *
 * Este serviço pode ser reutilizado no registo, perfil e administração dos
 * utilizadores.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoPermissoesEmail
{
    /**
     * Normaliza identificadores de permissões.
     *
     * Os valores são convertidos para inteiros positivos, deduplicados e
     * ordenados.
     *
     * @param  array<int, int|string>  $identificadores  - Valores recebidos.
     * @return array<int, int> - Identificadores normalizados.
     *
     * @throws InvalidArgumentException Quando algum valor não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function normalizarIdentificadores(
        array $identificadores,
    ): array {
        $normalizados = [];

        foreach ($identificadores as $identificador) {
            if (
                is_int($identificador)
                && $identificador > 0
            ) {
                $normalizados[] = $identificador;

                continue;
            }

            if (
                is_string($identificador)
                && $identificador !== ''
                && ctype_digit($identificador)
                && (int) $identificador > 0
            ) {
                $normalizados[] = (int) $identificador;

                continue;
            }

            throw new InvalidArgumentException(
                'Foi recebida uma permissão de e-mail inválida.',
            );
        }

        $normalizados = array_values(
            array_unique($normalizados),
        );

        sort($normalizados);

        return $normalizados;
    }

    /**
     * Valida e sincroniza as permissões de um utilizador.
     *
     * Uma lista vazia remove todas as permissões atualmente atribuídas.
     *
     * @param  Utilizador  $utilizador  - Utilizador persistido.
     * @param  array<int, int|string>  $identificadores  - Permissões recebidas.
     * @return array<int, int> - Identificadores efetivamente sincronizados.
     *
     * @throws InvalidArgumentException Quando o utilizador ou uma permissão
     *                                  não são válidos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function sincronizar(
        Utilizador $utilizador,
        array $identificadores,
    ): array {
        if (
            ! $utilizador->exists
            || $utilizador->getKey() === null
        ) {
            throw new InvalidArgumentException(
                'O utilizador deve estar persistido antes de sincronizar as permissões.',
            );
        }

        $normalizados = $this->normalizarIdentificadores(
            $identificadores,
        );

        $this->validarExistencia($normalizados);

        $utilizador
            ->permissoesEmail()
            ->sync($normalizados);

        return $normalizados;
    }

    /**
     * Confirma que todas as permissões existem.
     *
     * @param  array<int, int>  $identificadores  - Identificadores normalizados.
     *
     * @throws InvalidArgumentException Quando alguma permissão não existe.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarExistencia(
        array $identificadores,
    ): void {
        if ($identificadores === []) {
            return;
        }

        $existentes = DB::table(
            'email_permissions',
        )
            ->whereIn('id', $identificadores)
            ->pluck('id')
            ->map(
                static fn (mixed $identificador): int => (int) $identificador,
            )
            ->all();

        $inexistentes = array_values(
            array_diff(
                $identificadores,
                $existentes,
            ),
        );

        if ($inexistentes === []) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'As seguintes permissões de e-mail não existem: %s.',
                implode(', ', $inexistentes),
            ),
        );
    }
}
