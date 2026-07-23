<?php

declare(strict_types=1);

namespace Database\Factories\Geografia;

use App\Models\Geografia\Pais;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cria dados de teste para países.
 *
 * @extends Factory<Pais>
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
class PaisFactory extends Factory
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
    protected $model = Pais::class;

    /**
     * Define os atributos por omissão de um país.
     *
     * @return array<string, mixed> Atributos do país.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function definition(): array
    {
        return [
            'nome' => $this
                ->faker
                ->unique()
                ->country(),

            'codigo_iso' => $this
                ->faker
                ->unique()
                ->countryCode(),
        ];
    }

    /**
     * Define um país conhecido.
     *
     * @param  string  $nome  Nome do país.
     * @param  string  $codigoIso  Código ISO de duas letras.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comDados(
        string $nome,
        string $codigoIso,
    ): static {
        return $this->state(
            fn (): array => [
                'nome' => $nome,
                'codigo_iso' => $codigoIso,
            ],
        );
    }
}
