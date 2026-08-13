<?php

declare(strict_types=1);

namespace Database\Factories\Geografia;

use App\Models\Geografia\OrigemGeografica;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria dados de teste para origens geográficas.
 *
 * Uma origem geográfica pode representar um país, uma nação constituinte,
 * um território ou uma origem internacional agregada.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<OrigemGeografica>
 *
 * @since 2.0.0
 */
final class OrigemGeograficaFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<OrigemGeografica>
     *
     * @since 2.0.0
     */
    protected $model = OrigemGeografica::class;

    /**
     * Define os atributos predefinidos de uma origem geográfica.
     *
     * O nome `definition` permanece em inglês por corresponder ao método
     * convencional das factories do Laravel.
     *
     * @return array<string, mixed> Atributos da origem geográfica.
     *
     * @since 2.0.0
     */
    public function definition(): array
    {
        return [
            'nome' => $this
                ->faker
                ->unique()
                ->country(),

            'codigo' => Str::upper(
                $this
                    ->faker
                    ->unique()
                    ->countryCode(),
            ),
        ];
    }

    /**
     * Define uma origem geográfica conhecida.
     *
     * A normalização e a validação são delegadas ao contrato definitivo do
     * modelo.
     *
     * @param  string  $nome  Nome da origem geográfica.
     * @param  string  $codigo  Código geográfico da aplicação.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando os dados não são válidos.
     *
     * @since 2.0.0
     */
    public function comDados(
        string $nome,
        string $codigo,
    ): static {
        $origemGeografica = new OrigemGeografica;

        $origemGeografica->nome =
            $nome;

        $origemGeografica->codigo =
            $codigo;

        return $this->state([
            'nome' => $origemGeografica->nome,

            'codigo' => $origemGeografica->codigo,
        ]);
    }
}
