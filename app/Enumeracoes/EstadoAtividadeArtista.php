<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa o estado conhecido da atividade de um artista.
 *
 * A ausência de valor representa um estado desconhecido ou não indicado.
 *
 * @since 2.0.0
 */
enum EstadoAtividadeArtista: string
{
    /**
     * Artista atualmente ativo.
     *
     * @since 2.0.0
     */
    case Ativo = 'ativo';

    /**
     * Artista temporariamente em hiato.
     *
     * @since 2.0.0
     */
    case EmHiato = 'em_hiato';

    /**
     * Artista cuja atividade terminou.
     *
     * @since 2.0.0
     */
    case Terminado = 'terminado';

    /**
     * Obtém a etiqueta apresentada ao utilizador.
     *
     * @return string Etiqueta legível.
     *
     * @since 2.0.0
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::EmHiato => 'Em hiato',
            self::Terminado => 'Atividade terminada',
        };
    }
}
