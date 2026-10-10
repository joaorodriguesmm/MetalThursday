<?php

declare(strict_types=1);

/**
 * Configura a política Cross-Origin Resource Sharing da aplicação.
 *
 * A aplicação não expõe atualmente recursos destinados a consumo
 * cross-origin. A política permanece fechada por omissão e deverá ser
 * alargada explicitamente caso surja uma necessidade funcional futura.
 *
 * @since 2.0.0
 */

return [
    'paths' => [],

    'allowed_methods' => [],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
