<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\MetalThursday\RascunhoMetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Cria dados de teste para rascunhos de MetalThursday.
 *
 * O nome `Factory` permanece em inglês por corresponder à convenção de
 * descoberta automática das factories do Laravel.
 *
 * @extends Factory<RascunhoMetalThursday>
 *
 * @since 2.0.0
 */
final class RascunhoMetalThursdayFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<RascunhoMetalThursday>
     *
     * @since 2.0.0
     */
    protected $model = RascunhoMetalThursday::class;

    /**
     * Define os atributos predefinidos de um rascunho.
     *
     * O conteúdo representa um formulário ainda por preencher. A reserva é
     * criada separadamente pela respetiva factory.
     *
     * @return array<string, mixed> Atributos do rascunho.
     *
     * @since 2.0.0
     */
    public function definition(): array
    {
        return [
            'reserva_metal_thursday_id' => ReservaMetalThursday::factory(),

            'dados' => [
                'nome' => null,

                'proximo_nomeado_id' => null,

                'seccoes' => [],
            ],
        ];
    }

    /**
     * Associa o rascunho a uma reserva concreta.
     *
     * @param  ReservaMetalThursday  $reserva  Reserva pretendida.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a reserva não está persistida.
     *
     * @since 2.0.0
     */
    public function comReserva(
        ReservaMetalThursday $reserva,
    ): static {
        $this->validarModeloPersistido(
            $reserva,
            'A reserva associada ao rascunho deve estar persistida.',
        );

        return $this->for(
            $reserva,
            'reservaMetalThursday',
        );
    }

    /**
     * Define os dados editáveis do rascunho.
     *
     * A factory não exige completude porque essa é precisamente uma
     * característica funcional dos rascunhos.
     *
     * @param  array<string, mixed>  $dados  Dados pretendidos.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     */
    public function comDados(
        array $dados,
    ): static {
        return $this->state([
            'dados' => $dados,
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
