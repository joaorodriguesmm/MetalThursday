<?php

declare(strict_types=1);

namespace App\Servicos\Utilizadores;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

/**
 * Gere o armazenamento das fotografias dos utilizadores.
 *
 * O serviço apenas elimina ficheiros pertencentes ao diretório controlado
 * pela aplicação, impedindo que caminhos arbitrários provoquem a eliminação
 * de outros ficheiros do disco público.
 *
 * @since 2.0.0
 */
final class ServicoFotografiasUtilizador
{
    /**
     * Disco utilizado para armazenar as fotografias.
     *
     * @since 2.0.0
     */
    private const DISCO_PUBLICO =
        'publico';

    /**
     * Diretório das fotografias dos utilizadores.
     *
     * @since 2.0.0
     */
    private const DIRETORIO_FOTOGRAFIAS =
        'fotografias/utilizadores';

    /**
     * Comprimento máximo aceite para o caminho relativo.
     *
     * O valor coincide com o comprimento da coluna
     * `utilizadores.fotografia`.
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_MAXIMO_CAMINHO =
        255;

    /**
     * Guarda uma fotografia no disco público.
     *
     * O Laravel gera um nome aleatório para o ficheiro, evitando utilizar o
     * nome original fornecido pelo utilizador.
     *
     * @param  UploadedFile  $fotografia  Fotografia previamente validada.
     * @return string Caminho relativo do ficheiro armazenado.
     *
     * @throws InvalidArgumentException Quando o carregamento não é válido.
     * @throws RuntimeException Quando o armazenamento falha ou devolve um
     *                          caminho inesperado.
     *
     * @since 2.0.0
     */
    public function guardar(
        UploadedFile $fotografia,
    ): string {
        $this->validarFotografia(
            $fotografia,
        );

        $caminho = $fotografia->store(
            self::DIRETORIO_FOTOGRAFIAS,
            self::DISCO_PUBLICO,
        );

        if (
            ! is_string($caminho)
            || $caminho === ''
        ) {
            throw new RuntimeException(
                'Não foi possível guardar a fotografia do utilizador.',
            );
        }

        $caminhoNormalizado = $this->normalizarCaminho(
            $caminho,
        );

        if (
            ! $this->caminhoPertenceAoDiretorioPermitido(
                $caminhoNormalizado,
            )
        ) {
            throw new RuntimeException(
                'O armazenamento devolveu um caminho de fotografia inesperado.',
            );
        }

        return $caminhoNormalizado;
    }

