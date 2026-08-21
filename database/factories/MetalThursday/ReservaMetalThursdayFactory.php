<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Cria dados de teste para reservas de MetalThursday.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<ReservaMetalThursday>
 *
 * @since 2.0.0
 */
final class ReservaMetalThursdayFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<ReservaMetalThursday>
     *
     * @since 2.0.0
     */
    protected $model = ReservaMetalThursday::class;

    /**
     * Define os atributos predefinidos de uma reserva.
     *
     * A data parte de uma quinta-feira conhecida e recua um número único de
     * semanas, garantindo sempre um slot válido.
     *
     * @return array<string, mixed> Atributos da reserva.
     *
     * @since 2.0.0
     */
    public function definition(): array
    {
        $semanasAnteriores = $this
            ->faker
            ->unique()
            ->numberBetween(
                0,
                10000,
            );

        return [
            'data' => CarbonImmutable::create(
                2026,
                1,
                1,
            )->subWeeks(
                $semanasAnteriores,
            ),

            'responsavel_id' => Utilizador::factory(),

            'metal_thursday_id' => null,
        ];
    }

    /**
     * Define a data reservada.
     *
     * @param  CarbonInterface  $data  Quinta-feira pretendida.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a data não corresponde a uma
     *                                  quinta-feira.
     *
     * @since 2.0.0
     */
    public function comData(
        CarbonInterface $data,
    ): static {
        $dataNormalizada = CarbonImmutable::instance(
            $data,
        )->startOfDay();

        if (! $dataNormalizada->isThursday()) {
            throw new InvalidArgumentException(
                'A data da reserva tem de corresponder a uma quinta-feira.',
            );
        }

        return $this->state([
            'data' => $dataNormalizada,
        ]);
    }

    /**
     * Associa um responsável à reserva.
     *
     * Quando nenhum utilizador é indicado, é criado um através da factory
     * respetiva.
     *
     * @param  Utilizador|null  $utilizador  Responsável pretendido.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando o utilizador indicado não está
     *                                  persistido.
     *
     * @since 2.0.0
     */
    public function comResponsavel(
        ?Utilizador $utilizador = null,
    ): static {
        if ($utilizador !== null) {
            $this->validarModeloPersistido(
                $utilizador,
                'O responsável pela reserva deve estar persistido.',
            );
        }

        return $this->for(
            $utilizador ?? Utilizador::factory(),
            'responsavel',
        );
    }

    /**
     * Cria uma reserva sem responsável.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     */
    public function semResponsavel(): static
    {
        return $this->state([
            'responsavel_id' => null,
        ]);
    }

    /**
     * Associa a MetalThursday que cumpriu a reserva.
     *
     * A data da reserva passa a corresponder à data da MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday associada.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a MetalThursday não está
     *                                  persistida.
     *
     * @since 2.0.0
     */
    public function comMetalThursday(
        MetalThursday $metalThursday,
    ): static {
        $this->validarModeloPersistido(
            $metalThursday,
            'A MetalThursday associada à reserva deve estar persistida.',
        );

        return $this->state([
            'data' => $metalThursday->data,

            'metal_thursday_id' => $metalThursday->getKey(),
        ]);
    }

    /**
     * Valida que um modelo relacionado já se encontra persistido.
     *
     * @param  Model  $modelo  Modelo a validar.
     * @param  string  $mensagem  Mensagem utilizada em caso de erro.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido.
     *
     * @since 2.0.0
     */
    private function validarModeloPersistido(
        Model $modelo,
        string $mensagem,
    ): void {
        if (
            ! $modelo->exists
            || $modelo->getKey() === null
        ) {
            throw new InvalidArgumentException(
                $mensagem,
            );
        }
    }
}
