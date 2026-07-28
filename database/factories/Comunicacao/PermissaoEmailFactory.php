<?php

declare(strict_types=1);

namespace Database\Factories\Comunicacao;

use App\Models\Comunicacao\PermissaoEmail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para permissões de correio eletrónico.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<PermissaoEmail>
 *
 * @since 2.0.0
 *
 * @version 2.0.0
 */
final class PermissaoEmailFactory extends Factory
{
    /**
     * Comprimento máximo do identificador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_IDENTIFICADOR = 64;

    /**
     * Comprimento máximo do nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_NOME = 100;

    /**
     * Ordem mínima permitida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ORDEM_MINIMA = 1;

    /**
     * Ordem máxima permitida pela coluna unsigned tiny integer.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const ORDEM_MAXIMA = 255;

    /**
     * Modelo associado à factory.
     *
     * @var class-string<PermissaoEmail>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model = PermissaoEmail::class;

    /**
     * Define os atributos predefinidos de uma permissão.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos da permissão.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function definition(): array
    {
        $nome = Str::ucfirst(
            $this
                ->faker
                ->unique()
                ->words(
                    3,
                    true,
                ),
        );

        $sufixo = Str::lower(
            Str::random(
                8,
            ),
        );

        $baseIdentificador = Str::limit(
            Str::slug(
                $nome,
                '_',
            ),
            self::COMPRIMENTO_MAXIMO_IDENTIFICADOR
                - strlen($sufixo)
                - 1,
            '',
        );

        return [
            'identificador' => sprintf(
                '%s_%s',
                $baseIdentificador,
                $sufixo,
            ),

            'nome' => Str::limit(
                $nome,
                self::COMPRIMENTO_MAXIMO_NOME,
                '',
            ),

            'descricao' => $this
                ->faker
                ->sentence(),

            'ordem' => $this
                ->faker
                ->unique()
                ->numberBetween(
                    self::ORDEM_MINIMA,
                    self::ORDEM_MAXIMA,
                ),
        ];
    }

    /**
     * Define o identificador da permissão.
     *
     * @param  string  $identificador  Identificador pretendido.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o identificador não é válido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function comIdentificador(
        string $identificador,
    ): static {
        $identificadorNormalizado = Str::slug(
            trim(
                $identificador,
            ),
            '_',
        );

        if ($identificadorNormalizado === '') {
            throw new InvalidArgumentException(
                'O identificador da permissão não pode estar vazio.',
            );
        }

        if (
            strlen(
                $identificadorNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_IDENTIFICADOR
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O identificador da permissão não pode exceder %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_IDENTIFICADOR,
                ),
            );
        }

        return $this->state(
            static fn (): array => [
                'identificador' => $identificadorNormalizado,
            ],
        );
    }

    /**
     * Define os dados apresentados ao utilizador.
     *
     * @param  string  $nome  Nome da permissão.
     * @param  string  $descricao  Explicação da permissão.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o nome ou a descrição não são
     *                                  válidos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function comDados(
        string $nome,
        string $descricao,
    ): static {
        $nomeNormalizado = Str::squish(
            $nome,
        );

        $descricaoNormalizada = Str::squish(
            $descricao,
        );

        if ($nomeNormalizado === '') {
            throw new InvalidArgumentException(
                'O nome da permissão não pode estar vazio.',
            );
        }

        if (
            mb_strlen(
                $nomeNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_NOME
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O nome da permissão não pode exceder %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_NOME,
                ),
            );
        }

        if ($descricaoNormalizada === '') {
            throw new InvalidArgumentException(
                'A descrição da permissão não pode estar vazia.',
            );
        }

        return $this->state(
            static fn (): array => [
                'nome' => $nomeNormalizado,

                'descricao' => $descricaoNormalizada,
            ],
        );
    }

    /**
     * Define a ordem de apresentação da permissão.
     *
     * @param  int  $ordem  Ordem pretendida.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a ordem não cabe na coluna.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function naOrdem(
        int $ordem,
    ): static {
        if (
            $ordem < self::ORDEM_MINIMA
            || $ordem > self::ORDEM_MAXIMA
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'A ordem da permissão deve estar entre %d e %d.',
                    self::ORDEM_MINIMA,
                    self::ORDEM_MAXIMA,
                ),
            );
        }

        return $this->state(
            static fn (): array => [
                'ordem' => $ordem,
            ],
        );
    }
}
