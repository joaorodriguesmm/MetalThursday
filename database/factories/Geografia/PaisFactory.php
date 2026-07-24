<?php

declare(strict_types=1);

namespace Database\Factories\Geografia;

use App\Models\Geografia\Pais;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para países.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<Pais>
 *
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class PaisFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<Pais>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model =
        Pais::class;

    /**
     * Define os atributos predefinidos de um país.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos do país.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function definition(): array
    {
        return [
            'nome' => $this
                ->faker
                ->unique()
                ->country(),

            'codigo_iso' => Str::upper(
                $this
                    ->faker
                    ->unique()
                    ->countryCode(),
            ),
        ];
    }

    /**
     * Define um país conhecido.
     *
     * O nome é limpo de espaços exteriores e o código ISO é normalizado para
     * maiúsculas.
     *
     * @param  string  $nome  Nome do país.
     * @param  string  $codigoIso  Código ISO 3166-1 alfa-2.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o nome está vazio ou o código
     *                                  ISO não contém exatamente duas letras.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function comDados(
        string $nome,
        string $codigoIso,
    ): static {
        $nomeNormalizado =
            trim(
                $nome,
            );

        $codigoIsoNormalizado =
            Str::upper(
                trim(
                    $codigoIso,
                ),
            );

        if ($nomeNormalizado === '') {
            throw new InvalidArgumentException(
                'O nome do país não pode estar vazio.',
            );
        }

        if (
            preg_match(
                '/^[A-Z]{2}$/',
                $codigoIsoNormalizado,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'O código ISO do país deve conter exatamente duas letras.',
            );
        }

        return $this->state(
            static fn (): array => [
                'nome' => $nomeNormalizado,

                'codigo_iso' => $codigoIsoNormalizado,
            ],
        );
    }
}
