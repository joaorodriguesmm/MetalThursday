<?php

declare(strict_types=1);

namespace App\Excecoes\Autenticacao;

use DomainException;

/**
 * Indica que a palavra-passe atual não corresponde ao valor persistido.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class PalavraPasseAtualIncorreta extends DomainException {}
