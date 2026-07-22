<?php

declare(strict_types=1);

namespace App\Servicos\Autenticacao;

use App\Excecoes\Autenticacao\NovaPalavraPasseIgualAAtual;
use App\Excecoes\Autenticacao\PalavraPasseAtualIncorreta;
use App\Models\Autenticacao\Utilizador;
use App\Regras\Autenticacao\PoliticaPalavraPasse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use SensitiveParameter;
use Throwable;

/**
 * Gere a alteração segura da palavra-passe de um utilizador.
 *
 * A linha do utilizador é bloqueada durante a operação para impedir alterações
 * concorrentes. A palavra-passe atual é novamente confirmada no serviço,
 * protegendo chamadas provenientes de outros pontos de entrada e alterações
 * ocorridas entre a validação HTTP e a persistência.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoAtualizacaoPalavraPasse
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Atualiza a palavra-passe do utilizador.
     *
     * @param  Utilizador  $utilizador  - Utilizador autenticado.
     * @param  string  $palavraPasseAtual  - Palavra-passe atual em texto simples.
     * @param  string  $novaPalavraPasse  - Nova palavra-passe em texto simples.
     * @return Utilizador - Utilizador atualizado e bloqueado durante a
     *                    operação.
     *
     * @throws InvalidArgumentException Quando o utilizador ou a nova
     *                                  palavra-passe não são válidos.
     * @throws PalavraPasseAtualIncorreta Quando a palavra-passe atual não
     *                                    corresponde ao valor persistido.
     * @throws NovaPalavraPasseIgualAAtual Quando a nova palavra-passe coincide
     *                                     com a atual.
     * @throws ModelNotFoundException Quando o utilizador deixou de existir.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function atualizar(
        Utilizador $utilizador,
        #[SensitiveParameter]
        string $palavraPasseAtual,
        #[SensitiveParameter]
        string $novaPalavraPasse,
    ): Utilizador {
        if (
            ! $utilizador->exists
            || $utilizador->getKey() === null
        ) {
            throw new InvalidArgumentException(
                'O utilizador deve estar persistido para alterar a palavra-passe.',
            );
        }

        PoliticaPalavraPasse::validar(
            $novaPalavraPasse,
        );

        return DB::transaction(
            function () use (
                $utilizador,
                $palavraPasseAtual,
                $novaPalavraPasse,
            ): Utilizador {
                $utilizadorBloqueado = Utilizador::query()
                    ->whereKey($utilizador->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $hashAtual = $utilizadorBloqueado
                    ->getAuthPassword();

                if (
                    ! is_string($hashAtual)
                    || $hashAtual === ''
                    || ! Hash::check(
                        $palavraPasseAtual,
                        $hashAtual,
                    )
                ) {
                    throw new PalavraPasseAtualIncorreta(
                        'A palavra-passe atual não está correta.',
                    );
                }

                if (
                    Hash::check(
                        $novaPalavraPasse,
                        $hashAtual,
                    )
                ) {
                    throw new NovaPalavraPasseIgualAAtual(
                        'A nova palavra-passe deve ser diferente da atual.',
                    );
                }

                /*
                 * O cast `hashed` do modelo Utilizador aplica a hash antes da
                 * persistência. A palavra-passe em texto simples nunca é
                 * guardada na base de dados.
                 */
                $utilizadorBloqueado->password =
                    $novaPalavraPasse;

                $utilizadorBloqueado->saveOrFail();

                return $utilizadorBloqueado;
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }
}
