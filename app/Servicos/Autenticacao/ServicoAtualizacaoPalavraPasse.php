<?php

declare(strict_types=1);

namespace App\Servicos\Autenticacao;

use App\Excecoes\Autenticacao\NovaPalavraPasseIgualAAtual;
use App\Excecoes\Autenticacao\PalavraPasseAtualIncorreta;
use App\Models\Autenticacao\Utilizador;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
 * Depois da alteração, o token de autenticação persistente é renovado para
 * invalidar autenticações anteriores baseadas na funcionalidade
 * «lembrar-me».
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
final class ServicoAtualizacaoPalavraPasse
{
    /**
     * Número máximo de tentativas perante conflitos transitórios da transação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Comprimento do novo token de autenticação persistente.
     *
     * O valor coincide com o comprimento utilizado pelo Laravel ao renovar
     * tokens da funcionalidade «lembrar-me».
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_TOKEN_PERSISTENTE = 60;

    /**
     * Atualiza a palavra-passe do utilizador.
     *
     * A política da nova palavra-passe é validada antes da abertura da
     * transação. Dentro da transação, o utilizador é novamente obtido com
     * bloqueio exclusivo e a palavra-passe atual é confirmada contra o valor
     * persistido nesse momento.
     *
     * A nova palavra-passe é atribuída em texto simples ao modelo. O cast
     * `hashed` de {@see Utilizador} aplica o hash antes da persistência.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  string  $palavraPasseAtual  Palavra-passe atual em texto simples.
     * @param  string  $novaPalavraPasse  Nova palavra-passe em texto simples.
     * @return Utilizador Utilizador atualizado.
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
     * @version 2.0.0
     */
    public function atualizar(
        Utilizador $utilizador,
        #[SensitiveParameter]
        string $palavraPasseAtual,
        #[SensitiveParameter]
        string $novaPalavraPasse,
    ): Utilizador {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $utilizador,
            );

        RequisitosPalavraPasse::validar(
            $novaPalavraPasse,
        );

        return DB::transaction(
            function () use (
                $identificadorUtilizador,
                $palavraPasseAtual,
                $novaPalavraPasse,
            ): Utilizador {
                $utilizadorBloqueado =
                    Utilizador::query()
                        ->whereKey(
                            $identificadorUtilizador,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $hashAtual =
                    $this->obterHashPalavraPasseAtual(
                        $utilizadorBloqueado,
                    );

                $this->validarPalavraPasseAtual(
                    $palavraPasseAtual,
                    $hashAtual,
                );

                $this->validarNovaPalavraPasseDiferente(
                    $novaPalavraPasse,
                    $hashAtual,
                );

                /*
                 * O cast `hashed` do modelo aplica o hash antes da
                 * persistência. A palavra-passe em texto simples não é
                 * guardada na base de dados.
                 */
                $utilizadorBloqueado->password =
                    $novaPalavraPasse;

                /*
                 * A renovação invalida os cookies persistentes anteriormente
                 * emitidos através da funcionalidade «lembrar-me».
                 */
                $utilizadorBloqueado->setRememberToken(
                    Str::random(
                        self::COMPRIMENTO_TOKEN_PERSISTENTE,
                    ),
                );

                $utilizadorBloqueado->saveOrFail();

                return $utilizadorBloqueado;
            },
            self::TENTATIVAS_TRANSACAO,
        );
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
     * @version 2.0.0
     */
    private function obterIdentificadorUtilizador(
        Utilizador $utilizador,
    ): int {
        if (! $utilizador->exists) {
            throw new InvalidArgumentException(
                'O utilizador deve estar persistido para alterar a palavra-passe.',
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

    /**
     * Obtém o hash persistido da palavra-passe atual.
     *
     * @param  Utilizador  $utilizador  Utilizador bloqueado.
     * @return string Hash persistido.
     *
     * @throws PalavraPasseAtualIncorreta Quando o utilizador não possui um
     *                                    hash utilizável.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function obterHashPalavraPasseAtual(
        Utilizador $utilizador,
    ): string {
        $hashAtual =
            $utilizador->getAuthPassword();

        if (
            ! is_string($hashAtual)
            || trim($hashAtual) === ''
        ) {
            throw new PalavraPasseAtualIncorreta(
                'A palavra-passe atual não está correta.',
            );
        }

        return $hashAtual;
    }

    /**
     * Confirma que a palavra-passe atual corresponde ao valor persistido.
     *
     * @param  string  $palavraPasseAtual  Palavra-passe atual em texto simples.
     * @param  string  $hashAtual  Hash persistido.
     *
     * @throws PalavraPasseAtualIncorreta Quando a palavra-passe não
     *                                    corresponde.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function validarPalavraPasseAtual(
        #[SensitiveParameter]
        string $palavraPasseAtual,
        #[SensitiveParameter]
        string $hashAtual,
    ): void {
        if (
            Hash::check(
                $palavraPasseAtual,
                $hashAtual,
            )
        ) {
            return;
        }

        throw new PalavraPasseAtualIncorreta(
            'A palavra-passe atual não está correta.',
        );
    }

    /**
     * Confirma que a nova palavra-passe é diferente da atual.
     *
     * @param  string  $novaPalavraPasse  Nova palavra-passe em texto simples.
     * @param  string  $hashAtual  Hash da palavra-passe atual.
     *
     * @throws NovaPalavraPasseIgualAAtual Quando as palavras-passe coincidem.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function validarNovaPalavraPasseDiferente(
        #[SensitiveParameter]
        string $novaPalavraPasse,
        #[SensitiveParameter]
        string $hashAtual,
    ): void {
        if (
            ! Hash::check(
                $novaPalavraPasse,
                $hashAtual,
            )
        ) {
            return;
        }

        throw new NovaPalavraPasseIgualAAtual(
            'A nova palavra-passe deve ser diferente da atual.',
        );
    }
}
