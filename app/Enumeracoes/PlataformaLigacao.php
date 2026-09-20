<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Plataformas reconhecidas nas ligações públicas das secções MetalThursday.
 *
 * A plataforma identifica apenas o serviço. O tipo concreto de conteúdo
 * incorporável é determinado posteriormente a partir do URL.
 *
 * @since 2.0.0
 */
enum PlataformaLigacao: string
{
    case Spotify = 'spotify';
    case AppleMusic = 'apple_music';
    case YouTube = 'youtube';
    case Outro = 'outro';

    /**
     * Obtém o nome apresentado ao utilizador.
     *
     * @since 2.0.0
     */
    public function nome(): string
    {
        return match ($this) {
            self::Spotify => 'Spotify',
            self::AppleMusic => 'Apple Music',
            self::YouTube => 'YouTube / YouTube Music',
            self::Outro => 'Outro',
        };
    }

    /**
     * Indica se a plataforma pode disponibilizar conteúdo incorporado.
     *
     * A possibilidade concreta continua dependente do URL recebido.
     *
     * @since 2.0.0
     */
    public function suportaIncorporacao(): bool
    {
        return $this !== self::Outro;
    }
}
