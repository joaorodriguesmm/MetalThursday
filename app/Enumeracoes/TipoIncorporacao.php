<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa os tipos de incorporação suportados nas secções.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
enum TipoIncorporacao: string
{
    /**
     * Apresenta apenas a ligação externa.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case Ligacao = 'ligacao';

    /**
     * Incorpora um vídeo do YouTube.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case VideoYouTube = 'video_youtube';

    /**
     * Incorpora uma lista de reprodução do YouTube.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case ListaReproducaoYouTube =
        'lista_reproducao_youtube';

    /**
     * Tenta criar um tipo de incorporação a partir de um valor recebido.
     *
     * Os valores da versão anterior são temporariamente reconhecidos durante
     * a reconstrução da aplicação.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return self|null Tipo correspondente ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function tentarCriar(
        mixed $valor,
    ): ?self {
        if (! is_string($valor)) {
            return null;
        }

        $valorNormalizado = mb_strtolower(
            trim($valor),
        );

        return match ($valorNormalizado) {
            self::Ligacao->value,
            'link' => self::Ligacao,

            self::VideoYouTube->value,
            'youtube_video' => self::VideoYouTube,

            self::ListaReproducaoYouTube->value,
            'youtube_playlist' => self::ListaReproducaoYouTube,

            default => null,
        };
    }

    /**
     * Obtém a etiqueta apresentada ao utilizador.
     *
     * @return string Etiqueta do tipo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Ligacao => 'Ligação externa',

            self::VideoYouTube => 'Vídeo do YouTube',

            self::ListaReproducaoYouTube => 'Lista de reprodução do YouTube',
        };
    }

    /**
     * Obtém a expressão regular utilizada pela interface para reconhecer o
     * tipo de ligação.
     *
     * A validação definitiva continua a ser efetuada no servidor.
     *
     * @return string|null Expressão regular ou nulo para ligações comuns.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function expressaoRegularJavaScript(): ?string
    {
        return match ($this) {
            self::Ligacao => null,

            self::VideoYouTube => '(?:youtube(?:-nocookie)?\\.com\\/(?:watch\\?(?:[^#\\s]*&)?v=|embed\\/|shorts\\/|live\\/)|youtu\\.be\\/)([A-Za-z0-9_-]{11})',

            self::ListaReproducaoYouTube => '(?:youtube(?:-nocookie)?\\.com\\/[^#\\s?]*\\?(?:[^#\\s]*&)?list=)([A-Za-z0-9_-]{10,150})',
        };
    }
}
