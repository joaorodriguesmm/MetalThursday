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
 * O serviço apenas elimina ficheiros pertencentes aos diretórios controlados
 * pela aplicação, evitando que um caminho arbitrário provoque a eliminação de
 * outros ficheiros do disco público.
 *
 * O diretório histórico `photos` permanece temporariamente autorizado para
 * permitir a remoção segura de fotografias criadas pelo fluxo antigo.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
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
     * Diretório atual das fotografias.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const DIRETORIO = 'fotografias/utilizadores';

    /**
     * Diretórios cujos ficheiros podem ser eliminados pelo serviço.
     *
     * @var array<int, string>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const DIRETORIOS_PERMITIDOS = [
        'fotografias/utilizadores/',
        'photos/',
    ];

    /**
     * Guarda uma fotografia no disco público.
     *
     * O Laravel cria um nome aleatório para o ficheiro, evitando confiar no
     * nome original enviado pelo utilizador.
     *
     * @param  UploadedFile  $fotografia  - Fotografia validada.
     * @return string - Caminho relativo do ficheiro guardado.
     *
     * @throws RuntimeException Quando o armazenamento falha.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function guardar(
        UploadedFile $fotografia,
    ): string {
        $caminho = $fotografia->store(
            self::DIRETORIO,
            self::DISCO,
        );

        if (! is_string($caminho) || $caminho === '') {
            throw new RuntimeException(
                'Não foi possível guardar a fotografia do utilizador.',
            );
        }

        return $caminho;
    }

    /**
     * Elimina uma fotografia gerida pela aplicação.
     *
     * Um caminho nulo representa a inexistência de fotografia e não constitui
     * um erro.
     *
     * @param  string|null  $caminho  - Caminho relativo da fotografia.
     * @return bool - Verdadeiro quando não existe ficheiro ou a eliminação foi
     *              concluída.
     *
     * @throws InvalidArgumentException Quando o caminho não pertence a um
     *                                  diretório autorizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function eliminar(?string $caminho): bool
    {
        if ($caminho === null || trim($caminho) === '') {
            return true;
        }

        $caminhoNormalizado = $this->normalizarCaminho(
            $caminho,
        );

        if (! $this->eCaminhoPermitido($caminhoNormalizado)) {
            throw new InvalidArgumentException(
                'O caminho da fotografia não pertence a um diretório autorizado.',
            );
        }

        $disco = Storage::disk(self::DISCO);

        if (! $disco->exists($caminhoNormalizado)) {
            return true;
        }

        return $disco->delete($caminhoNormalizado);
    }

    /**
     * Normaliza um caminho relativo.
     *
     * @param  string  $caminho  - Caminho recebido.
     * @return string - Caminho normalizado.
     *
     * @throws InvalidArgumentException Quando o caminho é inseguro.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarCaminho(
        string $caminho,
    ): string {
        $caminhoNormalizado = ltrim(
            str_replace('\\', '/', trim($caminho)),
            '/',
        );

        if (
            $caminhoNormalizado === ''
            || str_contains($caminhoNormalizado, '../')
            || str_contains($caminhoNormalizado, "\0")
        ) {
            throw new InvalidArgumentException(
                'O caminho da fotografia não é válido.',
            );
        }

        return $caminhoNormalizado;
    }

    /**
     * Determina se o caminho pertence a um diretório autorizado.
     *
     * @param  string  $caminho  - Caminho normalizado.
     * @return bool - Verdadeiro quando o caminho é gerido pela aplicação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function eCaminhoPermitido(
        string $caminho,
    ): bool {
        foreach (
            self::DIRETORIOS_PERMITIDOS as $diretorioPermitido
        ) {
            if (str_starts_with($caminho, $diretorioPermitido)) {
                return true;
            }
        }

        return false;
    }
}
