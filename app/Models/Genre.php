<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Mantém compatibilidade temporária com o nome inglês do modelo de género.
 *
 * Esta classe deve ser removida depois de todas as referências a
 * `App\Models\Genre` terem sido substituídas por `App\Models\Genero`.
 *
 * @deprecated Utilizar {@see Genero}.
 * @since 1.0.0
 *
 * @version 2.0.0
 */
class Genre extends Genero {}
