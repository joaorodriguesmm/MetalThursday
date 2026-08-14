<?php

declare(strict_types=1);

namespace App\ObjetosValor\Utilizadores;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Representa um motivo de suspensão válido e normalizado.
 *
 * O objeto é imutável. Os grupos de espaços são normalizados, o motivo não
 * pode ficar vazio e o respetivo comprimento respeita a estrutura de
 * persistência.
 *
 * @since 2.0.0
 */
final readonly class MotivoSuspensaoUtilizador implements JsonSerializable, Stringable
{
    /**
     * Comprimento máximo permitido pela estrutura de persistência.
     *
     * A constante é pública para que a validação HTTP e a persistência
     * utilizem o mesmo limite.
     *
     * @since 2.0.0
     */
    public const COMPRIMENTO_MAXIMO = 1000;

    /**
     * Cria o objeto com um motivo previamente validado e normalizado.
     *
     * @param  string  $valor  Motivo normalizado.
     *
     * @since 2.0.0
     */
    private function __construct(
        private string $valor,
    ) {}

    /**
     * Cria um motivo a partir de texto não normalizado.
     *
     * @param  string  $motivo  Motivo recebido.
     * @return self Motivo válido e normalizado.
     *
     * @throws InvalidArgumentException Quando o motivo não é válido.
     *
     * @since 2.0.0
     */
    public static function deTexto(
        string $motivo,
    ): self {
        self::validarCodificacao(
            $motivo,
        );

        self::validarCaracteresControlo(
            $motivo,
        );

        $motivoNormalizado = self::normalizar(
            $motivo,
        );

        self::validarObrigatoriedade(
            $motivoNormalizado,
        );

        self::validarComprimento(
            $motivoNormalizado,
        );

        return new self(
            $motivoNormalizado,
        );
    }

    /**
     * Obtém o motivo normalizado.
     *
     * @return string Motivo normalizado.
     *
     * @since 2.0.0
     */
    public function valor(): string
    {
        return $this->valor;
    }

    /**
     * Determina se representa o mesmo motivo.
     *
     * @param  self  $outro  Outro motivo.
     * @return bool Verdadeiro quando os valores coincidem.
     *
     * @since 2.0.0
     */
    public function igualA(
        self $outro,
    ): bool {
        return $this->valor === $outro->valor;
    }

    /**
     * Converte o objeto para texto.
     *
     * O nome permanece inalterado por corresponder a um método mágico do PHP.
     *
     * @return string Motivo normalizado.
     *
     * @since 2.0.0
     */
    public function __toString(): string
    {
        return $this->valor;
    }

    /**
     * Converte o objeto para uma representação compatível com JSON.
     *
     * O nome permanece em inglês por corresponder ao contrato
     * {@see JsonSerializable}.
     *
     * @return string Motivo normalizado.
     *
     * @since 2.0.0
     */
    public function jsonSerialize(): string
    {
        return $this->valor;
    }

    /**
     * Normaliza os espaços do motivo.
     *
     * @param  string  $motivo  Motivo original.
     * @return string Motivo normalizado.
     *
     * @throws InvalidArgumentException Quando a normalização falha.
     *
     * @since 2.0.0
     */
    private static function normalizar(
        string $motivo,
    ): string {
        $motivoNormalizado = preg_replace(
            '/\s+/u',
            ' ',
            $motivo,
        );

        if (! is_string($motivoNormalizado)) {
            throw new InvalidArgumentException(
                'Não foi possível normalizar o motivo da suspensão.',
            );
        }

        return trim(
            $motivoNormalizado,
        );
    }

    /**
     * Valida que o motivo utiliza uma codificação UTF-8 válida.
     *
     * @param  string  $motivo  Motivo original.
     *
     * @throws InvalidArgumentException Quando o texto não é UTF-8 válido.
     *
     * @since 2.0.0
     */
    private static function validarCodificacao(
        string $motivo,
    ): void {
        if (
            preg_match(
                '//u',
                $motivo,
            ) === 1
        ) {
            return;
        }

        throw new InvalidArgumentException(
            'O motivo da suspensão contém texto inválido.',
        );
    }

    /**
     * Valida que o motivo foi preenchido.
     *
     * @param  string  $motivo  Motivo normalizado.
     *
     * @throws InvalidArgumentException Quando o motivo está vazio.
     *
     * @since 2.0.0
     */
    private static function validarObrigatoriedade(
        string $motivo,
    ): void {
        if ($motivo !== '') {
            return;
        }

        throw new InvalidArgumentException(
            'O motivo da suspensão é obrigatório.',
        );
    }

    /**
     * Valida o comprimento máximo do motivo.
     *
     * @param  string  $motivo  Motivo normalizado.
     *
     * @throws InvalidArgumentException Quando o motivo excede o limite.
     *
     * @since 2.0.0
     */
    private static function validarComprimento(
        string $motivo,
    ): void {
        if (
            mb_strlen(
                $motivo,
            ) <= self::COMPRIMENTO_MAXIMO
        ) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'O motivo da suspensão não pode ter mais de %d caracteres.',
                self::COMPRIMENTO_MAXIMO,
            ),
        );
    }

    /**
     * Impede caracteres de controlo não relacionados com espaços.
     *
     * Tabulações e quebras de linha são aceites na entrada e posteriormente
     * normalizadas para espaços simples. Os restantes caracteres de controlo
     * ASCII são rejeitados.
     *
     * @param  string  $motivo  Motivo original.
     *
     * @throws InvalidArgumentException Quando existem caracteres inválidos.
     *
     * @since 2.0.0
     */
    private static function validarCaracteresControlo(
        string $motivo,
    ): void {
        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $motivo,
            ) !== 1
        ) {
            return;
        }

        throw new InvalidArgumentException(
            'O motivo da suspensão contém caracteres inválidos.',
        );
    }
}
