<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Forma como cada utilizador pretende visualizar uma plataforma musical.
 *
 * @since 2.0.0
 */
enum ModoVisualizacaoLigacao: string
{
    case Ocultar = 'ocultar';
    case Ligacao = 'ligacao';
    case Incorporar = 'incorporar';

    /**
     * Descrição apresentada nas preferências do perfil.
     *
     * @since 2.0.0
     */
    public function nome(): string
    {
        return match ($this) {
            self::Ocultar => 'Ocultar',
            self::Ligacao => 'Mostrar ligação',
            self::Incorporar => 'Incorporar',
        };
    }
}
