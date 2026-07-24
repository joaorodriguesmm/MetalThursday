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
 * @version 1.1.0
 */
final class PermissaoEmailFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<PermissaoEmail>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model =
        PermissaoEmail::class;

    /**
     * Define os atributos predefinidos de uma permissão de correio eletrónico.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos da permissão.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function definition(): array
    {
        $nome =
            $this
                ->faker
                ->unique()
                ->words(
                    3,
                    true,
                );

        return [
            'nome' => Str::ucfirst(
                $nome,
            ),

            'identificador' => sprintf(
                '%s_%s',
                Str::slug(
                    $nome,
                    '_',
                ),
                Str::lower(
                    Str::random(
                        8,
                    ),
                ),
            ),

            'descricao' => $this
                ->faker
                ->sentence(),
        ];
    }

    /**
     * Cria uma permissão sem descrição.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function semDescricao(): static
    {
        return $this->state(
            static fn (): array => [
                'descricao' => null,
            ],
        );
    }

    /**
     * Define um identificador conhecido para a permissão.
     *
     * O identificador recebido é normalizado para minúsculas, sem acentos e
     * com palavras separadas por underscores.
     *
     * @param  string  $identificador  Identificador pretendido.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o identificador não contém
     *                                  caracteres válidos.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function comIdentificador(
        string $identificador,
    ): static {
        $identificadorNormalizado =
            Str::slug(
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

        return $this->state(
            static fn (): array => [
                'identificador' => $identificadorNormalizado,
            ],
        );
    }
}
