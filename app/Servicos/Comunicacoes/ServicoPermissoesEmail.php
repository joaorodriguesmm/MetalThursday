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
 * O serviço pode ser utilizado durante o registo, a atualização do perfil ou
 * a administração dos utilizadores.
 *
 * Quando já existe uma transação ativa, participa nessa transação. Caso
 * contrário, inicia uma transação própria.
 *
 * @since 2.0.0
 */
final class ServicoPermissoesEmail
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * As tentativas são aplicadas apenas quando o serviço inicia a sua própria
     * transação.
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Normaliza identificadores de permissões.
     *
     * Os valores são convertidos para inteiros positivos, deduplicados e
     * ordenados numericamente.
     *
     * @param  array<array-key, mixed>  $identificadores  Valores recebidos.
     * @return list<int> Identificadores normalizados.
     *
     * @throws InvalidArgumentException Quando algum identificador não é
     *                                  válido.
     *
     * @since 2.0.0
     */
    public function normalizarIdentificadores(
        array $identificadores,
    ): array {
        /** @var array<int, int> $identificadoresNormalizados */
        $identificadoresNormalizados = [];

        foreach ($identificadores as $identificador) {
            $identificadorNormalizado =
                $this->normalizarIdentificador(
                    $identificador,
                );

            $identificadoresNormalizados[$identificadorNormalizado] = $identificadorNormalizado;
        }

        $resultado = array_values(
            $identificadoresNormalizados,
        );

        sort(
            $resultado,
            SORT_NUMERIC,
        );

        return $resultado;
    }

    /**
     * Valida e sincroniza as permissões de um utilizador.
     *
     * Uma lista vazia remove todas as permissões atualmente atribuídas.
     *
     * Os valores recebidos representam os identificadores numéricos das
     * permissões. O identificador textual estável e a ordem de apresentação
     * pertencem ao modelo {@see PermissaoEmail}.
     *
     * @param  Utilizador  $utilizador  Utilizador persistido.
     * @param  array<array-key, mixed>  $identificadores  Permissões recebidas.
     * @return list<int> Identificadores efetivamente sincronizados.
     *
     * @throws InvalidArgumentException Quando o utilizador ou alguma permissão
     *                                  não são válidos.
     * @throws ModelNotFoundException Quando o utilizador deixou de existir.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
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
            $resultado =
                $this->sincronizarDentroDaTransacao(
                    $identificadorUtilizador,
                    $identificadoresNormalizados,
                );
        } else {
            $resultado = DB::transaction(
                fn (): array => $this->sincronizarDentroDaTransacao(
                    $identificadorUtilizador,
                    $identificadoresNormalizados,
                ),
                self::TENTATIVAS_TRANSACAO,
            );
        }

        /*
         * O objeto originalmente recebido pode possuir a relação carregada.
         * Essa coleção deixou de representar o estado atual depois da
         * sincronização.
         */
        $utilizador->unsetRelation(
            'permissoesEmail',
        );

        return $resultado;
    }

    /**
     * Sincroniza as permissões dentro de uma transação ativa.
     *
     * O utilizador é novamente obtido e bloqueado para serializar
     * sincronizações concorrentes sobre as mesmas associações.
     *
     * As permissões selecionadas também são bloqueadas enquanto a sua
     * existência é validada, impedindo que sejam eliminadas antes da escrita
     * na tabela intermédia.
     *
     * @param  int  $identificadorUtilizador  Identificador do utilizador.
     * @param  list<int>  $identificadores  Identificadores normalizados.
     * @return list<int> Identificadores sincronizados.
     *
     * @throws InvalidArgumentException Quando alguma permissão não existe.
     * @throws ModelNotFoundException Quando o utilizador deixou de existir.
     *
     * @since 2.0.0
     */
    private function sincronizarDentroDaTransacao(
        int $identificadorUtilizador,
        array $identificadores,
    ): array {
        $utilizadorBloqueado =
            Utilizador::query()
                ->whereKey(
                    $identificadorUtilizador,
                )
                ->lockForUpdate()
                ->firstOrFail();

        $this->validarExistencia(
            $identificadores,
        );

        $utilizadorBloqueado
            ->permissoesEmail()
            ->sync(
                $identificadores,
            );

        $utilizadorBloqueado->unsetRelation(
            'permissoesEmail',
        );

        return $identificadores;
    }

    /**
     * Confirma que todas as permissões recebidas existem.
     *
     * Os registos encontrados ficam bloqueados até ao final da transação
     * atual.
     *
     * @param  list<int>  $identificadores  Identificadores normalizados.
     *
     * @throws InvalidArgumentException Quando alguma permissão não existe.
     *
     * @since 2.0.0
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
                ->lockForUpdate()
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
     * São aceites inteiros positivos e representações textuais compostas
     * exclusivamente por algarismos.
     *
     * @param  mixed  $identificador  Identificador recebido.
     * @return int Identificador normalizado.
     *
     * @throws InvalidArgumentException Quando o identificador não é um inteiro
     *                                  positivo.
     *
     * @since 2.0.0
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
            $identificadorNormalizado = trim(
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
     *                                  persistido ou não possui um
     *                                  identificador válido.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorUtilizador(
        Utilizador $utilizador,
    ): int {
        if (! $utilizador->exists) {
            throw new InvalidArgumentException(
                'O utilizador deve estar persistido antes de sincronizar as permissões.',
            );
        }

        $identificador = $utilizador->getKey();

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