    /**
     * Elimina uma fotografia gerida pela aplicação.
     *
     * Um caminho nulo ou vazio representa a inexistência de fotografia. A
     * operação é idempotente: um ficheiro inexistente ou eliminado
     * concorrentemente é considerado removido.
     *
     * @param  string|null  $caminho  Caminho relativo da fotografia.
     *
     * @throws InvalidArgumentException Quando o caminho não é válido ou não
     *                                  pertence ao diretório autorizado.
     * @throws RuntimeException Quando o ficheiro continua a existir depois de
     *                          uma tentativa de eliminação falhada.
     *
     * @since 2.0.0
     */
    public function eliminar(
        ?string $caminho,
    ): void {
        if (
            $caminho === null
            || trim($caminho) === ''
        ) {
            return;
        }

        $caminhoNormalizado = $this->normalizarCaminho(
            $caminho,
        );

        if (
            ! $this->caminhoPertenceAoDiretorioPermitido(
                $caminhoNormalizado,
            )
        ) {
            throw new InvalidArgumentException(
                'O caminho da fotografia não pertence ao diretório autorizado.',
            );
        }

        $disco = $this->obterDiscoPublico();

        if (
            ! $disco->exists(
                $caminhoNormalizado,
            )
        ) {
            return;
        }

        if (
            $disco->delete(
                $caminhoNormalizado,
            )
        ) {
            return;
        }

        /*
         * O ficheiro pode ter sido eliminado por outro processo entre a
         * verificação da existência e a tentativa de eliminação.
         */
        if (
            ! $disco->exists(
                $caminhoNormalizado,
            )
        ) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Não foi possível eliminar a fotografia "%s".',
                $caminhoNormalizado,
            ),
        );
    }

    /**
     * Obtém o disco público configurado.
     *
     * @return FilesystemAdapter Adaptador do disco público.
     *
     * @since 2.0.0
     */
    private function obterDiscoPublico(): FilesystemAdapter
    {
        return Storage::disk(
            self::DISCO_PUBLICO,
        );
    }

    /**
     * Valida o carregamento recebido.
     *
     * A validação do formato, das dimensões e do tamanho máximo pertence ao
     * pedido HTTP. Este método confirma defensivamente que o carregamento não
     * terminou com um erro.
     *
     * @param  UploadedFile  $fotografia  Fotografia recebida.
     *
     * @throws InvalidArgumentException Quando o carregamento falhou.
     *
     * @since 2.0.0
     */
    private function validarFotografia(
        UploadedFile $fotografia,
    ): void {
        if ($fotografia->isValid()) {
            return;
        }

        throw new InvalidArgumentException(
            'O carregamento da fotografia não é válido.',
        );
    }

    /**
     * Normaliza e valida um caminho relativo.
     *
     * Apenas são aceites caminhos relativos constituídos por segmentos
     * explícitos. Caminhos absolutos, ligações, parâmetros, fragmentos,
     * barras invertidas, segmentos `.` ou `..`, caracteres de controlo e
     * separadores repetidos são rejeitados.
     *
     * @param  string  $caminho  Caminho recebido.
     * @return string Caminho validado.
     *
     * @throws InvalidArgumentException Quando o caminho não é seguro.
     *
     * @since 2.0.0
     */
    private function normalizarCaminho(
        string $caminho,
    ): string {
        if (
            preg_match(
                '//u',
                $caminho,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'O caminho da fotografia contém texto inválido.',
            );
        }

        $caminhoNormalizado = trim(
            $caminho,
        );

        if (
            $caminhoNormalizado === ''
            || $caminhoNormalizado !== $caminho
        ) {
            throw new InvalidArgumentException(
                'O caminho da fotografia não é válido.',
            );
        }

        if (
            mb_strlen(
                $caminhoNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_CAMINHO
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O caminho da fotografia não pode exceder %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_CAMINHO,
                ),
            );
        }

        if (
            str_starts_with(
                $caminhoNormalizado,
                '/',
            )
            || str_contains(
                $caminhoNormalizado,
                '\\',
            )
            || str_contains(
                $caminhoNormalizado,
                '//',
            )
            || str_contains(
                $caminhoNormalizado,
                '?',
            )
            || str_contains(
                $caminhoNormalizado,
                '#',
            )
            || preg_match(
                '/\A[a-z][a-z0-9+.-]*:/i',
                $caminhoNormalizado,
            ) === 1
            || preg_match(
                '/[\x00-\x1F\x7F]/',
                $caminhoNormalizado,
            ) === 1
        ) {
            throw new InvalidArgumentException(
                'O caminho da fotografia não é seguro.',
            );
        }

        $segmentos = explode(
            '/',
            $caminhoNormalizado,
        );

        foreach ($segmentos as $segmento) {
            if (
                $segmento === ''
                || $segmento === '.'
                || $segmento === '..'
            ) {
                throw new InvalidArgumentException(
                    'O caminho da fotografia contém segmentos inválidos.',
                );
            }
        }

        return implode(
            '/',
            $segmentos,
        );
    }

    /**
     * Determina se o caminho pertence ao diretório autorizado.
     *
     * As fotografias são guardadas diretamente no diretório dos
     * utilizadores. Não são aceites subdiretórios adicionais nem nomes de
     * ficheiro vazios.
     *
     * @param  string  $caminho  Caminho validado.
     * @return bool Verdadeiro quando o ficheiro é gerido pela aplicação.
     *
     * @since 2.0.0
     */
    private function caminhoPertenceAoDiretorioPermitido(
        string $caminho,
    ): bool {
        $prefixo =
            self::DIRETORIO_FOTOGRAFIAS
            .'/';

        if (
            ! str_starts_with(
                $caminho,
                $prefixo,
            )
        ) {
            return false;
        }

        $nomeFicheiro = substr(
            $caminho,
            strlen(
                $prefixo,
            ),
        );

        return $nomeFicheiro !== ''
            && ! str_contains(
                $nomeFicheiro,
                '/',
            );
    }
}
