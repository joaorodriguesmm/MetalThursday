<?php

declare(strict_types=1);

namespace App\ObjetosValor\Utilizadores;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Representa um endereço de e-mail válido e normalizado.
 *
 * O endereço é normalizado para minúsculas e não contém espaços exteriores.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final readonly class EnderecoEmail implements JsonSerializable, Stringable
{
    /**
     * Comprimento máximo permitido.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO = 255;

    /**
     * Endereço normalizado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private string $valor;

    /**
     * Cria o objeto com um endereço previamente validado.
     *
     * @param  string  $valor  - Endereço normalizado.
     * @return void
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function __construct(string $valor)
    {
        $this->valor = $valor;
    }

    /**
     * Cria um endereço a partir de texto não normalizado.
     *
     * @param  string  $email  - Endereço recebido.
     * @return self - Endereço válido e normalizado.
     *
     * @throws InvalidArgumentException Quando o endereço não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function deTexto(string $email): self
    {
        $emailNormalizado = mb_strtolower(
            trim($email),
        );

        if ($emailNormalizado === '') {
            throw new InvalidArgumentException(
                'O endereço de e-mail é obrigatório.',
            );
        }

        if (
            mb_strlen($emailNormalizado)
            > self::COMPRIMENTO_MAXIMO
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O endereço de e-mail não pode ter mais de %d caracteres.',
                    self::COMPRIMENTO_MAXIMO,
                ),
            );
        }

        if (
            filter_var(
                $emailNormalizado,
                FILTER_VALIDATE_EMAIL,
            ) === false
        ) {
            throw new InvalidArgumentException(
                'O endereço de e-mail não é válido.',
            );
        }

        return new self($emailNormalizado);
    }

    /**
     * Obtém o endereço normalizado.
     *
     * @return string - Endereço normalizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function valor(): string
    {
        return $this->valor;
    }

    /**
     * Determina se representa o mesmo endereço.
     *
     * @param  self  $outro  - Outro endereço.
     * @return bool - Verdadeiro quando os endereços coincidem.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function igualA(self $outro): bool
    {
        return hash_equals(
            $this->valor,
            $outro->valor,
        );
    }

    /**
     * Converte o objeto para texto.
     *
     * @return string - Endereço normalizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __toString(): string
    {
        return $this->valor;
    }

    /**
     * Converte o objeto para uma representação JSON.
     *
     * @return string - Endereço normalizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function jsonSerialize(): string
    {
        return $this->valor;
    }
}
