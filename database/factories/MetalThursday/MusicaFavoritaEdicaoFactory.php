<?php

declare(strict_types=1);

namespace Database\Factories\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MusicaFavoritaEdicao;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * Cria dados de teste para músicas favoritas de uma edição.
 *
 * @extends Factory<MusicaFavoritaEdicao>
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
class MusicaFavoritaEdicaoFactory extends Factory
{
    /**
     * Modelo associado à factory.
     *
     * @var class-string<MusicaFavoritaEdicao>
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $model = MusicaFavoritaEdicao::class;

    /**
     * Define os atributos por omissão de uma música favorita.
     *
     * @return array<string, mixed> Atributos da música favorita.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function definition(): array
    {
        return [
            'edicao_id' => Edicao::factory(),
            'utilizador_id' => Utilizador::factory(),

            'posicao' => $this
                ->faker
                ->numberBetween(
                    1,
                    3,
                ),

            'musica' => sprintf(
                '%s - %s',
                $this
                    ->faker
                    ->words(
                        2,
                        true,
                    ),
                $this
                    ->faker
                    ->words(
                        3,
                        true,
                    ),
            ),

            'registado_por_id' => null,
        ];
    }

    /**
     * Associa a música favorita a uma edição.
     *
     * @param  Edicao  $edicao  Edição pretendida.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function paraEdicao(
        Edicao $edicao,
    ): static {
        return $this->for(
            $edicao,
            'edicao',
        );
    }

    /**
     * Associa a escolha ao respetivo utilizador.
     *
     * @param  Utilizador  $utilizador  Proprietário da escolha.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function pertencenteA(
        Utilizador $utilizador,
    ): static {
        return $this->for(
            $utilizador,
            'utilizador',
        );
    }

    /**
     * Define o utilizador que registou a escolha.
     *
     * @param  Utilizador  $utilizador  Utilizador responsável pelo registo.
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function registadaPor(
        Utilizador $utilizador,
    ): static {
        return $this->for(
            $utilizador,
            'registadoPor',
        );
    }

    /**
     * Define a posição da música favorita.
     *
     * @param  int  $posicao  Posição entre um e três.
     * @return static Factory configurada.
     *
     * @throws InvalidArgumentException Quando a posição não é válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function comPosicao(
        int $posicao,
    ): static {
        if ($posicao < 1 || $posicao > 3) {
            throw new InvalidArgumentException(
                'A posição da música favorita deve estar compreendida entre um e três.',
            );
        }

        return $this->state(
            fn (): array => [
                'posicao' => $posicao,
            ],
        );
    }

    /**
     * Cria uma escolha cujo proprietário deixou de estar identificado.
     *
     * @return static Factory configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function semUtilizador(): static
    {
        return $this->state(
            fn (): array => [
                'utilizador_id' => null,
            ],
        );
    }
}
