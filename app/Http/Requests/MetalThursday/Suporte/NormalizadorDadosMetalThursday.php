<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday\Suporte;

/**
 * Centraliza a normalização elementar utilizada pelos pedidos de
 * MetalThursday.
 *
 * Os valores inválidos são preservados sempre que possível para que as regras
 * de validação os possam rejeitar explicitamente.
 *
 * @since 2.0.0
 */
final class NormalizadorDadosMetalThursday
{
    /**
     * Normaliza as secções recebidas.
     *
     * Campos desconhecidos são preservados para que as regras estruturais dos
     * pedidos os possam rejeitar explicitamente.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Secções normalizadas ou valor original.
     *
     * @since 2.0.0
     */
    public static function normalizarSeccoes(
        mixed $valor,
    ): mixed {
        if (! is_array($valor)) {
            return $valor;
        }

        $seccoes = [];

        foreach (array_values($valor) as $seccao) {
            if (! is_array($seccao)) {
                $seccoes[] =
                    $seccao;

                continue;
            }

            $seccao['id'] = self::normalizarIdentificador(
                $seccao['id']
                    ?? null,
            );

            $seccao['tipo_seccao_id'] = self::normalizarIdentificador(
                $seccao['tipo_seccao_id']
                    ?? null,
            );

            $seccao['titulo'] = self::normalizarTextoLinhaOpcional(
                $seccao['titulo']
                    ?? null,
            );

            $seccao['descricao'] = self::normalizarTextoMultilinha(
                $seccao['descricao']
                    ?? null,
            );

            $seccao['artista_id'] = self::normalizarIdentificador(
                $seccao['artista_id']
                    ?? null,
            );

            $seccao['lancamento_id'] = self::normalizarIdentificador(
                $seccao['lancamento_id']
                    ?? null,
            );

            if (array_key_exists('ligacoes', $seccao)) {
                $seccao['ligacoes'] = self::normalizarLigacoes(
                    $seccao['ligacoes'],
                );
            }

            $seccao['ano'] = self::normalizarIdentificador(
                $seccao['ano']
                    ?? null,
            );

            $seccoes[] =
                $seccao;
        }

        return $seccoes;
    }

    /**
     * Normaliza a lista opcional de ligações de uma secção.
     *
     * Campos desconhecidos são preservados para que as regras estruturais dos
     * pedidos os possam rejeitar explicitamente.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Lista normalizada ou valor original.
     *
     * @since 2.0.0
     */
    public static function normalizarLigacoes(
        mixed $valor,
    ): mixed {
        if (! is_array($valor)) {
            return $valor;
        }

        $ligacoes = [];

        foreach (array_values($valor) as $ligacao) {
            if (! is_array($ligacao)) {
                $ligacoes[] = $ligacao;

                continue;
            }

            $ligacao['url'] = self::normalizarTextoOpcional(
                $ligacao['url']
                    ?? null,
            );

            $ligacao['etiqueta'] = self::normalizarTextoLinhaOpcional(
                $ligacao['etiqueta']
                    ?? null,
            );

            $ligacao['incorporar'] = self::normalizarBooleano(
                $ligacao['incorporar']
                    ?? false,
            );

            $ligacoes[] = $ligacao;
        }

        return $ligacoes;
    }

    /**
     * Normaliza os valores booleanos aceites pelos controlos HTML.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Booleano normalizado ou valor original.
     *
     * @since 2.0.0
     */
    public static function normalizarBooleano(
        mixed $valor,
    ): mixed {
        if (is_bool($valor)) {
            return $valor;
        }

        if (
            $valor === 0
            || $valor === '0'
        ) {
            return false;
        }

        if (
            $valor === 1
            || $valor === '1'
        ) {
            return true;
        }

        return $valor;
    }

    /**
     * Normaliza um identificador ou valor inteiro.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Identificador normalizado ou valor original.
     *
     * @since 2.0.0
     */
    public static function normalizarIdentificador(
        mixed $valor,
    ): mixed {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (
            is_string($valor)
            && ctype_digit($valor)
        ) {
            return (int) $valor;
        }

        return $valor;
    }

    /**
     * Normaliza um texto opcional de uma única linha.
     *
     * Caracteres de controlo permanecem inalterados para que a validação os
     * rejeite explicitamente.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Texto normalizado ou valor original.
     *
     * @since 2.0.0
     */
    public static function normalizarTextoLinhaOpcional(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        if (
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $valor,
            ) === 1
        ) {
            return $valor;
        }

        $texto = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                $valor,
            ),
        );

        if (! is_string($texto)) {
            return $valor;
        }

        return $texto !== ''
            ? $texto
            : null;
    }

    /**
     * Normaliza um texto opcional.
     *
     * Apenas espaços ASCII exteriores são removidos.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Texto normalizado ou valor original.
     *
     * @since 2.0.0
     */
    public static function normalizarTextoOpcional(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        $texto = trim(
            $valor,
            ' ',
        );

        return $texto !== ''
            ? $texto
            : null;
    }

    /**
     * Normaliza um texto com várias linhas.
     *
     * Tabulações e quebras de linha são permitidas. Os restantes caracteres
     * de controlo são preservados para que a validação os rejeite.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Texto normalizado ou valor original.
     *
     * @since 2.0.0
     */
    public static function normalizarTextoMultilinha(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $valor,
            ) === 1
        ) {
            return $valor;
        }

        $texto = trim(
            str_replace(
                [
                    "\r\n",
                    "\r",
                ],
                "\n",
                $valor,
            ),
            " \t\n",
        );

        return $texto !== ''
            ? $texto
            : null;
    }
}
