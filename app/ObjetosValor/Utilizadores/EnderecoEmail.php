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
 * A aplicação considera equivalentes os endereços que diferem apenas no uso
 * de maiúsculas e minúsculas.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
final readonly class EnderecoEmail implements JsonSerializable, Stringable
{
    /**
     * Comprimento máximo permitido pela estrutura de persistência.
     *
     * Esta constante é pública para que as regras de validação da camada HTTP
     * utilizem o mesmo limite do objeto de valor.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public const COMPRIMENTO_MAXIMO = 255;

    /**
     * Cria o objeto com um endereço previamente validado e normalizado.
     *
     * @param  string  $valor  Endereço normalizado.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function __construct(
        private string $valor,
    ) {}

    /**
     * Cria um endereço a partir de texto não normalizado.
     *
     * @param  string  $email  Endereço recebido.
     * @return self Endereço válido e normalizado.
     *
     * @throws InvalidArgumentException Quando o endereço não é válido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public static function deTexto(
        string $email,
    ): self {
        self::validarCaracteresControlo(
            $email,
        );

        $emailNormalizado = mb_strtolower(
            trim(
                $email,
            ),
        );

        self::validarObrigatoriedade(
            $emailNormalizado,
        );

        self::validarComprimento(
            $emailNormalizado,
        );

        self::validarFormato(
            $emailNormalizado,
        );

        return new self(
            $emailNormalizado,
        );
    }

    /**
     * Obtém o endereço normalizado.
     *
     * @return string Endereço normalizado.
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
     * @param  self  $outro  Outro endereço.
     * @return bool Verdadeiro quando os endereços coincidem.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
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
     * @return string Endereço normalizado.
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
     * Converte o objeto para uma representação compatível com JSON.
     *
     * O nome permanece em inglês por corresponder ao contrato
     * {@see JsonSerializable}.
     *
     * @return string Endereço normalizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function jsonSerialize(): string
    {
        return $this->valor;
    }

    /**
     * Valida que o endereço foi preenchido.
     *
     * @param  string  $email  Endereço normalizado.
     *
     * @throws InvalidArgumentException Quando o endereço está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private static function validarObrigatoriedade(
        string $email,
    ): void {
        if ($email !== '') {
            return;
        }

        throw new InvalidArgumentException(
            'O endereço de e-mail é obrigatório.',
        );
    }

    /**
     * Valida o comprimento máximo do endereço.
     *
     * @param  string  $email  Endereço normalizado.
     *
     * @throws InvalidArgumentException Quando o endereço excede o limite.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private static function validarComprimento(
        string $email,
    ): void {
        if (
            mb_strlen(
                $email,
            ) <= self::COMPRIMENTO_MAXIMO
        ) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'O endereço de e-mail não pode ter mais de %d caracteres.',
                self::COMPRIMENTO_MAXIMO,
            ),
        );
    }

    /**
     * Impede a utilização de caracteres de controlo.
     *
     * A validação é realizada antes da normalização para que quebras de linha,
     * tabulações ou outros caracteres de controlo não sejam silenciosamente
     * removidos de um endereço recebido.
     *
     * @param  string  $email  Endereço original.
     *
     * @throws InvalidArgumentException Quando existem caracteres de controlo.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private static function validarCaracteresControlo(
        string $email,
    ): void {
        if (
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $email,
            ) !== 1
        ) {
            return;
        }

        throw new InvalidArgumentException(
            'O endereço de e-mail contém caracteres inválidos.',
        );
    }

    /**
     * Valida o formato geral do endereço.
     *
     * @param  string  $email  Endereço normalizado.
     *
     * @throws InvalidArgumentException Quando o formato não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private static function validarFormato(
        string $email,
    ): void {
        if (
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL,
            ) !== false
        ) {
            return;
        }

        throw new InvalidArgumentException(
            'O endereço de e-mail não é válido.',
        );
    }
}
