<?php

declare(strict_types=1);

namespace Tests\Unit\Servicos\Utilizadores;

use App\Servicos\Utilizadores\ServicoFotografiasUtilizador;
use Illuminate\Filesystem\FilesystemAdapter;
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
 */
final class ServicoFotografiasUtilizadorTest extends TestCase
{
    /**
     * Serviço testado.
     *
     * @since 2.0.0
     */
    private ServicoFotografiasUtilizador $servicoFotografias;

    /**
     * Disco público falso utilizado pelos testes.
     *
     * A tipagem explícita permite que os analisadores estáticos reconheçam
     * os métodos de asserção disponibilizados pelo adaptador do Laravel.
     *
     * @since 2.0.0
     */
    private FilesystemAdapter $discoPublico;

    /**
     * Prepara cada teste.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(
            'publico',
        );

        /** @var FilesystemAdapter $discoPublico */
        $discoPublico = Storage::disk(
            'publico',
        );

        $this->discoPublico =
            $discoPublico;

        $this->servicoFotografias =
            new ServicoFotografiasUtilizador;
    }

    /**
     * Confirma que uma fotografia é guardada no diretório autorizado.
     *
     * @since 2.0.0
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

        $this->discoPublico->assertExists(
            $caminho,
        );
    }

    /**
     * Confirma que uma fotografia gerida pela aplicação pode ser eliminada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function elimina_fotografia_do_diretorio_autorizado(): void
    {
        $caminho =
            'fotografias/utilizadores/fotografia-atual.jpg';

        $this->discoPublico->put(
            $caminho,
            'conteudo-de-teste',
        );

        $this->servicoFotografias->eliminar(
            $caminho,
        );

        $this->discoPublico->assertMissing(
            $caminho,
        );
    }

    /**
     * Confirma que eliminar um ficheiro inexistente é uma operação idempotente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function considera_eliminado_um_ficheiro_inexistente(): void
    {
        $caminho =
            'fotografias/utilizadores/inexistente.jpg';

        $this->servicoFotografias->eliminar(
            $caminho,
        );

        $this->discoPublico->assertMissing(
            $caminho,
        );
    }

    /**
     * Confirma que valores sem fotografia não provocam erros.
     *
     * @param  string|null  $caminho  Caminho testado.
     *
     * @since 2.0.0
     */
    #[Test]
    #[DataProvider('fornecerCaminhosSemFotografia')]
    public function ignora_caminhos_sem_fotografia(
        ?string $caminho,
    ): void {
        $this->servicoFotografias->eliminar(
            $caminho,
        );

        $this->addToAssertionCount(
            1,
        );
    }

    /**
     * Fornece valores que representam a ausência de fotografia.
     *
     * @return array<string, array{0: string|null}> Caminhos testados.
     *
     * @since 2.0.0
     */
    public static function fornecerCaminhosSemFotografia(): array
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
     * Impede a eliminação de ficheiros fora do diretório autorizado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_caminho_fora_do_diretorio_autorizado(): void
    {
        $caminho =
            'documentos/ficheiro-importante.pdf';

        $this->discoPublico->put(
            $caminho,
            'conteudo-de-teste',
        );

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O caminho da fotografia não pertence ao diretório autorizado.',
        );

        try {
            $this->servicoFotografias->eliminar(
                $caminho,
            );
        } finally {
            $this->discoPublico->assertExists(
                $caminho,
            );
        }
    }

    /**
     * Confirma que o diretório histórico já não é autorizado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_o_diretorio_historico(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O caminho da fotografia não pertence ao diretório autorizado.',
        );

        $this->servicoFotografias->eliminar(
            'photos/fotografia-antiga.jpg',
        );
    }

    /**
     * Impede caminhos com tentativa de travessia de diretórios.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_travessia_de_diretorios(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O caminho da fotografia contém segmentos inválidos.',
        );

        $this->servicoFotografias->eliminar(
            'fotografias/utilizadores/../../segredo.txt',
        );
    }

    /**
     * Confirma que separadores de diretórios do Windows são rejeitados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_separadores_de_diretorios_do_windows(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O caminho da fotografia não é seguro.',
        );

        $this->servicoFotografias->eliminar(
            'fotografias\\utilizadores\\fotografia.jpg',
        );
    }

    /**
     * Confirma que não são permitidos subdiretórios adicionais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_subdiretorios_dentro_do_diretorio_autorizado(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'O caminho da fotografia não pertence ao diretório autorizado.',
        );

        $this->servicoFotografias->eliminar(
            'fotografias/utilizadores/arquivo/fotografia.jpg',
        );
    }
}
