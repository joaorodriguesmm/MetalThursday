<?php

declare(strict_types=1);

namespace Tests\Suporte;

use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Cria uma pequena imagem PNG real, sem depender da extensão GD.
 *
 * @since 2.0.0
 */
final class FotografiaPngTeste
{
    /**
     * @since 2.0.0
     */
    public static function criar(string $nome = 'fotografia.png'): File
    {
        $conteudo = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );

        if (! is_string($conteudo)) {
            throw new RuntimeException('Não foi possível criar a imagem PNG de teste.');
        }

        return UploadedFile::fake()->createWithContent(
            $nome,
            $conteudo,
        );
    }
}
