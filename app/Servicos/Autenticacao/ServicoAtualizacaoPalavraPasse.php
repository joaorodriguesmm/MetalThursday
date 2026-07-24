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
 * invalidar sessões baseadas na funcionalidade «lembrar-me».
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class ServicoAtualizacaoPalavraPasse
{
    /**
     * Número máximo de tentativas perante conflitos transitórios da transação.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Comprimento do novo token de autenticação persistente.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_TOKEN_PERSISTENTE = 60;

    /**
     * Atualiza a palavra-passe do utilizador.
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
     * @version 1.1.0
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
                    $utilizadorBloqueado
                        ->getAuthPassword();

                $this->validarPalavraPasseAtual(
                    $palavraPasseAtual,
                    $hashAtual,
                );

                $this->validarNovaPalavraPasseDiferente(
                    $novaPalavraPasse,
                    $hashAtual,
                );

                /*
                 * O cast `hashed` do modelo Utilizador aplica a hash antes da
                 * persistência. A palavra-passe em texto simples nunca é
                 * guardada diretamente na base de dados.
                 */
                $utilizadorBloqueado->password =
                    $novaPalavraPasse;

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
            || ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new InvalidArgumentException(
                'O utilizador deve estar persistido para alterar a palavra-passe.',
            );
        }

        return (int) $identificador;
    }

    /**
     * Confirma que a palavra-passe atual corresponde ao valor persistido.
     *
     * @param  string  $palavraPasseAtual  Palavra-passe atual em texto simples.
     * @param  mixed  $hashAtual  Hash persistida.
     *
     * @throws PalavraPasseAtualIncorreta Quando a palavra-passe não
     *                                    corresponde.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarPalavraPasseAtual(
        #[SensitiveParameter]
        string $palavraPasseAtual,
        #[SensitiveParameter]
        mixed $hashAtual,
    ): void {
        if (
            is_string($hashAtual)
            && $hashAtual !== ''
            && Hash::check(
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
     * @version 1.0.0
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
