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
 * @version 1.1.0
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
     * Comprimento máximo permitido pela estrutura de persistência.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO = 255;

    /**
     * Cria o objeto com um valor previamente validado.
     *
     * @param  string  $valor  Nome normalizado.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function __construct(
        private string $valor,
    ) {}

    /**
     * Cria um nome a partir de texto não normalizado.
     *
     * @param  string  $nome  Nome recebido.
     * @return self Nome válido e normalizado.
     *
     * @throws InvalidArgumentException Quando o nome não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public static function deTexto(
        string $nome,
    ): self {
        self::validarCaracteresControlo(
            $nome,
        );

        $nomeNormalizado =
            self::normalizar(
                $nome,
            );

        self::validarObrigatoriedade(
            $nomeNormalizado,
        );

        self::validarComprimento(
            $nomeNormalizado,
        );

        return new self(
            $nomeNormalizado,
        );
    }

    /**
     * Obtém o nome normalizado.
     *
     * @return string Nome normalizado.
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
     * Obtém o primeiro elemento do nome.
     *
     * @return string Primeiro nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function primeiroNome(): string
    {
        return $this->partes()[0];
    }

    /**
     * Obtém as iniciais do nome.
     *
     * Um nome simples utiliza os dois primeiros caracteres. Um nome composto
     * utiliza a primeira letra do primeiro e do último elemento.
     *
     * @return string Iniciais em maiúsculas.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function iniciais(): string
    {
        $partes =
            $this->partes();

        if (count($partes) === 1) {
            return mb_strtoupper(
                mb_substr(
                    $partes[0],
                    0,
                    2,
                ),
            );
        }

        $primeiroElemento =
            $partes[0];

        $ultimoElemento =
            $partes[array_key_last(
                $partes,
            )];

        return mb_strtoupper(
            mb_substr(
                $primeiroElemento,
                0,
                1,
            )
                .mb_substr(
                    $ultimoElemento,
                    0,
                    1,
                ),
        );
    }

    /**
     * Determina se representa o mesmo nome.
     *
     * A comparação respeita maiúsculas, minúsculas e acentuação porque o nome
     * de apresentação preserva a grafia escolhida pelo utilizador.
     *
     * @param  self  $outro  Outro nome.
     * @return bool Verdadeiro quando os valores coincidem.
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
     * Este nome permanece inalterado por corresponder a um método mágico do
     * PHP.
     *
     * @return string Nome normalizado.
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
     * Este nome permanece em inglês por corresponder ao contrato
     * JsonSerializable do PHP.
     *
     * @return string Nome normalizado.
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
     * Normaliza os espaços do nome.
     *
     * @param  string  $nome  Nome original.
     * @return string Nome normalizado.
     *
     * @throws InvalidArgumentException Quando a normalização falha.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private static function normalizar(
        string $nome,
    ): string {
        $nomeNormalizado =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $nome,
                ),
            );

        if (! is_string($nomeNormalizado)) {
            throw new InvalidArgumentException(
                'Não foi possível normalizar o nome do utilizador.',
            );
        }

        return $nomeNormalizado;
    }

    /**
     * Valida que o nome foi preenchido.
     *
     * @param  string  $nome  Nome normalizado.
     *
     * @throws InvalidArgumentException Quando o nome está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private static function validarObrigatoriedade(
        string $nome,
    ): void {
        if ($nome === '') {
            throw new InvalidArgumentException(
                'O nome do utilizador é obrigatório.',
            );
        }
    }

    /**
     * Valida os limites de comprimento do nome.
     *
     * @param  string  $nome  Nome normalizado.
     *
     * @throws InvalidArgumentException Quando o comprimento não é permitido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private static function validarComprimento(
        string $nome,
    ): void {
        $comprimento =
            mb_strlen(
                $nome,
            );

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
    }

    /**
     * Impede caracteres de controlo não relacionados com espaços.
     *
     * Tabulações e quebras de linha são aceites na entrada e posteriormente
     * normalizadas para espaços simples.
     *
     * @param  string  $nome  Nome original.
     *
     * @throws InvalidArgumentException Quando existem caracteres inválidos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private static function validarCaracteresControlo(
        string $nome,
    ): void {
        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $nome,
            ) !== 1
        ) {
            return;
        }

        throw new InvalidArgumentException(
            'O nome do utilizador contém caracteres inválidos.',
        );
    }

    /**
     * Divide o nome nos respetivos elementos.
     *
     * @return non-empty-list<string> Elementos do nome.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function partes(): array
    {
        $partes =
            preg_split(
                '/\s+/u',
                $this->valor,
                -1,
                PREG_SPLIT_NO_EMPTY,
            );

        if (
            ! is_array($partes)
            || $partes === []
        ) {
            return [
                $this->valor,
            ];
        }

        return array_values(
            $partes,
        );
    }
}
