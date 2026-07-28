<?php

declare(strict_types=1);

namespace App\Servicos\Comunicacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Gere a validação e a sincronização das permissões de e-mail.
 *
 * Este serviço pode ser utilizado no registo, no perfil e na administração
 * dos utilizadores.
 *
 * Quando já existe uma transação ativa, o serviço participa nessa transação.
 * Caso contrário, inicia uma transação própria.
 *
 * @since 2.0.0
 *
 * @version 1.3.0
 */
final class ServicoPermissoesEmail
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * Esta repetição é utilizada apenas quando o serviço inicia a própria
     * transação.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Normaliza identificadores de permissões.
     *
     * Os valores são convertidos para inteiros positivos, deduplicados e
     * ordenados de forma crescente.
     *
     * O tipo `mixed` é aceite defensivamente para que valores inválidos
     * provoquem uma exceção de domínio controlada em vez de um erro de tipo.
     *
     * @param  array<int, mixed>  $identificadores  Valores recebidos.
     * @return list<int> Identificadores normalizados.
     *
     * @throws InvalidArgumentException Quando algum identificador não é
     *                                  válido.
     *
     * @since 2.0.0
     *
     * @version 1.2.0
     */
    public function normalizarIdentificadores(
        array $identificadores,
    ): array {
        $normalizados = [];

        foreach ($identificadores as $identificador) {
            $identificadorNormalizado =
                $this->normalizarIdentificador(
                    $identificador,
                );

            $normalizados[$identificadorNormalizado] =
                $identificadorNormalizado;
        }

        $normalizados =
            array_values(
                $normalizados,
            );

        sort(
            $normalizados,
            SORT_NUMERIC,
        );

        return $normalizados;
    }

    /**
     * Valida e sincroniza as permissões de um utilizador.
     *
     * Uma lista vazia remove todas as permissões atualmente atribuídas.
     *
     * @param  Utilizador  $utilizador  Utilizador persistido.
     * @param  array<int, mixed>  $identificadores  Permissões recebidas.
     * @return list<int> Identificadores efetivamente sincronizados.
     *
     * @throws InvalidArgumentException Quando o utilizador ou alguma permissão
     *                                  não são válidos.
     * @throws ModelNotFoundException Quando o utilizador deixou de existir.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     *
     * @version 1.3.0
     */
    public function sincronizar(
        Utilizador $utilizador,
        array $identificadores,
    ): array {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $utilizador,
            );

        $identificadoresNormalizados =
            $this->normalizarIdentificadores(
                $identificadores,
            );

        if (DB::transactionLevel() > 0) {
            return $this->sincronizarDentroDaTransacao(
                $identificadorUtilizador,
                $identificadoresNormalizados,
            );
        }

        return DB::transaction(
            fn (): array => $this->sincronizarDentroDaTransacao(
                $identificadorUtilizador,
                $identificadoresNormalizados,
            ),
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Sincroniza as permissões dentro de uma transação ativa.
     *
     * O utilizador é novamente obtido e bloqueado para impedir sincronizações
     * concorrentes sobre a mesma associação.
     *
     * @param  int  $identificadorUtilizador  Identificador do utilizador.
     * @param  list<int>  $identificadores  Identificadores normalizados.
     * @return list<int> Identificadores sincronizados.
     *
     * @throws InvalidArgumentException Quando alguma permissão não existe.
     * @throws ModelNotFoundException Quando o utilizador deixou de existir.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function sincronizarDentroDaTransacao(
        int $identificadorUtilizador,
        array $identificadores,
    ): array {
        $this->validarExistencia(
            $identificadores,
        );

        $utilizadorBloqueado =
            Utilizador::query()
                ->whereKey(
                    $identificadorUtilizador,
                )
                ->lockForUpdate()
                ->firstOrFail();

        $utilizadorBloqueado
            ->permissoesEmail()
            ->sync(
                $identificadores,
            );

        /*
         * Evita que uma relação anteriormente carregada continue a apresentar
         * dados desatualizados depois da sincronização.
         */
        $utilizadorBloqueado->unsetRelation(
            'permissoesEmail',
        );

        return $identificadores;
    }

    /**
     * Confirma que todas as permissões recebidas existem.
     *
     * @param  list<int>  $identificadores  Identificadores normalizados.
     *
     * @throws InvalidArgumentException Quando alguma permissão não existe.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function validarExistencia(
        array $identificadores,
    ): void {
        if ($identificadores === []) {
            return;
        }

        $identificadoresExistentes =
            PermissaoEmail::query()
                ->whereKey(
                    $identificadores,
                )
                ->pluck(
                    'id',
                )
                ->map(
                    static fn (
                        mixed $identificador,
                    ): int => (int) $identificador,
                )
                ->all();

        $identificadoresInexistentes =
            array_values(
                array_diff(
                    $identificadores,
                    $identificadoresExistentes,
                ),
            );

        if ($identificadoresInexistentes === []) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'As seguintes permissões de e-mail não existem: %s.',
                implode(
                    ', ',
                    $identificadoresInexistentes,
                ),
            ),
        );
    }

    /**
     * Normaliza um identificador individual.
     *
     * @param  mixed  $identificador  Identificador recebido.
     * @return int Identificador normalizado.
     *
     * @throws InvalidArgumentException Quando o identificador não é um inteiro
     *                                  positivo.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function normalizarIdentificador(
        mixed $identificador,
    ): int {
        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (is_string($identificador)) {
            $identificadorNormalizado =
                trim(
                    $identificador,
                );

            if (
                $identificadorNormalizado !== ''
                && ctype_digit(
                    $identificadorNormalizado,
                )
                && (int) $identificadorNormalizado > 0
            ) {
                return (int) $identificadorNormalizado;
            }
        }

        throw new InvalidArgumentException(
            'Foi recebida uma permissão de e-mail inválida.',
        );
    }

    /**
     * Obtém o identificador de um utilizador persistido.
     *
     * @param  Utilizador  $utilizador  Utilizador recebido.
     * @return int Identificador do utilizador.
     *
     * @throws InvalidArgumentException Quando o utilizador ainda não foi
     *                                  persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorUtilizador(
        Utilizador $utilizador,
    ): int {
        $identificador =
            $utilizador->getKey();

        if (
            ! $utilizador->exists
            || ! is_int($identificador)
            && ! is_string($identificador)
        ) {
            throw new InvalidArgumentException(
                'O utilizador deve estar persistido antes de sincronizar as permissões.',
            );
        }

        $identificadorNormalizado =
            is_int($identificador)
            ? $identificador
            : (
                ctype_digit(
                    $identificador,
                )
                ? (int) $identificador
                : 0
            );

        if ($identificadorNormalizado < 1) {
            throw new InvalidArgumentException(
                'O utilizador deve estar persistido antes de sincronizar as permissões.',
            );
        }

        return $identificadorNormalizado;
    }
}
