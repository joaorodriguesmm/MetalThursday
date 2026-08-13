<?php

declare(strict_types=1);

namespace App\Excecoes\Autenticacao;

use DomainException;
use Throwable;

/**
 * Indica que a palavra-passe atual não corresponde ao valor persistido.
 *
 * @since 2.0.0
 */
final class PalavraPasseAtualIncorreta extends DomainException
{
    /**
     * Cria a exceção de palavra-passe atual incorreta.
     *
     * @param  string  $mensagem  Mensagem descritiva da exceção.
     * @param  int  $codigo  Código interno da exceção.
     * @param  Throwable|null  $excecaoAnterior  Exceção anterior, quando
     *                                           aplicável.
     *
     * @since 2.0.0
     */
    public function __construct(
        string $mensagem = 'A palavra-passe atual está incorreta.',
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
