<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Mantém compatibilidade temporária com o nome inglês do modelo de banda.
 *
 * Esta classe deve ser removida depois de todas as referências a
 * `App\Models\Band` terem sido substituídas por `App\Models\Banda`.
 *
 * @deprecated Utilizar {@see Banda}.
 * @since 1.0.0
 *
 * @version 2.0.0
 */
class Band extends Banda {}
