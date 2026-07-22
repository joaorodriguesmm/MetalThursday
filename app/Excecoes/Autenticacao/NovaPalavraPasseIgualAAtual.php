<?php

declare(strict_types=1);

namespace App\Excecoes\Autenticacao;

use DomainException;

/**
 * Indica que a nova palavra-passe coincide com a palavra-passe atual.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class NovaPalavraPasseIgualAAtual extends DomainException {}
