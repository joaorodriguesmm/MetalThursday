<?php

declare(strict_types=1);

namespace App\Excecoes\Autenticacao;

use DomainException;
use Throwable;

/**
 * Indica que a nova palavra-passe coincide com a palavra-passe atual.
 *
 * @since 2.0.0
 */
final class NovaPalavraPasseIgualAAtual extends DomainException
{
    /**
     * Cria a exceção de palavra-passe repetida.
     *
     * @param  string  $mensagem  Mensagem descritiva da exceção.
     * @param  int  $codigo  Código interno da exceção.
     * @param  Throwable|null  $excecaoAnterior  Exceção que originou esta
     *                                           exceção, quando aplicável.
     *
     * @since 2.0.0
     */
    public function __construct(
        string $mensagem = 'A nova palavra-passe deve ser diferente da palavra-passe atual.',
        int $codigo = 0,
        ?Throwable $excecaoAnterior = null,
    ) {
        parent::__construct(
            $mensagem,
            $codigo,
            $excecaoAnterior,
        );
    }
}
