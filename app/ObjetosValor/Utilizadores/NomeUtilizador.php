<?php

declare(strict_types=1);

namespace App\ObjetosValor\Utilizadores;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Representa um nome de utilizador válido e normalizado.
 *
 * O objeto é imutável. Depois de criado, garante que o nome não está vazio,
 * respeita os limites definidos e não contém espaços exteriores ou
 * consecutivos.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final readonly class NomeUtilizador implements JsonSerializable, Stringable
{
    /**
     * Comprimento mínimo permitido.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MINIMO = 3;

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
     * Nome normalizado.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private string $valor;

    /**
     * Cria o objeto com um valor previamente validado.
     *
     * @param  string  $valor  - Nome normalizado.
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
     * Cria um nome a partir de texto não normalizado.
     *
     * @param  string  $nome  - Nome recebido.
     * @return self - Nome válido e normalizado.
     *
     * @throws InvalidArgumentException Quando o nome não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function deTexto(string $nome): self
    {
        $nomeNormalizado = preg_replace(
            '/\s+/u',
            ' ',
            trim($nome),
        );

        if (
            $nomeNormalizado === null
            || $nomeNormalizado === ''
        ) {
            throw new InvalidArgumentException(
                'O nome do utilizador é obrigatório.',
            );
        }

        $comprimento = mb_strlen($nomeNormalizado);

        if ($comprimento < self::COMPRIMENTO_MINIMO) {
            throw new InvalidArgumentException(
                sprintf(
                    'O nome do utilizador deve ter, pelo menos, %d caracteres.',
                    self::COMPRIMENTO_MINIMO,
                ),
            );
        }

        if ($comprimento > self::COMPRIMENTO_MAXIMO) {
            throw new InvalidArgumentException(
                sprintf(
                    'O nome do utilizador não pode ter mais de %d caracteres.',
                    self::COMPRIMENTO_MAXIMO,
                ),
            );
        }

        return new self($nomeNormalizado);
    }

    /**
     * Obtém o nome normalizado.
     *
     * @return string - Nome normalizado.
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
     * Obtém o primeiro nome.
     *
     * @return string - Primeiro elemento do nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function primeiroNome(): string
    {
        $partes = $this->partes();

        return $partes[0];
    }

    /**
     * Obtém as iniciais do nome.
     *
     * Um nome simples utiliza os dois primeiros caracteres. Um nome composto
     * utiliza a primeira letra do primeiro e do último nome.
     *
     * @return string - Iniciais em maiúsculas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function iniciais(): string
    {
        $partes = $this->partes();

        if (count($partes) === 1) {
            return mb_strtoupper(
                mb_substr(
                    $partes[0],
                    0,
                    2,
                ),
            );
        }

        $primeiraParte = $partes[0];
        $ultimaParte = $partes[array_key_last($partes)];

        return mb_strtoupper(
            mb_substr($primeiraParte, 0, 1)
                .mb_substr($ultimaParte, 0, 1),
        );
    }

    /**
     * Determina se representa o mesmo nome.
     *
     * @param  self  $outro  - Outro nome.
     * @return bool - Verdadeiro quando os valores coincidem.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function igualA(self $outro): bool
    {
        return $this->valor === $outro->valor;
    }

    /**
     * Converte o objeto para texto.
     *
     * @return string - Nome normalizado.
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
     * @return string - Nome normalizado.
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
     * Divide o nome nos respetivos elementos.
     *
     * @return array<int, string> - Elementos do nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function partes(): array
    {
        $partes = preg_split(
            '/\s+/u',
            $this->valor,
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        /*
         * O valor já foi validado no construtor estático, pelo que este caso
         * apenas protege contra uma falha inesperada da expressão regular.
         */
        if ($partes === false || $partes === []) {
            return [$this->valor];
        }

        return $partes;
    }
}
