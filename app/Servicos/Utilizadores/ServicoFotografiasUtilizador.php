<?php

declare(strict_types=1);

namespace App\Servicos\Utilizadores;

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
 *
 * @version 2.0.0
 */
final class ServicoFotografiasUtilizador
{
    /**
     * Disco utilizado para armazenar as fotografias.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const DISCO = 'public';

    /**
     * Diretório das fotografias dos utilizadores.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const DIRETORIO = 'fotografias/utilizadores';

    /**
     * Comprimento máximo aceite para um caminho relativo.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_CAMINHO = 1024;

    /**
     * Guarda uma fotografia no disco público.
     *
     * O Laravel gera um nome aleatório para o ficheiro, evitando utilizar
     * o nome original fornecido pelo utilizador.
     *
     * @param  UploadedFile  $fotografia  Fotografia validada.
     * @return string Caminho relativo do ficheiro armazenado.
     *
     * @throws InvalidArgumentException Quando o carregamento não é válido.
     * @throws RuntimeException Quando o armazenamento falha ou devolve um
     *                          caminho inesperado.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function guardar(
        UploadedFile $fotografia,
    ): string {
        $this->validarFotografia(
            $fotografia,
        );

        $caminho = $fotografia->store(
            self::DIRETORIO,
            self::DISCO,
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
     * operação é idempotente: um ficheiro inexistente é considerado
     * eliminado.
     *
     * @param  string|null  $caminho  Caminho relativo da fotografia.
     *
     * @throws InvalidArgumentException Quando o caminho não é válido ou não
     *                                  pertence ao diretório autorizado.
     * @throws RuntimeException Quando o ficheiro existe, mas não pode ser
     *                          eliminado.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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

        $disco = Storage::disk(
            self::DISCO,
        );

        if (
            ! $disco->exists(
                $caminhoNormalizado,
            )
        ) {
            return;
        }

        $fotografiaEliminada = $disco->delete(
            $caminhoNormalizado,
        );

        if (! $fotografiaEliminada) {
            throw new RuntimeException(
                sprintf(
                    'Não foi possível eliminar a fotografia "%s".',
                    $caminhoNormalizado,
                ),
            );
        }
    }

    /**
     * Valida o carregamento recebido.
     *
     * A validação do formato, dimensões e tamanho máximo pertence ao pedido
     * HTTP. Este método confirma defensivamente que o carregamento não
     * terminou com um erro.
     *
     * @param  UploadedFile  $fotografia  Fotografia recebida.
     *
     * @throws InvalidArgumentException Quando o carregamento falhou.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     * explícitos. Caminhos absolutos, barras invertidas, segmentos `.` ou
     * `..`, caracteres de controlo e separadores repetidos são rejeitados.
     *
     * @param  string  $caminho  Caminho recebido.
     * @return string Caminho validado.
     *
     * @throws InvalidArgumentException Quando o caminho é inseguro.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function normalizarCaminho(
        string $caminho,
    ): string {
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
                'O caminho da fotografia é demasiado longo.',
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
            || preg_match(
                '/^[A-Za-z]:/',
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
     *
     * @version 1.0.0
     */
    private function caminhoPertenceAoDiretorioPermitido(
        string $caminho,
    ): bool {
        $prefixo = self::DIRETORIO
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
