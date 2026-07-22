<?php

declare(strict_types=1);

namespace Tests\Unit\Servicos\Utilizadores;

use App\Servicos\Utilizadores\ServicoFotografiasUtilizador;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o serviço responsável pelas fotografias dos utilizadores.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoFotografiasUtilizadorTest extends TestCase
{
    /**
     * Serviço testado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private ServicoFotografiasUtilizador $servicoFotografias;

    /**
     * Prepara cada teste.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->servicoFotografias =
            new ServicoFotografiasUtilizador;
    }

    /**
     * Confirma que uma fotografia é guardada no diretório atual.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function guarda_fotografia_no_diretorio_dos_utilizadores(): void
    {
        $fotografia = UploadedFile::fake()->create(
            name: 'fotografia.jpg',
            kilobytes: 128,
            mimeType: 'image/jpeg',
        );

        $caminho = $this->servicoFotografias->guardar(
            $fotografia,
        );

        self::assertStringStartsWith(
            'fotografias/utilizadores/',
            $caminho,
        );

        self::assertNotSame(
            'fotografias/utilizadores/fotografia.jpg',
            $caminho,
        );

        Storage::disk('public')->assertExists(
            $caminho,
        );
    }

    /**
     * Confirma que uma fotografia do diretório atual pode ser eliminada.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function elimina_fotografia_do_diretorio_atual(): void
    {
        $caminho =
            'fotografias/utilizadores/fotografia-atual.jpg';

        Storage::disk('public')->put(
            $caminho,
            'conteudo-de-teste',
        );

        $resultado = $this->servicoFotografias->eliminar(
            $caminho,
        );

        self::assertTrue($resultado);

        Storage::disk('public')->assertMissing(
            $caminho,
        );
    }

    /**
     * Confirma a compatibilidade com o diretório histórico.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function elimina_fotografia_do_diretorio_historico(): void
    {
        $caminho = 'photos/fotografia-antiga.jpg';

        Storage::disk('public')->put(
            $caminho,
            'conteudo-de-teste',
        );

        $resultado = $this->servicoFotografias->eliminar(
            $caminho,
        );

        self::assertTrue($resultado);

        Storage::disk('public')->assertMissing(
            $caminho,
        );
    }

    /**
     * Confirma que a ausência física do ficheiro é idempotente.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function considera_eliminado_um_ficheiro_inexistente(): void
    {
        $caminho =
            'fotografias/utilizadores/inexistente.jpg';

        self::assertTrue(
            $this->servicoFotografias->eliminar(
                $caminho,
            ),
        );

        Storage::disk('public')->assertMissing(
            $caminho,
        );
    }

    /**
     * Confirma que valores sem caminho não provocam erros.
     *
     * @param  string|null  $caminho  - Caminho testado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    #[DataProvider('fornecerCaminhosVazios')]
    public function ignora_caminhos_vazios(
        ?string $caminho,
    ): void {
        self::assertTrue(
            $this->servicoFotografias->eliminar(
                $caminho,
            ),
        );
    }

    /**
     * Fornece caminhos que representam a ausência de fotografia.
     *
     * @return array<string, array{0: string|null}> - Caminhos testados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function fornecerCaminhosVazios(): array
    {
        return [
            'nulo' => [
                null,
            ],

            'vazio' => [
                '',
            ],

            'apenas espaços' => [
                '   ',
            ],
        ];
    }

    /**
     * Impede a eliminação de ficheiros fora dos diretórios permitidos.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_caminho_fora_dos_diretorios_permitidos(): void
    {
        $caminho = 'documentos/ficheiro-importante.pdf';

        Storage::disk('public')->put(
            $caminho,
            'conteudo-de-teste',
        );

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O caminho da fotografia não pertence a um diretório autorizado.',
        );

        try {
            $this->servicoFotografias->eliminar(
                $caminho,
            );
        } finally {
            Storage::disk('public')->assertExists(
                $caminho,
            );
        }
    }

    /**
     * Impede caminhos com tentativa de travessia de diretórios.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function rejeita_travessia_de_diretorios(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O caminho da fotografia não é válido.',
        );

        $this->servicoFotografias->eliminar(
            'fotografias/utilizadores/../../segredo.txt',
        );
    }

    /**
     * Normaliza separadores de diretórios provenientes de Windows.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function normaliza_separadores_de_diretorios(): void
    {
        $caminhoNormalizado =
            'fotografias/utilizadores/fotografia.jpg';

        Storage::disk('public')->put(
            $caminhoNormalizado,
            'conteudo-de-teste',
        );

        $resultado = $this->servicoFotografias->eliminar(
            'fotografias\\utilizadores\\fotografia.jpg',
        );

        self::assertTrue($resultado);

        Storage::disk('public')->assertMissing(
            $caminhoNormalizado,
        );
    }
}
